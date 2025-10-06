<?php
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

if (isset($_POST['atualizar_saldo'])) {
    $id = $_POST['id'];
    $saldo = str_replace(',', '.', $_POST['saldo']);
    
    $stmt = $pdo->prepare("UPDATE usuarios SET saldo = ? WHERE id = ?");
    if ($stmt->execute([$saldo, $id])) {
        $_SESSION['success'] = 'Saldo atualizado com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao atualizar saldo!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

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

$nome = ($stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?"))->execute([$usuarioId]) ? $stmt->fetchColumn() : null;
$nome = $nome ? explode(' ', $nome)[0] : null;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT u.*, ui.email as email_indicador FROM usuarios u LEFT JOIN usuarios ui ON u.indicacao = ui.id WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (u.nome LIKE :search OR u.email LIKE :search OR u.telefone LIKE :search)";
}

$query .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($query);

if (!empty($search)) {
    $searchTerm = "%$search%";
    $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
}

$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count stats
$total_usuarios = count($usuarios);
$usuarios_ativos = array_filter($usuarios, function($u) { return $u['banido'] == 0; });
$influencers = array_filter($usuarios, function($u) { return $u['influencer'] == 1; });
$total_saldo = array_sum(array_column($usuarios, 'saldo'));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite ?? 'Admin'; ?> - Gerenciar Usuários</title>
    
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
        
        /* Advanced Sidebar Styles */
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
        
        /* Sidebar Footer */
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
            padding: 1rem 1rem 1rem 3rem;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: rgba(34, 197, 94, 0.5);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
            background: rgba(0, 0, 0, 0.5);
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
        
        /* User Cards */
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
            gap: 1.5rem;
        }
        
        .user-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
        }
        
        .user-card::before {
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
        
        .user-card:hover::before {
            opacity: 1;
        }
        
        .user-card:hover {
            transform: translateY(-4px);
            border-color: rgba(34, 197, 94, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .user-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }
        
        .user-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge.admin {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
        }
        
        .badge.influencer {
            background: linear-gradient(135deg, #ec4899, #db2777);
            color: white;
            box-shadow: 0 2px 8px rgba(236, 72, 153, 0.3);
        }
        
        .badge.banned {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }
        
        .user-info {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #e5e7eb;
            font-size: 0.9rem;
            padding: 0.5rem 0;
        }
        
        .info-icon {
            width: 20px;
            color: #22c55e;
            text-align: center;
        }
        
        .whatsapp-link {
            color: #25d366;
            margin-left: 0.5rem;
            transition: all 0.3s ease;
            padding: 0.25rem;
            border-radius: 6px;
        }
        
        .whatsapp-link:hover {
            color: #128c7e;
            background: rgba(37, 211, 102, 0.1);
        }
        
        /* Action Buttons */
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .action-btn {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            border: none;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        
        .btn-balance {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .btn-balance:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }
        
        .btn-ban {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn-ban:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }
        
        .btn-unban {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        
        .btn-unban:hover {
            background: linear-gradient(135deg, #16a34a, #15803d);
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.4);
        }
        
        .btn-influencer {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        
        .btn-influencer:hover {
            background: linear-gradient(135deg, #16a34a, #15803d);
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.4);
        }
        
        .btn-remove-inf {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .btn-remove-inf:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
        }
        
        .user-meta {
            color: #9ca3af;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-meta i {
            color: #6b7280;
        }
        
        /* Modal Styles */
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(8px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.98) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 2.5rem;
            width: 90%;
            max-width: 500px;
            backdrop-filter: blur(20px);
            box-shadow: 
                0 25px 80px rgba(0, 0, 0, 0.8),
                0 0 0 1px rgba(34, 197, 94, 0.1);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        
        .modal.active .modal-content {
            transform: scale(1);
        }
        
        .modal-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .modal-title i {
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
        
        .modal-label {
            color: #e5e7eb;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-label i {
            color: #22c55e;
        }
        
        .modal-input {
            width: 100%;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }
        
        .modal-input:focus {
            outline: none;
            border-color: rgba(34, 197, 94, 0.5);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
            background: rgba(0, 0, 0, 0.6);
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
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        
        .modal-btn-primary {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        
        .modal-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
        }
        
        .modal-btn-secondary {
            background: rgba(107, 114, 128, 0.3);
            color: #e5e7eb;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-btn-secondary:hover {
            background: rgba(107, 114, 128, 0.4);
            transform: translateY(-2px);
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
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            
            .users-grid {
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
            
            .user-card {
                padding: 1.5rem;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                margin: 1rem;
                padding: 2rem;
            }
            
            .modal-buttons {
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
            
            .user-info {
                grid-template-columns: 1fr;
            }
            
            .user-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .sidebar {
                width: 260px;
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
                <a href="usuarios.php" class="nav-item active">
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
                <h2 class="welcome-title">Usuários do Sistema</h2>
                <p class="welcome-subtitle">Gerencie todos os usuários cadastrados na plataforma</p>
            </section>
            
            <!-- Stats Grid -->
            <section class="stats-grid">
                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value"><?= number_format($total_usuarios, 0, ',', '.') ?></div>
                    <div class="mini-stat-label">Total de Usuários</div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value"><?= number_format(count($usuarios_ativos), 0, ',', '.') ?></div>
                    <div class="mini-stat-label">Usuários Ativos</div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value"><?= number_format(count($influencers), 0, ',', '.') ?></div>
                    <div class="mini-stat-label">Influencers</div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value">R$ <?= number_format($total_saldo, 2, ',', '.') ?></div>
                    <div class="mini-stat-label">Saldo Total</div>
                </div>
            </section>
            
            <!-- Search Section -->
            <section class="search-section">
                <div class="search-header">
                    <div class="search-icon-container">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="search-title">Buscar Usuários</h3>
                </div>
                
                <form method="GET">
                    <div class="search-container">
                        <i class="fa-solid fa-search search-icon"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               class="search-input" 
                               placeholder="Pesquisar por nome, email ou telefone..." 
                               onchange="this.form.submit()">
                    </div>
                </form>
            </section>
            
            <!-- Users Section -->
            <section>
                <?php if (empty($usuarios)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>Nenhum usuário encontrado</h3>
                        <p>Tente ajustar os filtros de busca ou verificar se há usuários cadastrados</p>
                    </div>
                <?php else: ?>
                    <div class="users-grid">
                        <?php foreach ($usuarios as $usuario): ?>
                            <?php 
                            $telefone = $usuario['telefone'];
                            if (strlen($telefone) == 11) {
                                $telefoneFormatado = '('.substr($telefone, 0, 2).') '.substr($telefone, 2, 5).'-'.substr($telefone, 7);
                            } else {
                                $telefoneFormatado = $telefone;
                            }
                            
                            $whatsappLink = 'https://wa.me/55'.preg_replace('/[^0-9]/', '', $usuario['telefone']);
                            ?>
                            
                            <div class="user-card">
                                <div class="user-header">
                                    <div>
                                        <h3 class="user-name"><?= htmlspecialchars($usuario['nome']) ?></h3>
                                        <div class="user-badges">
                                            <?php if ($usuario['admin'] == 1): ?>
                                                <span class="badge admin">Admin</span>
                                            <?php endif; ?>
                                            <?php if ($usuario['influencer'] == 1): ?>
                                                <span class="badge influencer">Influencer</span>
                                            <?php endif; ?>
                                            <?php if ($usuario['banido'] == 1): ?>
                                                <span class="badge banned">Banido</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="user-info">
                                    <div class="info-item">
                                        <i class="fa-solid fa-envelope info-icon"></i>
                                        <span><?= htmlspecialchars($usuario['email']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fa-solid fa-phone info-icon"></i>
                                        <span><?= $telefoneFormatado ?></span>
                                        <a href="<?= $whatsappLink ?>" target="_blank" class="whatsapp-link">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                    <div class="info-item">
                                        <i class="fa-solid fa-wallet info-icon"></i>
                                        <span>R$ <?= number_format($usuario['saldo'], 2, ',', '.') ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fa-solid fa-user-plus info-icon"></i>
                                        <span>Indicado por: <?= $usuario['email_indicador'] ? htmlspecialchars($usuario['email_indicador']) : 'Ninguém' ?></span>
                                    </div>
                                </div>
                                
                                <div class="action-buttons">
                                    <button onclick="abrirModalEditarSaldo('<?= $usuario['id'] ?>', '<?= number_format($usuario['saldo'], 2, '.', '') ?>')" 
                                            class="action-btn btn-balance">
                                        <i class="fa-solid fa-edit"></i>
                                        Editar Saldo
                                    </button>
                                    
                                    <a href="?toggle_banido&id=<?= $usuario['id'] ?>" 
                                       class="action-btn <?= $usuario['banido'] ? 'btn-unban' : 'btn-ban' ?>">
                                        <i class="fa-solid fa-<?= $usuario['banido'] ? 'user-check' : 'user-slash' ?>"></i>
                                        <?= $usuario['banido'] ? 'Desbanir' : 'Banir' ?>
                                    </a>
                                    
                                    <a href="?toggle_influencer&id=<?= $usuario['id'] ?>" 
                                       class="action-btn <?= $usuario['influencer'] ? 'btn-remove-inf' : 'btn-influencer' ?>">
                                        <i class="fa-solid fa-<?= $usuario['influencer'] ? 'user-minus' : 'star' ?>"></i>
                                        <?= $usuario['influencer'] ? 'Remover Inf.' : 'Tornar Inf.' ?>
                                    </a>
                                </div>
                                
                                <div class="user-meta">
                                    <i class="fa-solid fa-calendar"></i>
                                    <span>Cadastrado em: <?= date('d/m/Y H:i', strtotime($usuario['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Modal Editar Saldo -->
    <div id="editarSaldoModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-title">
                <i class="fa-solid fa-wallet"></i>
                Editar Saldo do Usuário
            </h2>
            <form method="POST" id="formEditarSaldo">
                <input type="hidden" name="id" id="usuarioId">
                <div>
                    <label class="modal-label">
                        <i class="fa-solid fa-dollar-sign"></i>
                        Novo Saldo (R$)
                    </label>
                    <input type="text" name="saldo" id="usuarioSaldo" class="modal-input" 
                           placeholder="0,00" required>
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="atualizar_saldo" class="modal-btn modal-btn-primary">
                        <i class="fa-solid fa-save"></i>
                        Salvar Alterações
                    </button>
                    <button type="button" onclick="fecharModal()" class="modal-btn modal-btn-secondary">
                        <i class="fa-solid fa-times"></i>
                        Cancelar
                    </button>
                </div>
            </form>
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
        function abrirModalEditarSaldo(id, saldo) {
            document.getElementById('usuarioId').value = id;
            document.getElementById('usuarioSaldo').value = saldo;
            document.getElementById('editarSaldoModal').classList.add('active');
        }
        
        function fecharModal() {
            document.getElementById('editarSaldoModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('editarSaldoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModal();
            }
        });
        
        // Smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            console.log('%c👥 Gerenciamento de Usuários carregado!', 'color: #22c55e; font-size: 16px; font-weight: bold;');
            
            // Check if mobile on load
            if (window.innerWidth <= 1024) {
                sidebar.classList.add('hidden');
            }
            
            // Animate cards on load
            const userCards = document.querySelectorAll('.user-card');
            userCards.forEach((card, index) => {
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