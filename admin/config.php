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

$config = $pdo->query("SELECT * FROM config LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (isset($_POST['salvar_config'])) {
    $nome_site = $_POST['nome_site'];
    $deposito_min = str_replace(',', '.', $_POST['deposito_min']);
    $saque_min = str_replace(',', '.', $_POST['saque_min']);
    $cpa_padrao = str_replace(',', '.', $_POST['cpa_padrao']);
    $revshare_padrao = str_replace(',', '.', $_POST['revshare_padrao']); // Novo campo
    
    $logo = $config['logo'];
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $uploadDir = '../assets/upload/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $newName = uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newName;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                if ($config['logo'] && file_exists('../' . $config['logo'])) {
                    unlink('../' . $config['logo']);
                }
                $logo = '/assets/upload/' . $newName;
            } else {
                $_SESSION['failure'] = 'Erro ao fazer upload da logo!';
                header('Location: '.$_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['failure'] = 'Formato de arquivo inválido! Use apenas JPG ou PNG.';
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        }
    }
    
    // Query atualizada para incluir revshare_padrao
    $stmt = $pdo->prepare("UPDATE config SET nome_site = ?, logo = ?, deposito_min = ?, saque_min = ?, cpa_padrao = ?, revshare_padrao = ?");
    if ($stmt->execute([$nome_site, $logo, $deposito_min, $saque_min, $cpa_padrao, $revshare_padrao])) {
        $_SESSION['success'] = 'Configurações atualizadas com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao atualizar as configurações!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

$nome = ($stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?"))->execute([$usuarioId]) ? $stmt->fetchColumn() : null;
$nome = $nome ? explode(' ', $nome)[0] : null;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite ?? 'Admin'; ?> - Configurações</title>
    
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
        
        /* Form Container */
        .form-container {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 3rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .form-container::before {
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
        
        .form-container:hover::before {
            opacity: 1;
        }
        
        .form-container:hover {
            transform: translateY(-4px);
            border-color: rgba(34, 197, 94, 0.2);
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5);
        }
        
        .form-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-title i {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(34, 197, 94, 0.1) 100%);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #22c55e;
            font-size: 1.25rem;
        }
        
        /* Form Sections */
        .form-section {
            margin-bottom: 3rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .section-title i {
            color: #22c55e;
            font-size: 1.125rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .form-group {
            position: relative;
        }
        
        .form-label {
            display: block;
            color: #e5e7eb;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-label i {
            color: #22c55e;
            font-size: 0.875rem;
        }
        
        .form-input {
            width: 100%;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            color: white;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .form-input.percentage {
            padding-right: 2.5rem;
        }
        
        .form-input:focus {
            outline: none;
            border-color: rgba(34, 197, 94, 0.5);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
            background: rgba(0, 0, 0, 0.6);
        }
        
        .form-input::placeholder {
            color: #6b7280;
        }
        
        .percentage-symbol {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-weight: 600;
            pointer-events: none;
        }
        
        .input-container {
            position: relative;
        }
        
        /* File Input */
        .file-input-container {
            position: relative;
        }
        
        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
            background: rgba(0, 0, 0, 0.4);
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            color: #9ca3af;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .file-input-label:hover {
            border-color: rgba(34, 197, 94, 0.4);
            background: rgba(34, 197, 94, 0.05);
            color: #22c55e;
        }
        
        .file-input-label.has-file {
            border-color: rgba(34, 197, 94, 0.4);
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }
        
        /* Current Logo */
        .current-logo {
            margin-top: 1rem;
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .current-logo p {
            color: #9ca3af;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .current-logo img {
            max-height: 80px;
            max-width: 100%;
            object-fit: contain;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            padding: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Submit Button */
        .submit-button {
            width: 100%;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            padding: 1.25rem 2rem;
            border-radius: 16px;
            font-size: 1.125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 3rem;
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
        }
        
        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(34, 197, 94, 0.4);
        }
        
        .submit-button:active {
            transform: translateY(0);
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
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
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
            
            .form-container {
                padding: 2rem;
            }
            
            .form-title {
                font-size: 1.5rem;
            }
            
            .sidebar {
                width: 280px;
            }
        }
        
        @media (max-width: 480px) {
            .welcome-title {
                font-size: 1.875rem;
            }
            
            .form-container {
                padding: 1.5rem;
            }
            
            .form-grid {
                gap: 1rem;
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
                <a href="saques.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="nav-text">Saques</div>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Sistema</div>
                <a href="config.php" class="nav-item active">
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
                <h2 class="welcome-title">Configurações do Sistema</h2>
                <p class="welcome-subtitle">Gerencie as configurações básicas e personalize sua plataforma</p>
            </section>
            
            <!-- Form Container -->
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <h2 class="form-title">
                        <i class="fas fa-cogs"></i>
                        Configurações Gerais
                    </h2>
                    
                    <!-- Site Configuration Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-globe"></i>
                            Informações do Site
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-tag"></i>
                                    Nome do Site
                                </label>
                                <input type="text" name="nome_site" value="<?= htmlspecialchars($config['nome_site'] ?? '') ?>" class="form-input" placeholder="Digite o nome do seu site" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-image"></i>
                                    Logo do Site
                                </label>
                                <div class="file-input-container">
                                    <input type="file" name="logo" accept="image/jpeg, image/png" id="logo-upload" class="file-input">
                                    <label for="logo-upload" class="file-input-label" id="file-label">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span>Clique para enviar logo (JPG, PNG)</span>
                                    </label>
                                </div>
                                
                                <?php if (!empty($config['logo'])): ?>
                                    <div class="current-logo">
                                        <p><i class="fas fa-image"></i> Logo atual:</p>
                                        <img src="<?= htmlspecialchars($config['logo']) ?>" alt="Logo atual">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Financial Configuration Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-dollar-sign"></i>
                            Configurações Financeiras
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-plus-circle"></i>
                                    Depósito Mínimo (R$)
                                </label>
                                <input type="text" name="deposito_min" value="<?= htmlspecialchars($config['deposito_min'] ?? '0') ?>" class="form-input" placeholder="Ex: 10,00" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-minus-circle"></i>
                                    Saque Mínimo (R$)
                                </label>
                                <input type="text" name="saque_min" value="<?= htmlspecialchars($config['saque_min'] ?? '0') ?>" class="form-input" placeholder="Ex: 20,00" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Affiliate Configuration Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-handshake"></i>
                            Configurações de Afiliados
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user-plus"></i>
                                    CPA Padrão (R$)
                                </label>
                                <input type="text" name="cpa_padrao" value="<?= htmlspecialchars($config['cpa_padrao'] ?? '0') ?>" class="form-input" placeholder="Ex: 5,00" required>
                                <p style="color: #6b7280; font-size: 0.8rem; margin-top: 0.5rem;">
                                    <i class="fas fa-info-circle"></i> 
                                    Comissão fixa paga por cada novo cadastro indicado
                                </p>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-chart-line"></i>
                                    RevShare Padrão (%)
                                </label>
                                <div class="input-container">
                                    <input type="text" name="revshare_padrao" value="<?= htmlspecialchars($config['revshare_padrao'] ?? '0') ?>" class="form-input percentage" placeholder="Ex: 10,00" required>
                                    <span class="percentage-symbol">%</span>
                                </div>
                                <p style="color: #6b7280; font-size: 0.8rem; margin-top: 0.5rem;">
                                    <i class="fas fa-info-circle"></i> 
                                    Percentual sobre as perdas dos usuários indicados
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="salvar_config" class="submit-button">
                        <i class="fas fa-save"></i>
                        Salvar Configurações
                    </button>
                </form>
            </div>
            
        </div>
    </main>
    
    <script>
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
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(8px)';
            });
            
            item.addEventListener('mouseleave', function() {
                if (!this.classList.contains('active')) {
                    this.style.transform = 'translateX(0)';
                }
            });
        });
        
        // File input enhancement
        document.getElementById('logo-upload').addEventListener('change', function(e) {
            const label = document.getElementById('file-label');
            const fileName = e.target.files[0]?.name;
            
            if (fileName) {
                label.innerHTML = `
                    <i class="fas fa-check-circle"></i>
                    <span>${fileName}</span>
                `;
                label.classList.add('has-file');
            } else {
                label.innerHTML = `
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Clique para enviar logo (JPG, PNG)</span>
                `;
                label.classList.remove('has-file');
            }
        });
        
        // Input formatting for currency fields
        function formatCurrency(input) {
            let value = input.value.replace(/\D/g, '');
            if (value === '') return;
            value = (value / 100).toFixed(2) + '';
            value = value.replace(".", ",");
            value = value.replace(/(\d)(\d{3}),/, "$1.$2,");
            input.value = value;
        }
        
        // Input formatting for percentage fields
        function formatPercentage(input) {
            let value = input.value.replace(/[^\d,]/g, '');
            if (value.includes(',')) {
                let parts = value.split(',');
                if (parts[1] && parts[1].length > 2) {
                    parts[1] = parts[1].substring(0, 2);
                }
                value = parts.join(',');
            }
            input.value = value;
        }
        
        // Apply currency formatting to financial inputs
        document.querySelectorAll('input[name="deposito_min"], input[name="saque_min"], input[name="cpa_padrao"]').forEach(input => {
            input.addEventListener('input', function() {
                formatCurrency(this);
            });
        });
        
        // Apply percentage formatting to revshare input
        document.querySelector('input[name="revshare_padrao"]').addEventListener('input', function() {
            formatPercentage(this);
        });
        
        // Smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            console.log('%c⚙️ Configurações do Sistema carregadas!', 'color: #22c55e; font-size: 16px; font-weight: bold;');
            
            // Check if mobile on load
            if (window.innerWidth <= 1024) {
                sidebar.classList.add('hidden');
            }
            
            // Animate form container on load
            const formContainer = document.querySelector('.form-container');
            formContainer.style.opacity = '0';
            formContainer.style.transform = 'translateY(20px)';
            setTimeout(() => {
                formContainer.style.transition = 'all 0.6s ease';
                formContainer.style.opacity = '1';
                formContainer.style.transform = 'translateY(0)';
            }, 300);
        });
    </script>
</body>
</html>