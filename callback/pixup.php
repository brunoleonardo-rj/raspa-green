<?php
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../classes/Pixup.php';
require_once __DIR__ . '/../classes/FacebookPixel.php';

define('PIXUP_LOG_FILE', __DIR__ . '/../logs_callback_pixup.txt');

function writeCallbackLog($msg)
{
    file_put_contents(PIXUP_LOG_FILE, date('d/m/Y H:i:s') . " - " . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

writeCallbackLog("Webhook recebido: {$rawInput}");

if (!$data || !isset($data['requestBody'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload inválido']);
    exit;
}

try {
    $pixup = new Pixup($pdo);
    $parsed = $pixup->processWebhook($data);

    if (!$parsed['transactionId']) {
        throw new Exception("Webhook inválido");
    }

    $transactionId = $parsed['transactionId'];
    $status = strtoupper($parsed['status']);
    $externalId = $data['requestBody']['external_id']; // <-- AQUI

    writeCallbackLog("Webhook recebido: {$externalId}");

    $pdo->beginTransaction();

    // Busca depósito
    $stmt = $pdo->prepare("SELECT id, user_id, valor, status FROM depositos WHERE transactionId = :txid AND gateway = 'pixup' LIMIT 1 FOR UPDATE");
    $stmt->execute([':txid' => $transactionId]);
    $deposito = $stmt->fetch();

    if (!$deposito) {
        $pdo->commit();
        writeCallbackLog("ERRO: Depósito não encontrado para Tx={$transactionId}");
        http_response_code(404);
        echo json_encode(['error' => 'Depósito não encontrado']);
        exit;
    }

    writeCallbackLog("DEPÓSITO ENCONTRADO: " . print_r($deposito, true));

    // Se já está pago, evita duplicidade
    if ($deposito['status'] === 'PAID') {
        $pdo->commit();
        writeCallbackLog("Depósito {$deposito['id']} já estava marcado como PAID.");
        echo json_encode(['message' => 'Já processado']);
        exit;
    }

    // Atualiza status
    if ($status === 'PAID' || $status === 'APPROVED') {
        $stmt = $pdo->prepare("
        UPDATE depositos 
        SET status = 'PAID', pago_em = NOW(), updated_at = NOW() 
        WHERE id = :id
    ");
        $stmt->execute([':id' => $deposito['id']]);

    } else {
        $stmt = $pdo->prepare("
        UPDATE depositos 
        SET status = :status, updated_at = NOW() 
        WHERE id = :id
    ");
        $stmt->execute([
            ':status' => $status,
            ':id' => $deposito['id']
        ]);
    }
    writeCallbackLog("SQL: " . $stmt->queryString . " | PARAMS: " . json_encode(['status' => $status, 'id' => $deposito['id']]));

    writeCallbackLog("DEPÓSITO ATUALIZADO PARA {$status}");

    // Credita saldo somente se pago
    if ($status === 'PAID' || $status === 'APPROVED') {
        $stmt = $pdo->prepare("UPDATE usuarios SET saldo = saldo + :valor WHERE id = :uid");
        $stmt->execute([':valor' => $deposito['valor'], ':uid' => $deposito['user_id']]);
        writeCallbackLog("SALDO CREDITADO: R$ {$deposito['valor']} para usuário {$deposito['user_id']}");

        // Verifica CPA
        $stmt = $pdo->prepare("SELECT indicacao FROM usuarios WHERE id = :uid");
        $stmt->execute([':uid' => $deposito['user_id']]);
        $usuario = $stmt->fetch();

        if ($usuario && !empty($usuario['indicacao'])) {
            writeCallbackLog("USUÁRIO TEM INDICAÇÃO: {$usuario['indicacao']}");

            $stmt = $pdo->prepare("SELECT COUNT(*) as total_pagos FROM depositos WHERE user_id = :uid AND status = 'PAID' AND id != :current_id");
            $stmt->execute([':uid' => $deposito['user_id'], ':current_id' => $deposito['id']]);
            $depositosAnteriores = $stmt->fetch();

            if ($depositosAnteriores['total_pagos'] == 0) {
                $stmt = $pdo->prepare("SELECT id, comissao_cpa, banido FROM usuarios WHERE id = :afiliado_id");
                $stmt->execute([':afiliado_id' => $usuario['indicacao']]);
                $afiliado = $stmt->fetch();

                if ($afiliado && $afiliado['banido'] != 1 && !empty($afiliado['comissao_cpa'])) {
                    $comissao = $afiliado['comissao_cpa'];

                    $stmt = $pdo->prepare("UPDATE usuarios SET saldo = saldo + :comissao WHERE id = :afiliado_id");
                    $stmt->execute([':comissao' => $comissao, ':afiliado_id' => $afiliado['id']]);

                    try {
                        $stmt = $pdo->prepare("INSERT INTO transacoes_afiliados
                            (afiliado_id, usuario_id, deposito_id, valor, created_at)
                            VALUES (:afiliado_id, :usuario_id, :deposito_id, :valor, NOW())");
                        $stmt->execute([
                            ':afiliado_id' => $afiliado['id'],
                            ':usuario_id' => $deposito['user_id'],
                            ':deposito_id' => $deposito['id'],
                            ':valor' => $comissao
                        ]);
                    } catch (Exception $insertError) {
                        writeCallbackLog("ERRO AO INSERIR TRANSAÇÃO AFILIADO: " . $insertError->getMessage());
                    }

                    writeCallbackLog("CPA PAGO: Afiliado {$afiliado['id']} recebeu R$ {$comissao}");
                } else {
                    writeCallbackLog("CPA NÃO PAGO: Afiliado inválido ou sem comissão");
                }
            } else {
                writeCallbackLog("CPA NÃO PAGO: Usuário {$deposito['user_id']} já tinha {$depositosAnteriores['total_pagos']} depósito(s)");
            }
        } else {
            writeCallbackLog("USUÁRIO SEM INDICAÇÃO");
        }

        writeCallbackLog("Webhook IDs => transactionId={$transactionId}, externalId={$externalId}");

        // Exemplo dentro do callback de aprovação

        $stmt = $pdo->prepare("SELECT * FROM checkout_sessions WHERE transaction_id=:t LIMIT 1");
        $stmt->execute([':t' => $externalId]);   // usa external_id, não transactionId
        $session = $stmt->fetch();

        if ($session) {
            require_once __DIR__ . '/../classes/FacebookPixel.php';
            $pixel = new FacebookPixel($pdo);

            if ($pixel->isEnabled()) {
                $pixel->sendEvent(
                    "Purchase",
                    [
                        "email" => $session['email'],
                        "phone" => $session['phone'],
                        "amount" => $session['amount']
                    ],
                    "purchase_" . $session['transaction_id'],
                    $session['fbp'],
                    $session['fbc'],
                    $session['ip_address'],
                    $session['user_agent']
                );
            }
            writeCallbackLog("Pixel Purchase disparado para user_id={$deposito['user_id']}");
        }
    }

    $pdo->commit();
    writeCallbackLog("TRANSAÇÃO FINALIZADA COM SUCESSO");
    echo json_encode(['message' => 'OK']);
} catch (Exception $e) {
    $pdo->rollBack();
    writeCallbackLog("ERRO GERAL: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno']);
}
