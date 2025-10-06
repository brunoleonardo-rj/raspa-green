<?php
ob_start();
header_remove();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
session_start();

require_once __DIR__ . '/../conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

define('WITHDRAW_LOG_FILE', __DIR__ . '/../logs_withdraw.txt');

function writeWithdrawLog($msg) {
    file_put_contents(
        WITHDRAW_LOG_FILE,
        date('d/m/Y H:i:s') . " - " . $msg . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

function jsonResponse($payload, $status = 200) {
    http_response_code($status);
    ob_clean(); // limpa qualquer saída anterior
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Log da requisição bruta
$rawInput = file_get_contents('php://input');
writeWithdrawLog("Requisição recebida: {$rawInput}");

if (!isset($_SESSION['usuario_id'])) {
    writeWithdrawLog("ERRO: Usuário não autenticado");
    jsonResponse(['success' => false, 'message' => 'Usuário não autenticado'], 401);
}

$usuario_id = $_SESSION['usuario_id'];
$data = json_decode($rawInput, true);

if (empty($data['amount']) || empty($data['cpf'])) {
    writeWithdrawLog("ERRO: Dados incompletos recebidos");
    jsonResponse(['success' => false, 'message' => 'Dados incompletos'], 400);
}

$amount = (float) $data['amount'];
$cpf = preg_replace('/[^0-9]/', '', $data['cpf']);

if (strlen($cpf) !== 11) {
    writeWithdrawLog("ERRO: CPF inválido - {$cpf}");
    jsonResponse(['success' => false, 'message' => 'CPF inválido'], 400);
}

try {
    $pdo->beginTransaction();
    writeWithdrawLog("Transação iniciada para usuário {$usuario_id}, valor={$amount}, cpf={$cpf}");

    // Verifica saldo
    $stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = :id FOR UPDATE");
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    writeWithdrawLog("Saldo atual do usuário {$usuario_id}: " . ($usuario['saldo'] ?? 'não encontrado'));

    if (!$usuario) {
        throw new Exception('Usuário não encontrado');
    }

    if ($usuario['saldo'] < $amount) {
        throw new Exception('Saldo insuficiente para realizar o saque');
    }

    // Verifica saques pendentes
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM saques WHERE user_id = :user_id AND status = 'PENDING'");
    $stmt->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $hasPending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
    writeWithdrawLog("Saques pendentes do usuário {$usuario_id}: {$hasPending}");

    if ($hasPending > 0) {
        throw new Exception('Você já possui um saque pendente. Aguarde a conclusão para solicitar outro.');
    }

    // Consulta API CPF
    $nome = "Nome não encontrado";
    writeWithdrawLog("Consultando API CPF para {$cpf}");

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api-cpf-gratis.p.rapidapi.com/?cpf=" . $cpf,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "x-rapidapi-host: api-cpf-gratis.p.rapidapi.com",
            "x-rapidapi-key: e5c1fd4e13msh008c726672c9a43p1218d5jsn9a8b01aa6822"
        ],
        CURLOPT_TIMEOUT => 5
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        writeWithdrawLog("Erro cURL ao consultar CPF: {$err}");
    } else {
        writeWithdrawLog("Resposta API CPF: {$response}");
        $apiData = json_decode($response, true);
        if (isset($apiData['code']) && $apiData['code'] == 200 && !empty($apiData['data']['nome'])) {
            $nome = $apiData['data']['nome'];
        }
    }

    // Atualiza saldo
    $newBalance = $usuario['saldo'] - $amount;
    $stmt = $pdo->prepare("UPDATE usuarios SET saldo = :saldo WHERE id = :id");
    $stmt->bindParam(':saldo', $newBalance);
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    writeWithdrawLog("Saldo atualizado para usuário {$usuario_id}: {$newBalance}");

    // Cria saque
    $transactionId = uniqid('WTH_');
    $stmt = $pdo->prepare("INSERT INTO saques (transactionId, user_id, nome, cpf, valor, status) 
                           VALUES (:transactionId, :user_id, :nome, :cpf, :valor, 'PENDING')");
    $stmt->bindParam(':transactionId', $transactionId);
    $stmt->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':cpf', $cpf);
    $stmt->bindParam(':valor', $amount);
    $stmt->execute();
    writeWithdrawLog("Saque registrado: Tx={$transactionId}, valor={$amount}, nome={$nome}");

    $pdo->commit();
    writeWithdrawLog("Transação concluída com sucesso para usuário {$usuario_id}");

    jsonResponse(['success' => true, 'message' => 'Saque solicitado com sucesso!']);

} catch (Exception $e) {
    $pdo->rollBack();
    writeWithdrawLog("ERRO: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
