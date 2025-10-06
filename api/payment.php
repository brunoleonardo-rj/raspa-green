<?php
session_start();
header('Content-Type: application/json');

/**
 * Função de log centralizada
 */
function writePaymentLog($msg)
{
    $file = __DIR__ . '/../logs_payment.txt';
    file_put_contents(
        $file,
        date('d/m/Y H:i:s') . " - " . $msg . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

// Validação de método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    writePaymentLog("Método inválido: {$_SERVER['REQUEST_METHOD']}");
    exit;
}

// Captura dos dados enviados
$amount = isset($_POST['amount']) ? floatval(str_replace(',', '.', $_POST['amount'])) : 0;
$cpf = isset($_POST['cpf']) ? preg_replace('/\D/', '', $_POST['cpf']) : '';

writePaymentLog("Requisição recebida - amount={$amount}, cpf={$cpf}");

// Validação de dados
if ($amount <= 0 || strlen($cpf) !== 11) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    writePaymentLog("Erro: Dados inválidos recebidos.");
    exit;
}

require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../classes/Pixup.php';

try {
    // Verifica gateway ativo
    $stmt = $pdo->query("SELECT active FROM gateway LIMIT 1");
    $activeGateway = $stmt->fetchColumn();

    if ($activeGateway !== 'pixup') {
        throw new Exception('Somente PixUp está configurado como gateway.');
    }
    writePaymentLog("Gateway ativo: {$activeGateway}");

    // Verifica se usuário está autenticado
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Usuário não autenticado.');
    }
    $usuario_id = $_SESSION['usuario_id'];

    // Busca dados do usuário
    $stmt = $pdo->prepare("SELECT nome,email FROM usuarios WHERE id=:id LIMIT 1");
    $stmt->execute([':id' => $usuario_id]);
    $usuario = $stmt->fetch();
    if (!$usuario) {
        throw new Exception('Usuário não encontrado.');
    }

    $external_id = uniqid('dep_');
    $idempotencyKey = uniqid() . '-' . time();

    /**
     * Gateway PixUp
     */
    writePaymentLog("Iniciando criação de depósito via PixUp");

    $pixup = new Pixup($pdo);

    $depositData = $pixup->createPixPayment([
        "amount" => $amount,
        "customerData" => [
            "name" => $usuario['nome'],
            "email" => $usuario['email'],
            "document" => $cpf
        ],
        "metadata" => [
            "depositId" => $external_id,
            "description" => "Depósito de R$ {$amount}",
            "postbackUrl" => $pixup->getBackendUrl() . "/callback/pixup.php"
        ]
    ]);

    writePaymentLog("Retorno PixUp: " . json_encode($depositData));

    if (!$depositData['success']) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => $depositData['error']
        ]);
        exit;
    }

    // Salva no banco
    $stmt = $pdo->prepare("INSERT INTO depositos 
        (transactionId,user_id,nome,cpf,valor,status,qrcode,gateway,idempotency_key,created_at) 
        VALUES (:t,:uid,:nome,:cpf,:valor,:status,:qrcode,'pixup',:ikey,NOW())");
    $stmt->execute([
        ':t' => $depositData['transactionId'],
        ':uid' => $usuario_id,
        ':nome' => $usuario['nome'],
        ':cpf' => $cpf,
        ':valor' => $amount,
        ':status' => $depositData['status'],
        ':qrcode' => $depositData['qrCode'],
        ':ikey' => $idempotencyKey
    ]);
    writePaymentLog("Depósito registrado no banco para usuário {$usuario_id}");

    // Captura dados do navegador para envio e persistência
    $fbp = $_COOKIE['_fbp'] ?? null;
    $fbc = $_COOKIE['_fbc'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;


    // Salva sessão de checkout
    $stmt = $pdo->prepare("INSERT INTO checkout_sessions 
    (transaction_id,user_id,email,phone,amount,fbp,fbc,ip_address,user_agent) 
    VALUES (:t,:uid,:email,:phone,:amount,:fbp,:fbc,:ip,:ua)
    ON DUPLICATE KEY UPDATE 
        email=VALUES(email),
        phone=VALUES(phone),
        amount=VALUES(amount),
        fbp=VALUES(fbp),
        fbc=VALUES(fbc),
        ip_address=VALUES(ip_address),
        user_agent=VALUES(user_agent)");

    $stmt->execute([
        ':t' => $external_id,
        ':uid' => $usuario_id,
        ':email' => $usuario['email'],
        ':phone' => $cpf,
        ':amount' => $amount,
        ':fbp' => $fbp,
        ':fbc' => $fbc,
        ':ip' => $ip_address,
        ':ua' => $user_agent
    ]);
    writePaymentLog("Sessão de checkout registrada para transação {$external_id}");

    /**
     * 🔹 Dispara evento Facebook Pixel (InitiateCheckout)
     */
    require_once __DIR__ . '/../classes/FacebookPixel.php';
    $pixel = new FacebookPixel($pdo);

    if ($pixel->isEnabled()) {
        $pixel->sendEvent("InitiateCheckout", [
            "email" => $usuario['email'],
            "phone" => $cpf,
            "amount" => $amount
        ], $external_id, $fbp, $fbc, $ip_address, $user_agent);

        writePaymentLog("Pixel InitiateCheckout disparado para usuário {$usuario_id}");
    } else {
        writePaymentLog("Pixel não configurado, evento ignorado.");
    }

    echo json_encode([
        "success" => true,
        "status" => $depositData['status'],
        "qrcode" => $depositData['qrCode'],
        "qrcode_base64" => $depositData['qrCodeBase64'],
        "gateway" => "pixup"
    ]);
    exit;

} catch (Exception $e) {
    writePaymentLog("ERRO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
