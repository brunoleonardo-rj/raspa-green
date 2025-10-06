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

$banners = $pdo->query("SELECT * FROM banners ORDER BY ordem ASC")->fetchAll(PDO::FETCH_ASSOC);

// Editar banner
if (isset($_POST['editar_banner'])) {
    $banner_id = $_POST['banner_id'];
    $banner_atual = $pdo->prepare("SELECT banner_img FROM banners WHERE id = ?");
    $banner_atual->execute([$banner_id]);
    $banner_data = $banner_atual->fetch();
    
    if ($banner_data) {
        $nova_imagem = $banner_data['banner_img'];
        
        if (isset($_FILES['nova_banner_img']) && $_FILES['nova_banner_img']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $ext = pathinfo($_FILES['nova_banner_img']['name'], PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed)) {
                $uploadDir = '../assets/banners/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $newName = 'banner_' . uniqid() . '.' . $ext;
                $uploadPath = $uploadDir . $newName;
                
                if (move_uploaded_file($_FILES['nova_banner_img']['tmp_name'], $uploadPath)) {
                    if (file_exists('../' . $banner_data['banner_img'])) {
                        unlink('../' . $banner_data['banner_img']);
                    }
                    $nova_imagem = '/assets/banners/' . $newName;
                } else {
                    $_SESSION['failure'] = 'Erro ao fazer upload da nova imagem!';
                    header('Location: '.$_SERVER['PHP_SELF']);
                    exit;
                }
            } else {
                $_SESSION['failure'] = 'Formato de arquivo inválido! Use apenas JPG ou PNG.';
                header('Location: '.$_SERVER['PHP_SELF']);
                exit;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE banners SET banner_img = ? WHERE id = ?");
        if ($stmt->execute([$nova_imagem, $banner_id])) {
            $_SESSION['success'] = 'Banner atualizado com sucesso!';
        } else {
            $_SESSION['failure'] = 'Erro ao atualizar banner!';
        }
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Adicionar banner
if (isset($_POST['adicionar_banner'])) {
    if (isset($_FILES['banner_img']) && $_FILES['banner_img']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = pathinfo($_FILES['banner_img']['name'], PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $uploadDir = '../assets/banners/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $newName = 'banner_' . uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newName;
            
            if (move_uploaded_file($_FILES['banner_img']['tmp_name'], $uploadPath)) {
                $ordem = $pdo->query("SELECT COALESCE(MAX(ordem), 0) + 1 FROM banners")->fetchColumn();
                $stmt = $pdo->prepare("INSERT INTO banners (banner_img, ativo, ordem) VALUES (?, 1, ?)");
                
                if ($stmt->execute(['/assets/banners/' . $newName, $ordem])) {
                    $_SESSION['success'] = 'Banner adicionado com sucesso!';
                } else {
                    $_SESSION['failure'] = 'Erro ao salvar banner no banco!';
                }
            } else {
                $_SESSION['failure'] = 'Erro ao fazer upload do banner!';
            }
        } else {
            $_SESSION['failure'] = 'Formato de arquivo inválido! Use apenas JPG ou PNG.';
        }
    } else {
        $_SESSION['failure'] = 'Nenhum arquivo selecionado!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Deletar banner
if (isset($_POST['deletar_banner'])) {
    $banner_id = $_POST['banner_id'];
    $banner = $pdo->prepare("SELECT banner_img FROM banners WHERE id = ?");
    $banner->execute([$banner_id]);
    $banner_data = $banner->fetch();
    
    if ($banner_data) {
        $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
        if ($stmt->execute([$banner_id])) {
            if (file_exists('../' . $banner_data['banner_img'])) {
                unlink('../' . $banner_data['banner_img']);
            }
            $_SESSION['success'] = 'Banner deletado com sucesso!';
        } else {
            $_SESSION['failure'] = 'Erro ao deletar banner!';
        }
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Atualizar status do banner
if (isset($_POST['toggle_banner'])) {
    $banner_id = $_POST['banner_id'];
    $novo_status = $_POST['novo_status'];
    
    $stmt = $pdo->prepare("UPDATE banners SET ativo = ? WHERE id = ?");
    if ($stmt->execute([$novo_status, $banner_id])) {
        $_SESSION['success'] = 'Status do banner atualizado!';
    } else {
        $_SESSION['failure'] = 'Erro ao atualizar status!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Atualizar ordem dos banners
if (isset($_POST['atualizar_ordem'])) {
    $ordens = $_POST['ordem'];
    
    foreach ($ordens as $id => $ordem) {
        $stmt = $pdo->prepare("UPDATE banners SET ordem = ? WHERE id = ?");
        $stmt->execute([$ordem, $id]);
    }
    
    $_SESSION['success'] = 'Ordem dos banners atualizada!';
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
    <title>Dashboard - Gestão de Banners</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
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
            box-shadow: 0 0 50px rgba(34, 197, 94, 0.1), inset 1px 0 0 rgba(255, 255, 255, 0.05);
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
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3), 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
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
        
        .main-content {
            margin-left: 320px;
            min-height: 100vh;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: 
                radial-gradient(circle at 10% 20%, rgba(34, 197, 94, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.02) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(59, 130, 246, 0.01) 0%, transparent 50%);
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
        
        .content-container {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 3rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .content-title {
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
        
        .content-title i {
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
        
        .upload-section {
            margin-bottom: 3rem;
            padding: 2rem;
            background: rgba(0, 0, 0, 0.3);
            border: 2px dashed rgba(34, 197, 94, 0.3);
            border-radius: 16px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-section:hover {
            background: rgba(34, 197, 94, 0.05);
            border-color: rgba(34, 197, 94, 0.5);
            transform: translateY(-2px);
        }
        
        .upload-icon {
            width: 80px;
            height: 80px;
            background: rgba(34, 197, 94, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #22c55e;
            margin: 0 auto 1.5rem;
            transition: all 0.3s ease;
        }
        
        .upload-text {
            color: #e5e7eb;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .upload-subtitle {
            color: #9ca3af;
            font-size: 0.95rem;
        }
        
        .file-input {
            display: none;
        }
        
        .banners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .banner-card {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .banner-card:hover {
            border-color: rgba(34, 197, 94, 0.3);
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
        }
        
        .banner-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .banner-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        
        .banner-status {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-badge.ativo {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .status-badge.inativo {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .ordem-badge {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .banner-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-action {
            flex: 1;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .btn-edit:hover {
            background: rgba(34, 197, 94, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.2);
        }
        
        .btn-toggle {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .btn-toggle:hover {
            background: rgba(59, 130, 246, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.2);
        }
        
        .btn-delete {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.2);
        }
        
        .order-section {
            margin-top: 3rem;
            padding: 2rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
        }
        
        .order-title {
            color: #ffffff;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .order-title i {
            color: #22c55e;
        }
        
        .order-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .order-input {
            width: 70px;
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 0.75rem;
            color: white;
            text-align: center;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .order-input:focus {
            outline: none;
            border-color: rgba(34, 197, 94, 0.5);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }
        
        .order-label {
            color: #e5e7eb;
            font-size: 0.95rem;
            font-weight: 500;
            flex: 1;
        }
        
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
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
        }
        
        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(34, 197, 94, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }
        
        .empty-icon {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #4b5563;
            margin: 0 auto 2rem;
        }
        
        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #9ca3af;
            margin-bottom: 0.75rem;
        }
        
        .empty-subtitle {
            font-size: 1rem;
            color: #6b7280;
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(8px);
        }
        
        .modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.98) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6);
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .modal-title i {
            color: #22c55e;
        }
        
        .modal-close {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .modal-close:hover {
            background: rgba(239, 68, 68, 0.3);
            transform: scale(1.05);
        }
        
        .modal-body {
            margin-bottom: 2rem;
        }
        
        .current-image {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .file-upload-area {
            border: 2px dashed rgba(34, 197, 94, 0.3);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            background: rgba(34, 197, 94, 0.05);
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .file-upload-area:hover {
            border-color: rgba(34, 197, 94, 0.5);
            background: rgba(34, 197, 94, 0.1);
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn-modal {
            flex: 1;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
        }
        
        .btn-cancel {
            background: rgba(107, 114, 128, 0.2);
            color: #9ca3af;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }
        
        .btn-cancel:hover {
            background: rgba(107, 114, 128, 0.3);
            color: #ffffff;
        }
        
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
            
            .banners-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .order-grid {
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
            
            .content-container {
                padding: 2rem;
            }
            
            .content-title {
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
            
            .content-container {
                padding: 1.5rem;
            }
            
            .sidebar {
                width: 260px;
            }
            
            .banners-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .banner-actions {
                flex-direction: column;
                gap: 0.5rem;
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

    <div class="overlay" id="overlay"></div>
    
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
                <a href="config.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-cogs"></i></div>
                    <div class="nav-text">Configurações</div>
                </a>
                <a href="gateway.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="nav-text">Gateway</div>
                </a>
                <a href="banners.php" class="nav-item active">
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
    
    <main class="main-content" id="mainContent">
        <header class="header">
            <div class="header-content">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="color: #a1a1aa; font-size: 0.9rem; display: none;">Bem-vindo, <?= htmlspecialchars($nome) ?></span>
                    <div class="user-avatar">
                        <?= strtoupper(substr($nome, 0, 1)) ?>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="page-content">
            <section class="welcome-section">
                <h2 class="welcome-title">Gestão de Banners</h2>
                <p class="welcome-subtitle">Gerencie os banners exibidos na página principal da sua plataforma</p>
            </section>
            
            <div class="content-container">
                <h2 class="content-title">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Adicionar Novo Banner
                </h2>
                
                <div class="upload-section" onclick="document.getElementById('banner-upload').click()">
                    <div class="upload-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="upload-text">Clique para adicionar um novo banner</div>
                    <div class="upload-subtitle">Formatos aceitos: JPG, PNG (máx. 5MB)</div>
                </div>
                
                <form method="POST" enctype="multipart/form-data" style="display: none;">
                    <input type="file" name="banner_img" accept="image/jpeg,image/png,image/jpg" id="banner-upload">
                    <input type="hidden" name="adicionar_banner" value="1">
                </form>
            </div>
            
            <div class="content-container">
                <h2 class="content-title">
                    <i class="fas fa-images"></i>
                    Banners Cadastrados
                    <span style="background: rgba(34, 197, 94, 0.2); color: #22c55e; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.8rem; margin-left: auto;">
                        <?= count($banners) ?> banner<?= count($banners) != 1 ? 's' : '' ?>
                    </span>
                </h2>
                
                <?php if (empty($banners)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-images"></i>
                        </div>
                        <div class="empty-title">Nenhum banner cadastrado</div>
                        <div class="empty-subtitle">Adicione seu primeiro banner usando a seção acima</div>
                    </div>
                <?php else: ?>
                    <div class="banners-grid">
                        <?php foreach ($banners as $banner): ?>
                            <div class="banner-card">
                                <img src="<?= htmlspecialchars($banner['banner_img']) ?>" 
                                     alt="Banner #<?= $banner['id'] ?>" 
                                     class="banner-image"
                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzNTAiIGhlaWdodD0iMTgwIiB2aWV3Qm94PSIwIDAgMzUwIDE4MCI+PHJlY3Qgd2lkdGg9IjM1MCIgaGVpZ2h0PSIxODAiIGZpbGw9IiMzNzQxNTEiLz48dGV4dCB4PSIxNzUiIHk9IjkwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjZDFkNWRiIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTYiPkltYWdlbSBuw6NvIGVuY29udHJhZGE8L3RleHQ+PC9zdmc+'"
                                     loading="lazy">
                                
                                <div class="banner-info">
                                    <div class="banner-status">
                                        <span class="status-badge <?= $banner['ativo'] ? 'ativo' : 'inativo' ?>">
                                            <i class="fas fa-<?= $banner['ativo'] ? 'check-circle' : 'times-circle' ?>"></i>
                                            <?= $banner['ativo'] ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </div>
                                    <span class="ordem-badge">
                                        <i class="fas fa-sort"></i>
                                        Ordem: <?= $banner['ordem'] ?>
                                    </span>
                                </div>
                                
                                <div class="banner-actions">
                                    <button type="button" class="btn-action btn-edit" onclick="openEditModal(<?= $banner['id'] ?>, '<?= htmlspecialchars($banner['banner_img']) ?>')">
                                        <i class="fas fa-edit"></i>
                                        Editar
                                    </button>
                                    
                                    <form method="POST" style="flex: 1;">
                                        <input type="hidden" name="banner_id" value="<?= $banner['id'] ?>">
                                        <input type="hidden" name="novo_status" value="<?= $banner['ativo'] ? 0 : 1 ?>">
                                        <button type="submit" name="toggle_banner" class="btn-action btn-toggle">
                                            <i class="fas fa-<?= $banner['ativo'] ? 'eye-slash' : 'eye' ?>"></i>
                                            <?= $banner['ativo'] ? 'Desativar' : 'Ativar' ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="flex: 1;" onsubmit="return confirm('Tem certeza que deseja deletar este banner?')">
                                        <input type="hidden" name="banner_id" value="<?= $banner['id'] ?>">
                                        <button type="submit" name="deletar_banner" class="btn-action btn-delete">
                                            <i class="fas fa-trash"></i>
                                            Deletar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="order-section">
                        <h3 class="order-title">
                            <i class="fas fa-sort"></i>
                            Reordenar Banners
                        </h3>
                        
                        <form method="POST">
                            <div class="order-grid">
                                <?php foreach ($banners as $banner): ?>
                                    <div class="order-item">
                                        <input type="number" 
                                               name="ordem[<?= $banner['id'] ?>]" 
                                               value="<?= $banner['ordem'] ?>" 
                                               min="1" 
                                               class="order-input"
                                               title="Ordem do banner">
                                        <span class="order-label">
                                            <i class="fas fa-image"></i>
                                            Banner #<?= $banner['id'] ?>
                                            <?= $banner['ativo'] ? '(Ativo)' : '(Inativo)' ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="submit" name="atualizar_ordem" class="submit-button">
                                <i class="fas fa-save"></i>
                                Salvar Nova Ordem
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-edit"></i>
                    Editar Banner
                </h3>
                <button type="button" class="modal-close" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="banner_id" id="editBannerId">
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="color: #e5e7eb; font-weight: 600; margin-bottom: 0.75rem; display: block;">
                            <i class="fas fa-image" style="color: #22c55e; margin-right: 0.5rem;"></i>
                            Imagem Atual
                        </label>
                        <img id="currentImage" src="" alt="Banner atual" class="current-image">
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="color: #e5e7eb; font-weight: 600; margin-bottom: 0.75rem; display: block;">
                            <i class="fas fa-upload" style="color: #22c55e; margin-right: 0.5rem;"></i>
                            Nova Imagem (opcional)
                        </label>
                        <div class="file-upload-area" onclick="document.getElementById('editBannerInput').click()">
                            <div class="upload-text">Clique para selecionar nova imagem</div>
                            <div class="upload-subtitle">JPG, PNG (máx. 5MB)</div>
                        </div>
                        <input type="file" name="nova_banner_img" accept="image/jpeg,image/png,image/jpg" id="editBannerInput" style="display: none;">
                        <div id="selectedFileName" style="color: #22c55e; font-size: 0.9rem; margin-top: 0.5rem; display: none;"></div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeEditModal()">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" name="editar_banner" class="btn-modal btn-save">
                        <i class="fas fa-save"></i>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
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
        
        window.addEventListener('resize', () => {
            if (window.innerWidth <= 1024) {
                sidebar.classList.add('hidden');
                overlay.classList.remove('active');
            } else {
                sidebar.classList.remove('hidden');
                overlay.classList.remove('active');
            }
        });
        
        document.getElementById('banner-upload').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!allowedTypes.includes(this.files[0].type)) {
                    Notiflix.Notify.failure('Formato de arquivo inválido! Use apenas JPG ou PNG.');
                    this.value = '';
                    return;
                }
                
                if (this.files[0].size > 5 * 1024 * 1024) {
                    Notiflix.Notify.failure('Arquivo muito grande! Tamanho máximo: 5MB');
                    this.value = '';
                    return;
                }
                
                Notiflix.Loading.circle('Enviando banner...');
                this.closest('form').submit();
            }
        });
        
        function openEditModal(bannerId, bannerImg) {
            document.getElementById('editBannerId').value = bannerId;
            document.getElementById('currentImage').src = bannerImg;
            document.getElementById('editModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('editForm').reset();
            document.getElementById('selectedFileName').style.display = 'none';
        }
        
        document.getElementById('editBannerInput').addEventListener('change', function() {
            const fileNameDiv = document.getElementById('selectedFileName');
            if (this.files && this.files[0]) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!allowedTypes.includes(this.files[0].type)) {
                    Notiflix.Notify.failure('Formato de arquivo inválido! Use apenas JPG ou PNG.');
                    this.value = '';
                    fileNameDiv.style.display = 'none';
                    return;
                }
                
                if (this.files[0].size > 5 * 1024 * 1024) {
                    Notiflix.Notify.failure('Arquivo muito grande! Tamanho máximo: 5MB');
                    this.value = '';
                    fileNameDiv.style.display = 'none';
                    return;
                }
                
                fileNameDiv.textContent = '✓ ' + this.files[0].name;
                fileNameDiv.style.display = 'block';
            } else {
                fileNameDiv.style.display = 'none';
            }
        });
        
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        document.addEventListener('DOMContentLoaded', () => {
            console.log('%c🖼️ Gestão de Banners carregada!', 'color: #22c55e; font-size: 16px; font-weight: bold;');
            
            if (window.innerWidth <= 1024) {
                sidebar.classList.add('hidden');
            }
            
            const containers = document.querySelectorAll('.content-container');
            containers.forEach((container, index) => {
                container.style.opacity = '0';
                container.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    container.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                    container.style.opacity = '1';
                    container.style.transform = 'translateY(0)';
                }, 200 + (index * 150));
            });
            
            const bannerCards = document.querySelectorAll('.banner-card');
            bannerCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 600 + (index * 100));
            });
        });
        
        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html>