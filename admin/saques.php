<?php
include '../includes/session.php';
include '../conexao.php';
include '../includes/notiflix.php';

// === LOG DE SAQUES ===
define('SAQUE_LOG_FILE', __DIR__ . '/../logs_saques.txt');

function writeSaqueLog($msg)
{
    file_put_contents(
        SAQUE_LOG_FILE,
        "[" . date('d/m/Y H:i:s') . "] " . $msg . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

$usuarioId = $_SESSION['usuario_id'];
$admin = ($stmt = $pdo->prepare("SELECT admin FROM usuarios WHERE id = ?"))->execute([$usuarioId]) ? $stmt->fetchColumn() : null;

if ($admin != 1) {
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você não é um administrador!'];
    header("Location: /");
    exit;
}

if (isset($_POST['aprovar_saque'])) {
    $saque_id = $_POST['saque_id'];
    writeSaqueLog("Requisição de aprovação recebida → Saque ID={$saque_id}, Admin={$usuarioId}");

    try {
        $pdo->beginTransaction();

        // Verificar qual gateway está ativo
        $stmt = $pdo->prepare("SELECT active FROM gateway LIMIT 1");
        $stmt->execute();
        $activeGateway = $stmt->fetchColumn();
        writeSaqueLog("Gateway ativo detectado: {$activeGateway}");

        if (!in_array($activeGateway, ['pixup', 'digitopay', 'gatewayproprio'])) {
            throw new Exception('Gateway não configurado ou não suportado.');
        }

        // Buscar dados do saque
        $stmt = $pdo->prepare("SELECT s.*, u.nome, u.email FROM saques s JOIN usuarios u ON s.user_id = u.id WHERE s.id = ? LIMIT 1 FOR UPDATE");
        $stmt->execute([$saque_id]);
        $saque = $stmt->fetch();

        if (!$saque) {
            throw new Exception("Saque não encontrado");
        }
        writeSaqueLog("Saque encontrado → User={$saque['user_id']} | Valor={$saque['valor']} | CPF={$saque['cpf']}");

        if ($activeGateway === 'pixup') {
            // ===== PROCESSAR COM PIXUP =====
            $stmt = $pdo->prepare("SELECT pixup_base_url, pixup_client_id, pixup_client_secret, pixup_backend_url FROM gateway WHERE active='pixup' LIMIT 1");
            $stmt->execute();
            $pixup = $stmt->fetch();

            if (!$pixup) {
                throw new Exception("Credenciais PIXUP não configuradas");
            }

            $auth = base64_encode($pixup['pixup_client_id'] . ':' . $pixup['pixup_client_secret']);

            // STEP 1: Obter Token
            writeSaqueLog("Solicitando TOKEN Pixup para saque {$saque_id}");
            $ch = curl_init($pixup['pixup_base_url'] . '/oauth/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . $auth,
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: PHP-CURL/7.0'
            ]);

            $tokenResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new Exception("Erro cURL no token: " . $curlError);
            }

            $tokenData = json_decode($tokenResponse, true);
            if ($httpCode !== 200 || !isset($tokenData['access_token'])) {
                throw new Exception("Falha ao obter token de acesso da PIXUP. HTTP: $httpCode | Response: $tokenResponse");
            }
            $accessToken = $tokenData['access_token'];
            writeSaqueLog("Token Pixup obtido com sucesso para saque {$saque_id}");

            // STEP 2: Preparar Payload
            $external_id = uniqid() . '-' . time();
            $cpf_limpo = preg_replace('/\D/', '', $saque['cpf']);

            $payload = [
                'amount' => (float) $saque['valor'],
                'description' => 'Saque - ID: ' . $saque['id'],
                'external_id' => $external_id,
                'creditParty' => [
                    'name' => trim($saque['nome']),
                    'keyType' => 'CPF',
                    'key' => $cpf_limpo,
                    'taxId' => $cpf_limpo
                ]
            ];
            writeSaqueLog("Payload Pixup preparado → ExternalID={$external_id}, Valor={$saque['valor']}");

            // STEP 3: Fazer Pagamento
            $ch = curl_init($pixup['pixup_base_url'] . '/pix/payment');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: PHP-CURL/7.0'
            ]);

            writeSaqueLog(
                "Payload Pixup preparado → ExternalID={$external_id}, Valor={$saque['valor']}, Payload=" . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );

            $paymentResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new Exception("Erro cURL no pagamento: " . $curlError);
            }

            $paymentData = json_decode($paymentResponse, true);
            if ($httpCode !== 200 && $httpCode !== 201) {
                $errorMsg = "Falha ao processar pagamento na PIXUP. HTTP: $httpCode | Response: $paymentResponse";
                if ($paymentData && isset($paymentData['message'])) {
                    $errorMsg .= " | Erro da API: " . $paymentData['message'];
                }
                if ($paymentData && isset($paymentData['errors'])) {
                    $errorMsg .= " | Erros: " . json_encode($paymentData['errors']);
                }
                throw new Exception($errorMsg);
            }

            // STEP 4: Atualizar status
            $stmt = $pdo->prepare("UPDATE saques SET 
                status = 'PAID', 
                gateway = 'pixup',
                transaction_id = ?,
                webhook_data = ?
                WHERE id = ?");
            $updateResult = $stmt->execute([
                $external_id,
                json_encode($paymentData),
                $saque_id
            ]);

            if (!$updateResult || $stmt->rowCount() === 0) {
                throw new Exception("Erro ao atualizar status do saque no banco de dados");
            }

            writeSaqueLog("Saque {$saque_id} atualizado no banco como PAID | Tx={$external_id}");
        }

        $pdo->commit();
        writeSaqueLog("Saque {$saque_id} concluído com sucesso via {$activeGateway}");
        $_SESSION['success'] = 'Saque aprovado e pagamento realizado com sucesso!';

    } catch (Exception $e) {
        $pdo->rollBack();
        writeSaqueLog("ERRO no saque {$saque_id}: " . $e->getMessage());
        $_SESSION['failure'] = 'Erro ao aprovar o saque: ' . $e->getMessage();
        error_log("Erro ao processar saque ID {$saque_id} via {$activeGateway}: " . $e->getMessage());
        error_log("Trace: " . $e->getTraceAsString());
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


if (isset($_POST['reprovar_saque'])) {
    $saque_id = $_POST['saque_id'];

    try {
        $pdo->beginTransaction();

        // Buscar dados do saque antes de deletar
        $stmt = $pdo->prepare("SELECT user_id, valor FROM saques WHERE id = ?");
        $stmt->execute([$saque_id]);
        $saque = $stmt->fetch();

        if ($saque) {
            // Buscar saldo atual do usuário
            $stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = ?");
            $stmt->execute([$saque['user_id']]);
            $saldoAtual = $stmt->fetchColumn();

            // Devolver o valor para o saldo do usuário
            $novoSaldo = $saldoAtual + $saque['valor'];
            $stmt = $pdo->prepare("UPDATE usuarios SET saldo = ? WHERE id = ?");
            $stmt->execute([$novoSaldo, $saque['user_id']]);

            // Registrar transação de estorno com saldos corretos
            $stmt = $pdo->prepare("
                INSERT INTO transacoes (user_id, tipo, valor, saldo_anterior, saldo_posterior, status, descricao, created_at) 
                VALUES (?, 'REFUND', ?, ?, ?, 'COMPLETED', 'Estorno de saque reprovado', NOW())
            ");
            $stmt->execute([$saque['user_id'], $saque['valor'], $saldoAtual, $novoSaldo]);
        }

        // Deletar o saque
        $stmt = $pdo->prepare("DELETE FROM saques WHERE id = ?");
        $stmt->execute([$saque_id]);

        $pdo->commit();
        $_SESSION['success'] = 'Saque reprovado e valor devolvido ao usuário!';

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['failure'] = 'Erro ao reprovar o saque: ' . $e->getMessage();
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$nome = ($stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?"))->execute([$usuarioId]) ? $stmt->fetchColumn() : null;
$nome = $nome ? explode(' ', $nome)[0] : null;

$stmt = $pdo->query("SELECT saques.id, saques.user_id, saques.valor, saques.cpf, saques.status, saques.updated_at, saques.gateway, usuarios.nome 
                     FROM saques 
                     JOIN usuarios ON saques.user_id = usuarios.id
                     ORDER BY saques.updated_at DESC");
$saques = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_saques = count($saques);
$saques_aprovados = array_filter($saques, function ($s) {
    return in_array($s['status'], ['PAID', 'REALIZADO']);
});
$saques_pendentes = array_filter($saques, function ($s) {
    return in_array($s['status'], ['PENDING', 'PROCESSING', 'EM PROCESSAMENTO', 'ANALISE']);
});
$valor_total_aprovado = array_sum(array_column($saques_aprovados, 'valor'));
$valor_total_pendente = array_sum(array_column($saques_pendentes, 'valor'));

// Verificar gateway ativo para exibir na interface
$stmt = $pdo->prepare("SELECT active FROM gateway LIMIT 1");
$stmt->execute();
$activeGateway = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite ?? 'Admin'; ?> - Gerenciar Saques</title>
    <?php
    // Se as variáveis não estiverem definidas, buscar do banco
    if (!isset($faviconSite)) {
        try {
            $stmt = $pdo->prepare("SELECT favicon FROM config WHERE id = 1 LIMIT 1");
            $stmt->execute();
            $config_favicon = $stmt->fetch(PDO::FETCH_ASSOC);
            $faviconSite = $config_favicon['favicon'] ?? null;

            // Se $nomeSite não estiver definido, buscar também
            if (!isset($nomeSite)) {
                $stmt = $pdo->prepare("SELECT nome_site FROM config WHERE id = 1 LIMIT 1");
                $stmt->execute();
                $config_nome = $stmt->fetch(PDO::FETCH_ASSOC);
                $nomeSite = $config_nome['nome_site'] ?? 'Raspadinha';
            }
        } catch (PDOException $e) {
            $faviconSite = null;
            $nomeSite = $nomeSite ?? 'Raspadinha';
        }
    }
    ?>
    <?php if ($faviconSite && file_exists($_SERVER['DOCUMENT_ROOT'] . $faviconSite)): ?>
        <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($faviconSite) ?>" />
        <link rel="shortcut icon" href="<?= htmlspecialchars($faviconSite) ?>" />
        <link rel="apple-touch-icon" href="<?= htmlspecialchars($faviconSite) ?>" />
    <?php else: ?>
        <link rel="icon"
            href="data:image/svg+xml,<?= urlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#22c55e"/><text x="50" y="50" text-anchor="middle" dominant-baseline="middle" fill="white" font-family="Arial" font-size="40" font-weight="bold">' . strtoupper(substr($nomeSite, 0, 1)) . '</text></svg>') ?>" />
    <?php endif; ?>
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Notiflix -->
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #000000;
            color: #ffffff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 320px;
            height: 100vh;
            background: linear-gradient(145deg, #0a0a0a 0%, #141414 25%, #1a1a1a 50%, #0f0f0f 100%);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(34, 197, 94, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            box-shadow:
                0 0 50px rgba(34, 197, 94, 0.1),
                inset 1px 0 0 rgba(255, 255, 255, 0.05);
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 20% 20%, rgba(34, 197, 94, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 60%, rgba(59, 130, 246, 0.05) 0%, transparent 50%);
            opacity: 0.8;
            pointer-events: none;
        }

        .sidebar.hidden {
            transform: translateX(-100%);
        }

        .sidebar-header {
            position: relative;
            padding: 2.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, transparent 100%);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            position: relative;
            z-index: 2;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #ffffff;
            box-shadow:
                0 8px 20px rgba(34, 197, 94, 0.3),
                0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .logo-icon::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, #22c55e, #16a34a, #22c55e);
            border-radius: 18px;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .logo:hover .logo-icon::after {
            opacity: 1;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .logo-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
        }

        .logo-subtitle {
            font-size: 0.75rem;
            color: #22c55e;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-menu {
            padding: 2rem 0;
            position: relative;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            padding: 0 2rem 0.75rem 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: #a1a1aa;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            margin: 0.25rem 1rem;
            border-radius: 12px;
            font-weight: 500;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 0 4px 4px 0;
            transform: scaleY(0);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .nav-item:hover::before,
        .nav-item.active::before {
            transform: scaleY(1);
        }

        .nav-item:hover,
        .nav-item.active {
            color: #ffffff;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(34, 197, 94, 0.05) 100%);
            border: 1px solid rgba(34, 197, 94, 0.2);
            transform: translateX(4px);
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.1);
        }

        .nav-icon {
            width: 24px;
            height: 24px;
            margin-right: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            position: relative;
        }

        .nav-text {
            font-size: 0.95rem;
            flex: 1;
        }

        .nav-badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            min-width: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, transparent 100%);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .user-profile:hover {
            background: rgba(34, 197, 94, 0.1);
            border-color: rgba(34, 197, 94, 0.3);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #ffffff;
            font-size: 1rem;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            color: #ffffff;
            font-size: 0.9rem;
            line-height: 1.2;
        }

        .user-role {
            font-size: 0.75rem;
            color: #22c55e;
            font-weight: 500;
        }

        /* Main Content */
        .main-content {
            margin-left: 320px;
            min-height: 100vh;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background:
                radial-gradient(circle at 10% 20%, rgba(34, 197, 94, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.02) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(59, 130, 246, 0.01) 0%, transparent 50%);
        }

        .main-content.expanded {
            margin-left: 0;
        }

        .header {
            position: sticky;
            top: 0;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem 2.5rem;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .menu-toggle {
            display: none;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(34, 197, 94, 0.05));
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #22c55e;
            padding: 0.75rem;
            border-radius: 12px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .menu-toggle:hover {
            background: rgba(34, 197, 94, 0.2);
            transform: scale(1.05);
        }

        .header-title {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, #a1a1aa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-content {
            padding: 2.5rem;
        }

        .welcome-section {
            margin-bottom: 3rem;
        }

        .welcome-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            background: linear-gradient(135deg, #ffffff 0%, #fff 50%, #fff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .welcome-subtitle {
            font-size: 1.25rem;
            color: #6b7280;
            font-weight: 400;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .mini-stat-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(20px);
        }

        .mini-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #22c55e, #16a34a);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .mini-stat-card:hover::before {
            opacity: 1;
        }

        .mini-stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(34, 197, 94, 0.3);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }

        .mini-stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .mini-stat-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(34, 197, 94, 0.1) 100%);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #22c55e;
            font-size: 1rem;
        }

        .mini-stat-icon.warning {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.2) 0%, rgba(251, 191, 36, 0.1) 100%);
            border-color: rgba(251, 191, 36, 0.3);
            color: #f59e0b;
        }

        .mini-stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }

        .mini-stat-label {
            color: #a1a1aa;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .withdrawals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
            gap: 1.5rem;
        }

        .withdrawal-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
        }

        .withdrawal-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(34, 197, 94, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .withdrawal-card:hover::before {
            opacity: 1;
        }

        .withdrawal-card:hover {
            transform: translateY(-4px);
            border-color: rgba(34, 197, 94, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .withdrawal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .withdrawal-user {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .withdrawal-cpf {
            font-size: 0.9rem;
            color: #6b7280;
            font-family: 'Monaco', 'Consolas', monospace;
            background: rgba(0, 0, 0, 0.3);
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .withdrawal-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .withdrawal-status.approved {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1));
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        .withdrawal-status.pending {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(251, 191, 36, 0.1));
            border: 1px solid rgba(251, 191, 36, 0.3);
            color: #f59e0b;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        .withdrawal-value {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .withdrawal-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.875rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .btn-approve {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }

        .btn-approve:hover {
            background: linear-gradient(135deg, #16a34a, #15803d);
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.4);
        }

        .btn-reject {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-reject:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }

        .btn-disabled {
            background: rgba(107, 114, 128, 0.3);
            color: #9ca3af;
            cursor: not-allowed;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .withdrawal-date {
            color: #9ca3af;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .withdrawal-date i {
            color: #6b7280;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.3) 0%, rgba(10, 10, 10, 0.4) 100%);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.3;
            color: #374151;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #9ca3af;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            font-size: 1rem;
            font-weight: 400;
        }

        /* Mobile Styles */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                width: 300px;
                z-index: 1001;
            }

            .sidebar:not(.hidden) {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .header-actions span {
                display: none !important;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .withdrawals-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }

            .page-content {
                padding: 1.5rem;
            }

            .welcome-title {
                font-size: 2.25rem;
            }

            .withdrawal-card {
                padding: 1.5rem;
            }

            .withdrawal-actions {
                flex-direction: column;
            }

            .sidebar {
                width: 280px;
            }
        }

        @media (max-width: 480px) {
            .welcome-title {
                font-size: 1.875rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .withdrawal-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .withdrawal-value {
                font-size: 1.5rem;
            }

            .sidebar {
                width: 260px;
            }
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>

<body>
    <!-- Notifications -->
    <?php if (isset($_SESSION['success'])): ?>
        <script>
            Notiflix.Notify.success('<?= $_SESSION['success'] ?>');
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php elseif (isset($_SESSION['failure'])): ?>
        <script>
            Notiflix.Notify.failure('<?= $_SESSION['failure'] ?>');
        </script>
        <?php unset($_SESSION['failure']); ?>
    <?php endif; ?>

    <!-- Overlay for mobile -->
    <div class="overlay" id="overlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="#" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="logo-text">
                    <div class="logo-title">Dashboard</div>
                </div>
            </a>
        </div>

        <nav class="nav-menu">
            <div class="nav-section">
                <div class="nav-section-title">Principal</div>
                <a href="index.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-chart-pie"></i></div>
                    <div class="nav-text">Dashboard</div>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Gestão</div>
                <a href="usuarios.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-user"></i></div>
                    <div class="nav-text">Usuários</div>
                </a>
                <a href="afiliados.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-user-plus"></i></div>
                    <div class="nav-text">Afiliados</div>
                </a>
                <a href="depositos.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-credit-card"></i></div>
                    <div class="nav-text">Depósitos</div>
                </a>
                <a href="saques.php" class="nav-item active">
                    <div class="nav-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="nav-text">Saques</div>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Sistema</div>
                <a href="config.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-cogs"></i></div>
                    <div class="nav-text">Configurações</div>
                </a>
                <a href="gateway.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-usd"></i></div>
                    <div class="nav-text">Gateway</div>
                </a>
                <a href="banners.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-images"></i></div>
                    <div class="nav-text">Banners</div>
                </a>
                <a href="cartelas.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-diamond"></i></div>
                    <div class="nav-text">Raspadinhas</div>
                </a>
                <a href="../logout" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-sign-out-alt"></i></div>
                    <div class="nav-text">Sair</div>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>

                <div class="header-actions">
                    <span style="color: #a1a1aa; font-size: 0.9rem; display: none;">Bem-vindo,
                        <?= htmlspecialchars($nome) ?></span>
                    <div class="user-avatar">
                        <?= strtoupper(substr($nome, 0, 1)) ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="page-content">
            <!-- Welcome Section -->
            <section class="welcome-section">
                <h2 class="welcome-title">Gestão de Saques</h2>
                <p class="welcome-subtitle">Aprove ou reprove solicitações de saque via PIX de forma segura</p>
            </section>

            <!-- Stats Grid -->
            <section class="stats-grid">
                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value"><?= number_format($total_saques, 0, ',', '.') ?></div>
                    <div class="mini-stat-label">Total de Saques</div>
                </div>

                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value"><?= number_format(count($saques_aprovados), 0, ',', '.') ?></div>
                    <div class="mini-stat-label">Saques Aprovados</div>
                </div>

                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value"><?= number_format(count($saques_pendentes), 0, ',', '.') ?></div>
                    <div class="mini-stat-label">Saques Pendentes</div>
                </div>

                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value">R$ <?= number_format($valor_total_aprovado, 2, ',', '.') ?></div>
                    <div class="mini-stat-label">Valor Total Pago</div>
                </div>
            </section>

            <!-- Withdrawals Section -->
            <section>
                <?php if (empty($saques)): ?>
                    <div class="empty-state">
                        <i class="fas fa-money-bill-wave"></i>
                        <h3>Nenhum saque encontrado</h3>
                        <p>Não há solicitações de saque registradas no sistema ainda</p>
                    </div>
                <?php else: ?>
                    <div class="withdrawals-grid">
                        <?php foreach ($saques as $saque): ?>
                            <div class="withdrawal-card">
                                <div class="withdrawal-header">
                                    <div>
                                        <h3 class="withdrawal-user"><?= htmlspecialchars($saque['nome']) ?></h3>
                                        <div class="withdrawal-cpf">
                                            <i class="fas fa-key"></i>
                                            PIX: <?= htmlspecialchars($saque['cpf']) ?>
                                        </div>
                                    </div>

                                    <div class="withdrawal-status <?= $saque['status'] == 'PAID' ? 'approved' : 'pending' ?>">
                                        <div class="status-dot"></div>
                                        <span><?= $saque['status'] == 'PAID' ? 'Aprovado' : 'Pendente' ?></span>
                                    </div>
                                </div>

                                <div class="withdrawal-value">
                                    R$ <?= number_format($saque['valor'], 2, ',', '.') ?>
                                </div>

                                <?php if ($saque['status'] == 'PENDING'): ?>
                                    <div class="withdrawal-actions">
                                        <form method="POST" style="flex: 1;">
                                            <input type="hidden" name="saque_id" value="<?= $saque['id'] ?>">
                                            <button type="submit" name="aprovar_saque" class="action-btn btn-approve"
                                                onclick="openLoading()">
                                                <i class="fas fa-check"></i>
                                                Aprovar Saque
                                            </button>
                                        </form>
                                        <form method="POST" style="flex: 1;">
                                            <input type="hidden" name="saque_id" value="<?= $saque['id'] ?>">
                                            <button type="submit" name="reprovar_saque" class="action-btn btn-reject"
                                                onclick="openLoading()">
                                                <i class="fas fa-times"></i>
                                                Reprovar
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="withdrawal-actions">
                                        <button class="action-btn btn-disabled" disabled>
                                            <i class="fas fa-check-double"></i>
                                            Saque Processado
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <div class="withdrawal-date">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= date('d/m/Y H:i', strtotime($saque['updated_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script>
        // Loading function
        function openLoading() {
            Notiflix.Loading.standard('Processando solicitação...');
        }

        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const overlay = document.getElementById('overlay');

        menuToggle.addEventListener('click', () => {
            const isHidden = sidebar.classList.contains('hidden');

            if (isHidden) {
                sidebar.classList.remove('hidden');
                overlay.classList.add('active');
            } else {
                sidebar.classList.add('hidden');
                overlay.classList.add('active');
            }
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.add('hidden');
            overlay.classList.remove('active');
        });

        // Close sidebar on window resize if it's mobile
        window.addEventListener('resize', () => {
            if (window.innerWidth <= 1024) {
                sidebar.classList.add('hidden');
                overlay.classList.remove('active');
            } else {
                sidebar.classList.remove('hidden');
                overlay.classList.remove('active');
            }
        });

        // Enhanced hover effects for nav items
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('mouseenter', function () {
                this.style.transform = 'translateX(8px)';
            });

            item.addEventListener('mouseleave', function () {
                if (!this.classList.contains('active')) {
                    this.style.transform = 'translateX(0)';
                }
            });
        });

        // Smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            console.log('%c💸 Gerenciamento de Saques carregado!', 'color: #22c55e; font-size: 16px; font-weight: bold;');

            // Check if mobile on load
            if (window.innerWidth <= 1024) {
                sidebar.classList.add('hidden');
            }

            // Animate cards on load
            const withdrawalCards = document.querySelectorAll('.withdrawal-card');
            withdrawalCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Animate stats cards
            const statCards = document.querySelectorAll('.mini-stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });
        });
    </script>
</body>

</html>