<?php
include '../includes/session.php';
include '../conexao.php';
include '../includes/notiflix.php';

$usuarioId = $_SESSION['usuario_id'];
$admin = ($stmt = $pdo->prepare("SELECT admin FROM usuarios WHERE id = ?"))->execute([$usuarioId]) ? $stmt->fetchColumn() : null;

if( $admin != 1){
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você não é um administrador!'];
    header("Location: /");
    exit;
}

$nome = ($stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?"))->execute([$usuarioId]) ? $stmt->fetchColumn() : null;
$nome = $nome ? explode(' ', $nome)[0] : null;

$total_usuarios = ($stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios"))->execute() ? $stmt->fetchColumn() : 0;
$total_depositos = ($stmt = $pdo->prepare("SELECT COUNT(*) FROM depositos WHERE status = 1"))->execute() ? $stmt->fetchColumn() : 0;
$total_saldo = ($stmt = $pdo->prepare("SELECT SUM(saldo) FROM usuarios"))->execute() ? $stmt->fetchColumn() : 0;

$sql = "
    SELECT 
        u.nome, 
        d.valor, 
        d.updated_at 
    FROM 
        depositos d
    INNER JOIN 
        usuarios u ON d.user_id = u.id
    WHERE 
        d.status = 'PAID'
    ORDER BY 
        d.updated_at DESC
    LIMIT 5
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$depositos_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "
    SELECT 
        u.nome, 
        s.valor, 
        s.updated_at 
    FROM 
        saques s
    INNER JOIN 
        usuarios u ON s.user_id = u.id
    WHERE 
        s.status = 'PENDING'
    ORDER BY 
        s.updated_at DESC
    LIMIT 5
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$saques_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite ?? 'Admin'; ?> - Dashboard Administrativo</title>
    
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
        
        .notification-btn {
            position: relative;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #22c55e;
            padding: 0.75rem;
            border-radius: 12px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .notification-btn:hover {
            background: rgba(34, 197, 94, 0.2);
            transform: scale(1.05);
        }
        
        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: white;
            font-size: 0.6rem;
            font-weight: 600;
            padding: 0.2rem 0.4rem;
            border-radius: 6px;
            min-width: 16px;
            text-align: center;
        }
        
        /* Enhanced Stats Cards */
        .dashboard-content {
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
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(20px);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #22c55e, #16a34a, #22c55e);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -50px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(34, 197, 94, 0.1) 0%, transparent 70%);
            transform: translateY(-50%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stat-card:hover::before,
        .stat-card:hover::after {
            opacity: 1;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            border-color: rgba(34, 197, 94, 0.3);
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.4),
                0 0 40px rgba(34, 197, 94, 0.1);
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        
        .stat-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(34, 197, 94, 0.1) 100%);
            border: 2px solid rgba(34, 197, 94, 0.3);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #22c55e;
            font-size: 1.5rem;
            position: relative;
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.2);
        }
        
        .stat-value {
            font-size: 2.75rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.5rem;
            line-height: 1;
        }
        
        .stat-label {
            color: #a1a1aa;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #22c55e;
        }
        
        /* Enhanced Activity Cards */
        .activity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 2rem;
        }
        
        .activity-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2.5rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
        }
        
        .activity-card::before {
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
        
        .activity-card:hover::before {
            opacity: 1;
        }
        
        .activity-card:hover {
            border-color: rgba(34, 197, 94, 0.2);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .activity-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .activity-icon {
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
        
        .activity-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #ffffff;
        }
        
        .activity-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .activity-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .activity-item:hover::before {
            opacity: 1;
        }
        
        .activity-item:hover {
            background: rgba(34, 197, 94, 0.05);
            border-color: rgba(34, 197, 94, 0.2);
            transform: translateX(4px);
        }
        
        .activity-item:last-child {
            margin-bottom: 0;
        }
        
        .activity-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .activity-name {
            font-weight: 600;
            color: #ffffff;
            font-size: 1rem;
        }
        
        .activity-value {
            font-weight: 700;
            color: #22c55e;
            font-size: 1.1rem;
        }
        
        .activity-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .activity-status {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            color: #22c55e;
        }
        
        .status-dot {
            width: 6px;
            height: 6px;
            background: #22c55e;
            border-radius: 50%;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
            color: #374151;
        }
        
        .empty-state p {
            font-size: 1rem;
            font-weight: 500;
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
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .activity-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }
            
            .dashboard-content {
                padding: 1.5rem;
            }
            
            .welcome-title {
                font-size: 2.25rem;
            }
            
            .stat-card,
            .activity-card {
                padding: 2rem;
            }
            
            .sidebar {
                width: 280px;
            }
        }
        
        @media (max-width: 480px) {
            .welcome-title {
                font-size: 1.875rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .activity-item {
                padding: 1.25rem;
            }
            
            .activity-item-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
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
    <!-- Overlay for mobile -->
    <div class="overlay" id="overlay"></div>
    
    <!-- Advanced Sidebar -->
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
                <a href="index.php" class="nav-item active">
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
        
        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Welcome Section -->
            <section class="welcome-section">
                <h2 class="welcome-title">Olá, <?= htmlspecialchars($nome) ?>!</h2>
                <p class="welcome-subtitle">Aqui está um resumo das principais métricas e atividades do sistema</p>
            </section>
            
            <!-- Enhanced Stats Grid -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i>
                            <span>+12%</span>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($total_usuarios, 0, ',', '.') ?></div>
                    <div class="stat-label">Total de Usuários Ativos</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i>
                            <span>+8%</span>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($total_depositos, 0, ',', '.') ?></div>
                    <div class="stat-label">Depósitos Confirmados</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i>
                            <span>+24%</span>
                        </div>
                    </div>
                    <div class="stat-value">R$ <?= number_format($total_saldo, 2, ',', '.') ?></div>
                    <div class="stat-label">Saldo Total em Carteiras</div>
                </div>
            </section>
            
            <!-- Enhanced Activity Section -->
            <section class="activity-grid">
                <!-- Recent Deposits -->
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-icon">
                            <i class="fas fa-money-bill-transfer"></i>
                        </div>
                        <h3 class="activity-title">Depósitos Recentes</h3>
                    </div>
                    
                    <?php if (!empty($depositos_recentes)): ?>
                        <?php foreach ($depositos_recentes as $deposito): ?>
                            <div class="activity-item">
                                <div class="activity-item-header">
                                    <span class="activity-name"><?= htmlspecialchars($deposito['nome']) ?></span>
                                    <span class="activity-value">R$ <?= number_format($deposito['valor'], 2, ',', '.') ?></span>
                                </div>
                                <div class="activity-meta">
                                    <div class="activity-status">
                                        <div class="status-dot"></div>
                                        <span>Confirmado</span>
                                    </div>
                                    <span><?= date('d/m/Y H:i', strtotime($deposito['updated_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Nenhum depósito confirmado recentemente</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pending Withdrawals -->
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h3 class="activity-title">Saques Pendentes</h3>
                    </div>
                    
                    <?php if (!empty($saques_recentes)): ?>
                        <?php foreach ($saques_recentes as $saque): ?>
                            <div class="activity-item">
                                <div class="activity-item-header">
                                    <span class="activity-name"><?= htmlspecialchars($saque['nome']) ?></span>
                                    <span class="activity-value">R$ <?= number_format($saque['valor'], 2, ',', '.') ?></span>
                                </div>
                                <div class="activity-meta">
                                    <div class="activity-status" style="background: rgba(251, 191, 36, 0.1); border-color: rgba(251, 191, 36, 0.2); color: #f59e0b;">
                                        <div class="status-dot" style="background: #f59e0b;"></div>
                                        <span>Pendente</span>
                                    </div>
                                    <span><?= date('d/m/Y H:i', strtotime($saque['updated_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <p>Nenhum saque pendente no momento</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
    
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
                overlay.classList.remove('active');
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
        
        // Smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Add loading animation on page load
        document.addEventListener('DOMContentLoaded', () => {
            console.log('%c⚡ Dashboard Admin Pro carregado!', 'color: #22c55e; font-size: 16px; font-weight: bold;');
            
            // Check if mobile on load
            if (window.innerWidth <= 1024) {
                sidebar.classList.add('hidden');
            }
            
            // Animate stats cards on load
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });
            
            // Animate activity cards
            const activityCards = document.querySelectorAll('.activity-card');
            activityCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, (statCards.length * 150) + (index * 200));
            });
        });
        
        // Add click ripple effect to cards
        document.querySelectorAll('.stat-card, .activity-card').forEach(card => {
            card.addEventListener('click', function(e) {
                const ripple = document.createElement('div');
                const rect = this.getBoundingClientRect();
                const size = 60;
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.style.position = 'absolute';
                ripple.style.background = 'rgba(34, 197, 94, 0.3)';
                ripple.style.borderRadius = '50%';
                ripple.style.transform = 'scale(0)';
                ripple.style.animation = 'ripple 0.6s linear';
                ripple.style.pointerEvents = 'none';
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
        
        // Add CSS animation for ripple effect
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>