<?php
ob_start();
include '../includes/session.php';
include '../conexao.php';
include '../includes/notiflix.php';

$usuarioId = $_SESSION['usuario_id'];
$admin = ($stmt = $pdo->prepare("SELECT admin FROM usuarios WHERE id = ?"))->execute([$usuarioId]) ? $stmt->fetchColumn() : null;

if ($admin != 1) {
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você não é um administrador!'];
    header("Location: /");
    exit;
}

$nome = ($stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?"))->execute([$usuarioId]) ? $stmt->fetchColumn() : null;
$nome = $nome ? explode(' ', $nome)[0] : null;

if (isset($_GET['toggle_banido'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE usuarios SET banido = IF(banido=1, 0, 1) WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = 'Status de banido alterado com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao alterar status!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['toggle_influencer'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE usuarios SET influencer = IF(influencer=1, 0, 1) WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = 'Status de influencer alterado com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao alterar status!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['atualizar_comissao_cpa'])) {
    $id = $_POST['id'];
    $comissao_cpa = str_replace(',', '.', $_POST['comissao_cpa']);
    
    $stmt = $pdo->prepare("UPDATE usuarios SET comissao_cpa = ? WHERE id = ?");
    if ($stmt->execute([$comissao_cpa, $id])) {
        $_SESSION['success'] = 'Comissão CPA atualizada com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao atualizar comissão CPA!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Nova função para atualizar RevShare
if (isset($_POST['atualizar_comissao_revshare'])) {
    $id = $_POST['id'];
    $comissao_revshare = str_replace(',', '.', $_POST['comissao_revshare']);
    
    $stmt = $pdo->prepare("UPDATE usuarios SET comissao_revshare = ? WHERE id = ?");
    if ($stmt->execute([$comissao_revshare, $id])) {
        $_SESSION['success'] = 'Comissão RevShare atualizada com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao atualizar comissão RevShare!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Nova função para buscar detalhes do afiliado via AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] == 'detalhes_afiliado') {
    // Limpar qualquer saída anterior
    ob_clean();
    
    $afiliado_id = $_GET['afiliado_id'];
    
    try {
        // Buscar dados do afiliado
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$afiliado_id]);
        $afiliado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$afiliado) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Afiliado não encontrado']);
            exit;
        }
        
        // Buscar indicados
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   COALESCE(SUM(CASE WHEN d.status = 'PAID' THEN d.valor ELSE 0 END), 0) as total_depositado,
                   COUNT(CASE WHEN d.status = 'PAID' THEN d.id END) as total_depositos,
                   u.created_at as data_cadastro
            FROM usuarios u 
            LEFT JOIN depositos d ON u.id = d.user_id
            WHERE u.indicacao = ? 
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $stmt->execute([$afiliado_id]);
        $indicados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar histórico de comissões CPA (verificar se tabela existe)
        $historico_cpa = [];
        try {
            $stmt = $pdo->prepare("
                SELECT hc.*, u.nome as indicado_nome, u.email as indicado_email
                FROM historico_comissoes hc
                JOIN usuarios u ON hc.indicado_id = u.id
                WHERE hc.afiliado_id = ?
                ORDER BY hc.created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$afiliado_id]);
            $historico_cpa = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Tabela historico_comissoes pode não existir
            $historico_cpa = [];
        }
        
        // Buscar histórico RevShare (verificar se tabela existe)
        $historico_revshare = [];
        try {
            $stmt = $pdo->prepare("
                SELECT hr.*, 
                       u.nome as indicado_nome, 
                       u.email as indicado_email,
                       hr.valor_apostado as valor_perdido,
                       hr.percentual as percentual_revshare,
                       'N/A' as jogo
                FROM historico_revshare hr
                JOIN usuarios u ON hr.usuario_id = u.id
                WHERE hr.afiliado_id = ?
                ORDER BY hr.created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$afiliado_id]);
            $historico_revshare = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Tabela historico_revshare pode não existir
            $historico_revshare = [];
        }
        
        // Calcular estatísticas
        $total_comissao_cpa = 0;
        $total_comissao_revshare = 0;
        
        foreach ($historico_cpa as $cpa) {
            $total_comissao_cpa += floatval($cpa['valor_comissao'] ?? 0);
        }
        
        foreach ($historico_revshare as $rev) {
            $total_comissao_revshare += floatval($rev['valor_revshare'] ?? 0);
        }
        
        // Buscar saldo atual do afiliado
        $stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = ?");
        $stmt->execute([$afiliado_id]);
        $saldo_atual = $stmt->fetchColumn() ?? 0;
        
        $response = [
            'afiliado' => $afiliado,
            'indicados' => $indicados,
            'historico_cpa' => $historico_cpa,
            'historico_revshare' => $historico_revshare,
            'estatisticas' => [
                'total_indicados' => count($indicados),
                'total_depositado_indicados' => array_sum(array_column($indicados, 'total_depositado')),
                'total_comissao_cpa' => $total_comissao_cpa,
                'total_comissao_revshare' => $total_comissao_revshare,
                'saldo_atual' => floatval($saldo_atual)
            ]
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Erro ao buscar dados: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query atualizada para incluir dados de RevShare
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM usuarios WHERE indicacao = u.id) as total_indicados,
          (SELECT COALESCE(SUM(d.valor), 0) FROM depositos d 
           JOIN usuarios u2 ON d.user_id = u2.id 
           WHERE u2.indicacao = u.id AND d.status = 'PAID') as total_depositos,
          (SELECT COALESCE(SUM(valor_revshare), 0) FROM historico_revshare 
           WHERE afiliado_id = u.id) as total_revshare
          FROM usuarios u
          WHERE EXISTS (SELECT 1 FROM usuarios WHERE indicacao = u.id)";

if (!empty($search)) {
    $query .= " AND (u.nome LIKE :search OR u.email LIKE :search OR u.telefone LIKE :search)";
}

$query .= " ORDER BY total_depositos DESC, total_indicados DESC";

$stmt = $pdo->prepare($query);

if (!empty($search)) {
    $searchTerm = "%$search%";
    $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
}

$stmt->execute();
$afiliados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_afiliados = count($afiliados);
$total_indicados = array_sum(array_column($afiliados, 'total_indicados'));
$total_depositos_afiliados = array_sum(array_column($afiliados, 'total_depositos'));
$total_revshare_pago = array_sum(array_column($afiliados, 'total_revshare'));
$influencers_count = count(array_filter($afiliados, function($a) { return $a['influencer'] == 1; }));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite ?? 'Admin'; ?> - Gerenciar Afiliados</title>
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
        <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($faviconSite) ?>"/>
        <link rel="shortcut icon" href="<?= htmlspecialchars($faviconSite) ?>"/>
        <link rel="apple-touch-icon" href="<?= htmlspecialchars($faviconSite) ?>"/>
    <?php else: ?>
        <link rel="icon" href="data:image/svg+xml,<?= urlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#22c55e"/><text x="50" y="50" text-anchor="middle" dominant-baseline="middle" fill="white" font-family="Arial" font-size="40" font-weight="bold">' . strtoupper(substr($nomeSite, 0, 1)) . '</text></svg>') ?>"/>
    <?php endif; ?>
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Notiflix -->
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
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
        
        /* Advanced Sidebar Styles - Same as depositos.php */
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
        
        /* Enhanced Sidebar Header */
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
        
        /* Advanced Navigation */
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
        
        /* Enhanced Header */
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
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
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
        
        /* Main Page Content */
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
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        
        .mini-stat-icon.purple {
            background: linear-gradient(135deg, rgba(147, 51, 234, 0.2) 0%, rgba(147, 51, 234, 0.1) 100%);
            border-color: rgba(147, 51, 234, 0.3);
            color: #9333ea;
        }
        
        .mini-stat-icon.blue {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.1) 100%);
            border-color: rgba(59, 130, 246, 0.3);
            color: #3b82f6;
        }
        
        .mini-stat-icon.orange {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.2) 0%, rgba(249, 115, 22, 0.1) 100%);
            border-color: rgba(249, 115, 22, 0.3);
            color: #f97316;
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
        
        /* Search Section */
        .search-section {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(20px);
        }
        
        .search-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .search-icon-container {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(34, 197, 94, 0.1) 100%);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #22c55e;
            font-size: 1.125rem;
        }
        
        .search-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #ffffff;
        }
        
        .search-container {
            position: relative;
        }
        
        .search-input {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0.875rem 1rem 0.875rem 3rem;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: rgba(34, 197, 94, 0.5);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }
        
        .search-input::placeholder {
            color: #6b7280;
        }
        
        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1rem;
        }
        
        /* Affiliate Cards */
        .affiliates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
            gap: 1.5rem;
        }
        
        .affiliate-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
        }
        
        .affiliate-card::before {
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
        
        .affiliate-card:hover::before {
            opacity: 1;
        }
        
        .affiliate-card:hover {
            transform: translateY(-4px);
            border-color: rgba(34, 197, 94, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .affiliate-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .affiliate-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.75rem;
        }
        
        .affiliate-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge.admin {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
        }
        
        .badge.influencer {
            background: linear-gradient(135deg, #ec4899, #db2777);
            color: white;
        }
        
        .badge.banned {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .badge.affiliate {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        
        /* Contact Info */
        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #e5e7eb;
            font-size: 0.9rem;
        }
        
        .contact-item i {
            color: #22c55e;
            width: 16px;
            text-align: center;
        }
        
        .whatsapp-link {
            color: #25d366;
            margin-left: 0.5rem;
            transition: color 0.3s ease;
            font-size: 1rem;
        }
        
        .whatsapp-link:hover {
            color: #128c7e;
            transform: scale(1.1);
        }
        
        /* Stats Section in Cards */
        .affiliate-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1rem;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            border-color: rgba(34, 197, 94, 0.3);
            background: rgba(34, 197, 94, 0.05);
        }
        
        .stat-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #a1a1aa;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .stat-label i {
            color: #22c55e;
            font-size: 0.8rem;
        }
        
        .stat-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: #22c55e;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .edit-commission {
            background: none;
            border: none;
            color: #60a5fa;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }
        
        .edit-commission:hover {
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
            transform: scale(1.1);
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .action-btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            border: none;
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .btn-ban {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn-unban {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        
        .btn-influencer {
            background: linear-gradient(135deg, #ec4899, #db2777);
            color: white;
        }

        /* Botão de Detalhes */
        .btn-details {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .btn-details:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
        }

        /* Modal de Detalhes */
        .modal-details {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1100;
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
        }

        .modal-details.hidden {
            display: none;
            opacity: 0;
        }

        .modal-details-content {
            background: linear-gradient(135deg, rgba(10, 10, 10, 0.98) 0%, rgba(20, 20, 20, 0.95) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            width: 95%;
            max-width: 1200px;
            max-height: 90vh;
            overflow: hidden;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6);
            position: relative;
        }

        .modal-details-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, transparent 100%);
        }

        .modal-details-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-details-title i {
            color: #22c55e;
            font-size: 1.5rem;
        }

        .close-btn {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 0.75rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .close-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: scale(1.05);
        }

        .modal-details-body {
            padding: 2rem;
            max-height: calc(90vh - 120px);
            overflow-y: auto;
        }

        /* Loading */
        .loading-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem;
            color: #9ca3af;
        }

        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(34, 197, 94, 0.2);
            border-top: 4px solid #22c55e;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Stats Grid no Modal */
        .details-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .details-stat-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.6) 0%, rgba(10, 10, 10, 0.8) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .details-stat-card:hover {
            border-color: rgba(34, 197, 94, 0.3);
            transform: translateY(-2px);
        }

        .details-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .details-stat-icon.green {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(34, 197, 94, 0.1) 100%);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        .details-stat-icon.blue {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.1) 100%);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #3b82f6;
        }

        .details-stat-icon.purple {
            background: linear-gradient(135deg, rgba(147, 51, 234, 0.2) 0%, rgba(147, 51, 234, 0.1) 100%);
            border: 1px solid rgba(147, 51, 234, 0.3);
            color: #9333ea;
        }

        .details-stat-icon.orange {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.2) 0%, rgba(249, 115, 22, 0.1) 100%);
            border: 1px solid rgba(249, 115, 22, 0.3);
            color: #f97316;
        }

        .details-stat-info {
            display: flex;
            flex-direction: column;
        }

        .details-stat-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }

        .details-stat-label {
            font-size: 0.8rem;
            color: #9ca3af;
            font-weight: 500;
        }

        /* Tabs */
        .details-tabs {
            display: flex;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
            gap: 0.5rem;
        }

        .tab-btn {
            background: transparent;
            border: none;
            padding: 1rem 1.5rem;
            color: #9ca3af;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            border-radius: 12px 12px 0 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
        }

        .tab-btn:hover {
            color: #22c55e;
            background: rgba(34, 197, 94, 0.05);
        }

        .tab-btn.active {
            color: #22c55e;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.05) 100%);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-bottom: none;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #22c55e, #16a34a);
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .tab-header {
            margin-bottom: 1.5rem;
        }

        .tab-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .tab-header h3 i {
            color: #22c55e;
        }

        /* Table Container */
        .table-container {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            overflow: hidden;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table th {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.05) 100%);
            color: #22c55e;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .details-table td {
            padding: 1rem;
            color: #e5e7eb;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .details-table tr:hover {
            background: rgba(34, 197, 94, 0.02);
        }

        .details-table tr:last-child td {
            border-bottom: none;
        }

        /* Status badges na tabela */
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.ativo {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1));
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .status-badge.banido {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.1));
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* WhatsApp link na tabela */
        .whatsapp-table-link {
            color: #25d366;
            margin-left: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .whatsapp-table-link:hover {
            color: #128c7e;
            transform: scale(1.1);
        }

        /* Empty state nas tabelas */
        .table-empty {
            padding: 3rem;
            text-align: center;
            color: #6b7280;
        }

        .table-empty i {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.5;
            color: #374151;
        }
        
        .btn-remove-inf {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        /* Affiliate Meta */
        .affiliate-meta {
            color: #9ca3af;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .affiliate-meta i {
            color: #6b7280;
        }
        
        /* Modal Styles */
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
        }
        
        .modal.hidden {
            display: none;
        }
        
        .modal-content {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.98) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .modal-title i {
            color: #22c55e;
        }
        
        .modal-form-group {
            margin-bottom: 1.5rem;
        }
        
        .modal-label {
            display: block;
            color: #e5e7eb;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .modal-input-container {
            position: relative;
        }
        
        .modal-currency {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-weight: 600;
        }
        
        .modal-percentage {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-weight: 600;
        }
        
        .modal-input {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .modal-input.percentage {
            padding: 0.75rem 2.5rem 0.75rem 1rem;
        }
        
        .modal-input:focus {
            outline: none;
            border-color: rgba(34, 197, 94, 0.5);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }
        
        .modal-input::placeholder {
            color: #6b7280;
        }
        
        .modal-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .modal-btn {
            flex: 1;
            padding: 0.875rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .modal-btn-primary {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        
        .modal-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(34, 197, 94, 0.4);
        }
        
        .modal-btn-secondary {
            background: rgba(107, 114, 128, 0.3);
            color: #e5e7eb;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-btn-secondary:hover {
            background: rgba(107, 114, 128, 0.4);
        }
        
        /* Empty State */
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
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .affiliates-grid {
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
            
            .affiliate-card {
                padding: 1.5rem;
            }
            
            .contact-info {
                grid-template-columns: 1fr;
            }
            
            .affiliate-stats {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
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
            
            .sidebar {
                width: 260px;
            }
            
            .modal-content {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .modal-buttons {
                flex-direction: column;
            }
        }
        
        /* Overlay for mobile */
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
    
    <!-- Advanced Sidebar -->
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
                <a href="afiliados.php" class="nav-item active">
                    <div class="nav-icon"><i class="fas fa-user-plus"></i></div>
                    <div class="nav-text">Afiliados</div>
                </a>
                <a href="depositos.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-credit-card"></i></div>
                    <div class="nav-text">Depósitos</div>
                </a>
                <a href="saques.php" class="nav-item">
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
        <!-- Enhanced Header -->
        <header class="header">
            <div class="header-content">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                
                <div class="header-actions">
                    <span style="color: #a1a1aa; font-size: 0.9rem; display: none;">Bem-vindo, <?= htmlspecialchars($nome) ?></span>
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
                <h2 class="welcome-title">Gerenciar Afiliados</h2>
                <p class="welcome-subtitle">Visualize e gerencie todos os afiliados e suas comissões na plataforma</p>
            </section>
            
            <!-- Stats Grid -->
            <section class="stats-grid">
                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value"><?= number_format($total_afiliados, 0, ',', '.') ?></div>
                    <div class="mini-stat-label">Total de Afiliados</div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon purple">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value"><?= number_format($total_indicados, 0, ',', '.') ?></div>
                    <div class="mini-stat-label">Total de Indicados</div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value">R$ <?= number_format($total_depositos_afiliados, 2, ',', '.') ?></div>
                    <div class="mini-stat-label">Depósitos dos Indicados</div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon orange">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value">R$ <?= number_format($total_revshare_pago, 2, ',', '.') ?></div>
                    <div class="mini-stat-label">Total RevShare Pago</div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon blue">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value"><?= number_format($influencers_count, 0, ',', '.') ?></div>
                    <div class="mini-stat-label">Influencers Ativos</div>
                </div>
            </section>
            
            <!-- Search Section -->
            <section class="search-section">
                <div class="search-header">
                    <div class="search-icon-container">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="search-title">Pesquisar Afiliados</h3>
                </div>
                
                <form method="GET">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               class="search-input" 
                               placeholder="Pesquisar por nome, email ou telefone..." 
                               onchange="this.form.submit()">
                    </div>
                </form>
            </section>
            
            <!-- Affiliates Section -->
            <section>
                <?php if (empty($afiliados)): ?>
                    <div class="empty-state">
                        <i class="fas fa-handshake"></i>
                        <h3>Nenhum afiliado encontrado</h3>
                        <p>Não há afiliados que correspondam aos critérios de pesquisa</p>
                    </div>
                <?php else: ?>
                    <div class="affiliates-grid">
                        <?php foreach ($afiliados as $afiliado): ?>
                            <?php 
                            $telefone = $afiliado['telefone'];
                            if (strlen($telefone) == 11) {
                                $telefoneFormatado = '('.substr($telefone, 0, 2).') '.substr($telefone, 2, 5).'-'.substr($telefone, 7);
                            } else {
                                $telefoneFormatado = $telefone;
                            }
                            
                            $whatsappLink = 'https://wa.me/55'.preg_replace('/[^0-9]/', '', $afiliado['telefone']);
                            $comissao_cpa = isset($afiliado['comissao_cpa']) ? number_format($afiliado['comissao_cpa'], 2, ',', '.') : '0,00';
                            $comissao_revshare = isset($afiliado['comissao_revshare']) ? number_format($afiliado['comissao_revshare'], 2, ',', '.') : '0,00';
                            ?>
                            
                            <div class="affiliate-card">
                                <div class="affiliate-header">
                                    <div>
                                        <h3 class="affiliate-name"><?= htmlspecialchars($afiliado['nome']) ?></h3>
                                        <div class="affiliate-badges">
                                            <span class="badge affiliate">Afiliado</span>
                                            <?php if ($afiliado['admin'] == 1): ?>
                                                <span class="badge admin">Admin</span>
                                            <?php endif; ?>
                                            <?php if ($afiliado['influencer'] == 1): ?>
                                                <span class="badge influencer">Influencer</span>
                                            <?php endif; ?>
                                            <?php if ($afiliado['banido'] == 1): ?>
                                                <span class="badge banned">Banido</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="contact-info">
                                    <div class="contact-item">
                                        <i class="fas fa-envelope"></i>
                                        <span><?= htmlspecialchars($afiliado['email']) ?></span>
                                    </div>
                                    <div class="contact-item">
                                        <i class="fas fa-phone"></i>
                                        <span><?= $telefoneFormatado ?></span>
                                        <a href="<?= $whatsappLink ?>" target="_blank" class="whatsapp-link">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="affiliate-stats">
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="fas fa-users"></i>
                                            Indicados
                                        </div>
                                        <div class="stat-value"><?= $afiliado['total_indicados'] ?></div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="fas fa-money-bill-wave"></i>
                                            Depósitos
                                        </div>
                                        <div class="stat-value">R$ <?= number_format($afiliado['total_depositos'], 2, ',', '.') ?></div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="fas fa-percentage"></i>
                                            CPA
                                        </div>
                                        <div class="stat-value">
                                            R$ <?= $comissao_cpa ?>
                                            <button onclick="abrirModalComissao('<?= $afiliado['id'] ?>', '<?= isset($afiliado['comissao_cpa']) ? $afiliado['comissao_cpa'] : '0' ?>', 'cpa')"
                                                    class="edit-commission">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="fas fa-chart-line"></i>
                                            RevShare
                                        </div>
                                        <div class="stat-value">
                                            <?= $comissao_revshare ?>%
                                            <button onclick="abrirModalComissao('<?= $afiliado['id'] ?>', '<?= isset($afiliado['comissao_revshare']) ? $afiliado['comissao_revshare'] : '0' ?>', 'revshare')"
                                                    class="edit-commission">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="fas fa-wallet"></i>
                                            Rev. Ganho
                                        </div>
                                        <div class="stat-value">R$ <?= number_format($afiliado['total_revshare'] ?? 0, 2, ',', '.') ?></div>
                                    </div>
                                </div>
                                
                                <div class="action-buttons">
                                    <a href="?toggle_banido&id=<?= $afiliado['id'] ?>" 
                                    class="action-btn <?= $afiliado['banido'] ? 'btn-unban' : 'btn-ban' ?>">
                                        <i class="fas fa-<?= $afiliado['banido'] ? 'user-check' : 'user-slash' ?>"></i>
                                        <?= $afiliado['banido'] ? 'Desbanir' : 'Banir' ?>
                                    </a>
                                    
                                    <a href="?toggle_influencer&id=<?= $afiliado['id'] ?>" 
                                    class="action-btn <?= $afiliado['influencer'] ? 'btn-remove-inf' : 'btn-influencer' ?>">
                                        <i class="fas fa-<?= $afiliado['influencer'] ? 'user-minus' : 'star' ?>"></i>
                                        <?= $afiliado['influencer'] ? 'Remover Inf.' : 'Tornar Inf.' ?>
                                    </a>
                                    
                                    <button onclick="abrirDetalhesAfiliado(<?= $afiliado['id'] ?>)" 
                                            class="action-btn btn-details">
                                        <i class="fas fa-eye"></i>
                                        Detalhes
                                    </button>
                                </div>
                                
                                <div class="affiliate-meta">
                                    <i class="fas fa-calendar"></i>
                                    <span>Cadastrado em: <?= date('d/m/Y H:i', strtotime($afiliado['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Modal Editar Comissão CPA -->
    <div id="editarComissaoModal" class="modal hidden">
        <div class="modal-content">
            <h2 class="modal-title" id="modalTitle">
                <i class="fas fa-percentage"></i>
                Editar Comissão CPA
            </h2>
            <form method="POST" id="formEditarComissao">
                <input type="hidden" name="id" id="afiliadoId">
                <div class="modal-form-group">
                    <label class="modal-label" id="modalLabel">
                        <i class="fas fa-dollar-sign"></i>
                        Valor da Comissão CPA
                    </label>
                    <div class="modal-input-container">
                        <span class="modal-currency" id="modalCurrency">R$</span>
                        <input type="text" name="comissao_cpa" id="afiliadoComissao" 
                               class="modal-input" 
                               placeholder="0,00" required>
                        <span class="modal-percentage hidden" id="modalPercentage">%</span>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="atualizar_comissao_cpa" class="modal-btn modal-btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i>
                        Salvar
                    </button>
                    <button type="button" onclick="fecharModalComissao()" class="modal-btn modal-btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Editar Comissão RevShare -->
    <div id="editarRevshareModal" class="modal hidden">
        <div class="modal-content">
            <h2 class="modal-title">
                <i class="fas fa-chart-line"></i>
                Editar Comissão RevShare
            </h2>
            <form method="POST" id="formEditarRevshare">
                <input type="hidden" name="id" id="afiliadoIdRevshare">
                <div class="modal-form-group">
                    <label class="modal-label">
                        <i class="fas fa-percentage"></i>
                        Percentual da Comissão RevShare
                    </label>
                    <div class="modal-input-container">
                        <input type="text" name="comissao_revshare" id="afiliadoComissaoRevshare" 
                               class="modal-input percentage" 
                               placeholder="0,00" required>
                        <span class="modal-percentage">%</span>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="atualizar_comissao_revshare" class="modal-btn modal-btn-primary">
                        <i class="fas fa-save"></i>
                        Salvar
                    </button>
                    <button type="button" onclick="fecharModalRevshare()" class="modal-btn modal-btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detalhes do Afiliado -->
    <div id="detalhesAfiliadoModal" class="modal-details hidden">
        <div class="modal-details-content">
            <div class="modal-details-header">
                <h2 class="modal-details-title">
                    <i class="fas fa-user-circle"></i>
                    <span id="nomeAfiliadoModal">Detalhes do Afiliado</span>
                </h2>
                <button onclick="fecharDetalhesAfiliado()" class="close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-details-body">
                <!-- Loading state -->
                <div id="detalhesLoading" class="loading-container">
                    <div class="loading-spinner"></div>
                    <p>Carregando detalhes...</p>
                </div>
                
                <!-- Content container -->
                <div id="detalhesContent" class="hidden">
                    <!-- Estatísticas rápidas -->
                    <div class="details-stats-grid">
                        <div class="details-stat-card">
                            <div class="details-stat-icon green">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="details-stat-info">
                                <span class="details-stat-value" id="totalIndicados">0</span>
                                <span class="details-stat-label">Total de Indicados</span>
                            </div>
                        </div>
                        
                        <div class="details-stat-card">
                            <div class="details-stat-icon blue">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="details-stat-info">
                                <span class="details-stat-value" id="totalDepositado">R$ 0,00</span>
                                <span class="details-stat-label">Total Depositado</span>
                            </div>
                        </div>
                        
                        <div class="details-stat-card">
                            <div class="details-stat-icon purple">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="details-stat-info">
                                <span class="details-stat-value" id="totalCPA">R$ 0,00</span>
                                <span class="details-stat-label">Total CPA</span>
                            </div>
                        </div>
                        
                        <div class="details-stat-card">
                            <div class="details-stat-icon orange">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="details-stat-info">
                                <span class="details-stat-value" id="totalRevShare">R$ 0,00</span>
                                <span class="details-stat-label">Total RevShare</span>
                            </div>
                        </div>
                        
                        <div class="details-stat-card">
                            <div class="details-stat-icon green">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="details-stat-info">
                                <span class="details-stat-value" id="saldoAtual">R$ 0,00</span>
                                <span class="details-stat-label">Saldo Atual</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabs -->
                    <div class="details-tabs">
                        <button class="tab-btn active" onclick="abrirTab('indicados')">
                            <i class="fas fa-users"></i>
                            Indicados
                        </button>
                        <button class="tab-btn" onclick="abrirTab('historico-cpa')">
                            <i class="fas fa-percentage"></i>
                            Histórico CPA
                        </button>
                        <button class="tab-btn" onclick="abrirTab('historico-revshare')">
                            <i class="fas fa-chart-line"></i>
                            Histórico RevShare
                        </button>
                    </div>
                    
                    <!-- Tab Content - Indicados -->
                    <div id="tab-indicados" class="tab-content active">
                        <div class="tab-header">
                            <h3><i class="fas fa-users"></i> Lista de Indicados</h3>
                        </div>
                        <div id="listaIndicados" class="table-container">
                            <!-- Conteúdo será preenchido via JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Tab Content - Histórico CPA -->
                    <div id="tab-historico-cpa" class="tab-content">
                        <div class="tab-header">
                            <h3><i class="fas fa-percentage"></i> Histórico de Comissões CPA</h3>
                        </div>
                        <div id="historicoCPA" class="table-container">
                            <!-- Conteúdo será preenchido via JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Tab Content - Histórico RevShare -->
                    <div id="tab-historico-revshare" class="tab-content">
                        <div class="tab-header">
                            <h3><i class="fas fa-chart-line"></i> Histórico RevShare</h3>
                        </div>
                        <div id="historicoRevShare" class="table-container">
                            <!-- Conteúdo será preenchido via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<script>
        // Mobile menu toggle with smooth animations
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
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(8px)';
            });
            
            item.addEventListener('mouseleave', function() {
                if (!this.classList.contains('active')) {
                    this.style.transform = 'translateX(0)';
                }
            });
        });
        
        // Modal functions
        function abrirModalComissao(id, comissao, tipo) {
            if (tipo === 'revshare') {
                abrirModalRevshare(id, comissao);
                return;
            }
            
            document.getElementById('afiliadoId').value = id;
            document.getElementById('afiliadoComissao').value = comissao;
            document.getElementById('editarComissaoModal').classList.remove('hidden');
        }
        
        function abrirModalRevshare(id, comissao) {
            document.getElementById('afiliadoIdRevshare').value = id;
            document.getElementById('afiliadoComissaoRevshare').value = comissao;
            document.getElementById('editarRevshareModal').classList.remove('hidden');
        }
        
        function fecharModalComissao() {
            document.getElementById('editarComissaoModal').classList.add('hidden');
        }
        
        function fecharModalRevshare() {
            document.getElementById('editarRevshareModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('editarComissaoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalComissao();
            }
        });
        
        document.getElementById('editarRevshareModal').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalRevshare();
            }
        });

        // Variável global para controlar o modal
        let detalhesModalAberto = false;

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModalComissao();
                fecharModalRevshare();
                if (detalhesModalAberto) {
                    fecharDetalhesAfiliado();
                }
            }
        });

        // Função para abrir detalhes do afiliado
        async function abrirDetalhesAfiliado(afiliadoId) {
            const modal = document.getElementById('detalhesAfiliadoModal');
            const loading = document.getElementById('detalhesLoading');
            const content = document.getElementById('detalhesContent');
            
            // Mostrar modal com loading
            modal.classList.remove('hidden');
            loading.classList.remove('hidden');
            content.classList.add('hidden');
            detalhesModalAberto = true;
            
            try {
                // Fazer requisição AJAX
                const response = await fetch(`?ajax=detalhes_afiliado&afiliado_id=${afiliadoId}`);
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Preencher dados do modal
                preencherDetalhesModal(data);
                
                // Esconder loading e mostrar content
                loading.classList.add('hidden');
                content.classList.remove('hidden');
                
            } catch (error) {
                console.error('Erro ao carregar detalhes:', error);
                Notiflix.Notify.failure('Erro ao carregar detalhes do afiliado: ' + error.message);
                fecharDetalhesAfiliado();
            }
        }

        // Função para preencher o modal com os dados
        function preencherDetalhesModal(data) {
            const { afiliado, indicados, historico_cpa, historico_revshare, estatisticas } = data;
            
            // Atualizar título com nome do afiliado
            document.getElementById('nomeAfiliadoModal').textContent = `Detalhes de ${afiliado.nome}`;
            
            // Atualizar estatísticas
            document.getElementById('totalIndicados').textContent = estatisticas.total_indicados;
            document.getElementById('totalDepositado').textContent = `R$ ${formatarMoeda(estatisticas.total_depositado_indicados)}`;
            document.getElementById('totalCPA').textContent = `R$ ${formatarMoeda(estatisticas.total_comissao_cpa)}`;
            document.getElementById('totalRevShare').textContent = `R$ ${formatarMoeda(estatisticas.total_comissao_revshare)}`;
            document.getElementById('saldoAtual').textContent = `R$ ${formatarMoeda(estatisticas.saldo_atual)}`;
            
            // Preencher tabela de indicados
            preencherTabelaIndicados(indicados);
            
            // Preencher histórico CPA
            preencherHistoricoCPA(historico_cpa);
            
            // Preencher histórico RevShare
            preencherHistoricoRevShare(historico_revshare);
        }

        // Função para preencher tabela de indicados
        function preencherTabelaIndicados(indicados) {
            const container = document.getElementById('listaIndicados');
            
            if (indicados.length === 0) {
                container.innerHTML = `
                    <div class="table-empty">
                        <i class="fas fa-users"></i>
                        <h4>Nenhum indicado encontrado</h4>
                        <p>Este afiliado ainda não possui indicados</p>
                    </div>
                `;
                return;
            }
            
            let html = `
                <table class="details-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Nome</th>
                            <th><i class="fas fa-envelope"></i> Email</th>
                            <th><i class="fas fa-phone"></i> Telefone</th>
                            <th><i class="fas fa-dollar-sign"></i> Total Depositado</th>
                            <th><i class="fas fa-credit-card"></i> Nº Depósitos</th>
                            <th><i class="fas fa-calendar"></i> Cadastro</th>
                            <th><i class="fas fa-info-circle"></i> Status</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            indicados.forEach(indicado => {
                const telefoneFormatado = formatarTelefone(indicado.telefone);
                const whatsappLink = `https://wa.me/55${indicado.telefone.replace(/[^0-9]/g, '')}`;
                const statusClass = indicado.banido == 1 ? 'banido' : 'ativo';
                const statusText = indicado.banido == 1 ? 'Banido' : 'Ativo';
                const dataCadastro = formatarData(indicado.data_cadastro);
                
                html += `
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #22c55e, #16a34a); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.8rem;">
                                    ${indicado.nome.charAt(0).toUpperCase()}
                                </div>
                                ${indicado.nome}
                            </div>
                        </td>
                        <td>${indicado.email}</td>
                        <td>
                            ${telefoneFormatado}
                            <a href="${whatsappLink}" target="_blank" class="whatsapp-table-link">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </td>
                        <td style="font-weight: 600; color: #22c55e;">R$ ${formatarMoeda(indicado.total_depositado)}</td>
                        <td>
                            <span style="background: rgba(34, 197, 94, 0.1); color: #22c55e; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600;">
                                ${indicado.total_depositos}
                            </span>
                        </td>
                        <td style="color: #9ca3af;">${dataCadastro}</td>
                        <td>
                            <span class="status-badge ${statusClass}">${statusText}</span>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
            `;
            
            container.innerHTML = html;
        }

        // Função para preencher histórico CPA
        function preencherHistoricoCPA(historico) {
            const container = document.getElementById('historicoCPA');
            
            if (historico.length === 0) {
                container.innerHTML = `
                    <div class="table-empty">
                        <i class="fas fa-percentage"></i>
                        <h4>Nenhuma comissão CPA encontrada</h4>
                        <p>Ainda não há histórico de comissões CPA para este afiliado</p>
                    </div>
                `;
                return;
            }
            
            let html = `
                <table class="details-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Indicado</th>
                            <th><i class="fas fa-envelope"></i> Email</th>
                            <th><i class="fas fa-dollar-sign"></i> Valor Depósito</th>
                            <th><i class="fas fa-percentage"></i> Comissão</th>
                            <th><i class="fas fa-calendar"></i> Data</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            historico.forEach(item => {
                const dataFormatada = formatarDataHora(item.created_at);
                
                html += `
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="width: 28px; height: 28px; background: linear-gradient(135deg, #9333ea, #7c3aed); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.75rem;">
                                    ${item.indicado_nome.charAt(0).toUpperCase()}
                                </div>
                                ${item.indicado_nome}
                            </div>
                        </td>
                        <td style="color: #9ca3af;">${item.indicado_email}</td>
                        <td style="font-weight: 600; color: #3b82f6;">R$ ${formatarMoeda(item.valor_deposito || 0)}</td>
                        <td style="font-weight: 700; color: #22c55e;">R$ ${formatarMoeda(item.valor_comissao)}</td>
                        <td style="color: #9ca3af;">${dataFormatada}</td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
            `;
            
            container.innerHTML = html;
        }

        // Função para preencher histórico RevShare
        function preencherHistoricoRevShare(historico) {
            const container = document.getElementById('historicoRevShare');
            
            if (historico.length === 0) {
                container.innerHTML = `
                    <div class="table-empty">
                        <i class="fas fa-chart-line"></i>
                        <h4>Nenhum RevShare encontrado</h4>
                        <p>Ainda não há histórico de RevShare para este afiliado</p>
                    </div>
                `;
                return;
            }
            
            let html = `
                <table class="details-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Indicado</th>
                            <th><i class="fas fa-envelope"></i> Email</th>
                            <th><i class="fas fa-gamepad"></i> Jogo</th>
                            <th><i class="fas fa-money-bill-wave"></i> Valor Perdido</th>
                            <th><i class="fas fa-percentage"></i> % RevShare</th>
                            <th><i class="fas fa-chart-line"></i> Valor RevShare</th>
                            <th><i class="fas fa-calendar"></i> Data</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            historico.forEach(item => {
                const dataFormatada = formatarDataHora(item.created_at);
                
                html += `
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="width: 28px; height: 28px; background: linear-gradient(135deg, #f97316, #ea580c); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.75rem;">
                                    ${item.indicado_nome.charAt(0).toUpperCase()}
                                </div>
                                ${item.indicado_nome}
                            </div>
                        </td>
                        <td style="color: #9ca3af;">${item.indicado_email}</td>
                        <td>
                            <span style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600;">
                                ${item.jogo || 'N/A'}
                            </span>
                        </td>
                        <td style="font-weight: 600; color: #ef4444;">R$ ${formatarMoeda(item.valor_perdido || 0)}</td>
                        <td style="color: #f97316; font-weight: 600;">${formatarMoeda(item.percentual_revshare || 0)}%</td>
                        <td style="font-weight: 700; color: #22c55e;">R$ ${formatarMoeda(item.valor_revshare)}</td>
                        <td style="color: #9ca3af;">${dataFormatada}</td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
            `;
            
            container.innerHTML = html;
        }

        // Função para fechar modal de detalhes
        function fecharDetalhesAfiliado() {
            const modal = document.getElementById('detalhesAfiliadoModal');
            modal.classList.add('hidden');
            detalhesModalAberto = false;
            
            // Reset do modal para próxima abertura
            document.getElementById('detalhesLoading').classList.remove('hidden');
            document.getElementById('detalhesContent').classList.add('hidden');
            
            // Reset das tabs
            abrirTab('indicados');
        }

        // Função para alternar entre tabs
        function abrirTab(tabName) {
            // Esconder todos os conteúdos
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remover classe active de todos os botões
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Mostrar conteúdo da tab selecionada
            document.getElementById(`tab-${tabName}`).classList.add('active');
            
            // Adicionar classe active ao botão correspondente
            event.target.classList.add('active');
        }

        // Funções auxiliares para formatação
        function formatarMoeda(valor) {
            const numero = parseFloat(valor) || 0;
            return numero.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatarTelefone(telefone) {
            if (!telefone) return 'N/A';
            
            const apenasNumeros = telefone.replace(/[^0-9]/g, '');
            
            if (apenasNumeros.length === 11) {
                return `(${apenasNumeros.substring(0, 2)}) ${apenasNumeros.substring(2, 7)}-${apenasNumeros.substring(7)}`;
            } else if (apenasNumeros.length === 10) {
                return `(${apenasNumeros.substring(0, 2)}) ${apenasNumeros.substring(2, 6)}-${apenasNumeros.substring(6)}`;
            }
            
            return telefone;
        }

        function formatarData(dataString) {
            if (!dataString) return 'N/A';
            
            const data = new Date(dataString);
            return data.toLocaleDateString('pt-BR');
        }

        function formatarDataHora(dataString) {
            if (!dataString) return 'N/A';
            
            const data = new Date(dataString);
            return data.toLocaleString('pt-BR');
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            console.log('%c🤝 Afiliados carregados!', 'color: #22c55e; font-size: 16px; font-weight: bold;');
            
            // Check if mobile on load
            if (window.innerWidth <= 1024) {
                sidebar.classList.add('hidden');
            }
            
            // Animate cards on load
            const affiliateCards = document.querySelectorAll('.affiliate-card');
            affiliateCards.forEach((card, index) => {
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

            // Event listeners para fechar modal de detalhes
            const detalhesModal = document.getElementById('detalhesAfiliadoModal');
            if (detalhesModal) {
                detalhesModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        fecharDetalhesAfiliado();
                    }
                });
            }
        });
        
        // Smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
    </script>

</body>
</html>