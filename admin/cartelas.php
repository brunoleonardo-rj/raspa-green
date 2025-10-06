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

$nome = ($stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?"))->execute([$usuarioId]) ? $stmt->fetchColumn() : null;
$nome = $nome ? explode(' ', $nome)[0] : null;

if (isset($_POST['adicionar_raspadinha'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $valor = str_replace(',', '.', $_POST['valor']);
    
    $banner = '';
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $uploadDir = '../assets/img/banners/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $newName = uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newName;
            
            if (move_uploaded_file($_FILES['banner']['tmp_name'], $uploadPath)) {
                $banner = '/assets/img/banners/' . $newName;
            } else {
                $_SESSION['failure'] = 'Erro ao fazer upload do banner!';
                header('Location: '.$_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['failure'] = 'Formato de arquivo inválido! Use apenas JPG ou PNG.';
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO raspadinhas (nome, descricao, banner, valor) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$nome, $descricao, $banner, $valor])) {
        $_SESSION['success'] = 'Raspadinha adicionada com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao adicionar raspadinha!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['editar_raspadinha'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $valor = str_replace(',', '.', $_POST['valor']);
    
    $raspadinha = $pdo->prepare("SELECT banner FROM raspadinhas WHERE id = ?");
    $raspadinha->execute([$id]);
    $raspadinha = $raspadinha->fetch(PDO::FETCH_ASSOC);
    $banner = $raspadinha['banner'];
    
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $uploadDir = '../assets/img/banners/';
            $newName = uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newName;
            
            if (move_uploaded_file($_FILES['banner']['tmp_name'], $uploadPath)) {
                if ($banner && file_exists('../' . $banner)) {
                    unlink('../' . $banner);
                }
                $banner = '/assets/img/banners/' . $newName;
            } else {
                $_SESSION['failure'] = 'Erro ao fazer upload do novo banner!';
                header('Location: '.$_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['failure'] = 'Formato de arquivo inválido! Use apenas JPG ou PNG.';
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        }
    }
    
    $stmt = $pdo->prepare("UPDATE raspadinhas SET nome = ?, descricao = ?, banner = ?, valor = ? WHERE id = ?");
    if ($stmt->execute([$nome, $descricao, $banner, $valor, $id])) {
        $_SESSION['success'] = 'Raspadinha atualizada com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao atualizar raspadinha!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['excluir_raspadinha'])) {
    $id = $_GET['id'];
    
    $raspadinha = $pdo->prepare("SELECT banner FROM raspadinhas WHERE id = ?");
    $raspadinha->execute([$id]);
    $raspadinha = $raspadinha->fetch(PDO::FETCH_ASSOC);
    
    $pdo->prepare("DELETE FROM raspadinha_premios WHERE raspadinha_id = ?")->execute([$id]);
    
    if ($pdo->prepare("DELETE FROM raspadinhas WHERE id = ?")->execute([$id])) {
        if ($raspadinha['banner'] && file_exists('../' . $raspadinha['banner'])) {
            unlink('../' . $raspadinha['banner']);
        }
        $_SESSION['success'] = 'Raspadinha excluída com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao excluir raspadinha!';
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['adicionar_premio'])) {
    $raspadinha_id = $_POST['raspadinha_id'];
    $nome = $_POST['nome'];
    $valor = str_replace(',', '.', $_POST['valor']);
    $probabilidade = str_replace(',', '.', $_POST['probabilidade']);
    
    $icone = '';
    if (isset($_FILES['icone']) && $_FILES['icone']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = pathinfo($_FILES['icone']['name'], PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $uploadDir = '../assets/img/icons/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $newName = uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newName;
            
            if (move_uploaded_file($_FILES['icone']['tmp_name'], $uploadPath)) {
                $icone = '/assets/img/icons/' . $newName;
            } else {
                $_SESSION['failure'] = 'Erro ao fazer upload do ícone!';
                header('Location: '.$_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['failure'] = 'Formato de arquivo inválido! Use apenas JPG ou PNG.';
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO raspadinha_premios (raspadinha_id, nome, icone, valor, probabilidade) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$raspadinha_id, $nome, $icone, $valor, $probabilidade])) {
        $_SESSION['success'] = 'Prêmio adicionado com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao adicionar prêmio!';
    }
    header('Location: '.$_SERVER['PHP_SELF'].'?raspadinha_id='.$raspadinha_id);
    exit;
}

if (isset($_POST['editar_premio'])) {
    $id = $_POST['id'];
    $raspadinha_id = $_POST['raspadinha_id'];
    $nome = $_POST['nome'];
    $valor = str_replace(',', '.', $_POST['valor']);
    $probabilidade = str_replace(',', '.', $_POST['probabilidade']);
    
    $premio = $pdo->prepare("SELECT icone FROM raspadinha_premios WHERE id = ?");
    $premio->execute([$id]);
    $premio = $premio->fetch(PDO::FETCH_ASSOC);
    $icone = $premio['icone'];
    
    if (isset($_FILES['icone']) && $_FILES['icone']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = pathinfo($_FILES['icone']['name'], PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $uploadDir = '../assets/img/icons/';
            $newName = uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newName;
            
            if (move_uploaded_file($_FILES['icone']['tmp_name'], $uploadPath)) {
                if ($icone && file_exists('../' . $icone)) {
                    unlink('../' . $icone);
                }
                $icone = '/assets/img/icons/' . $newName;
            } else {
                $_SESSION['failure'] = 'Erro ao fazer upload do novo ícone!';
                header('Location: '.$_SERVER['PHP_SELF'].'?raspadinha_id='.$raspadinha_id);
                exit;
            }
        } else {
            $_SESSION['failure'] = 'Formato de arquivo inválido! Use apenas JPG ou PNG.';
            header('Location: '.$_SERVER['PHP_SELF'].'?raspadinha_id='.$raspadinha_id);
            exit;
        }
    }
    
    $stmt = $pdo->prepare("UPDATE raspadinha_premios SET nome = ?, icone = ?, valor = ?, probabilidade = ? WHERE id = ?");
    if ($stmt->execute([$nome, $icone, $valor, $probabilidade, $id])) {
        $_SESSION['success'] = 'Prêmio atualizado com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao atualizar prêmio!';
    }
    header('Location: '.$_SERVER['PHP_SELF'].'?raspadinha_id='.$raspadinha_id);
    exit;
}

if (isset($_GET['excluir_premio'])) {
    $id = $_GET['id'];
    $raspadinha_id = $_GET['raspadinha_id'];
    
    $premio = $pdo->prepare("SELECT icone FROM raspadinha_premios WHERE id = ?");
    $premio->execute([$id]);
    $premio = $premio->fetch(PDO::FETCH_ASSOC);
    
    if ($pdo->prepare("DELETE FROM raspadinha_premios WHERE id = ?")->execute([$id])) {
        if ($premio['icone'] && file_exists('../' . $premio['icone'])) {
            unlink('../' . $premio['icone']);
        }
        $_SESSION['success'] = 'Prêmio excluído com sucesso!';
    } else {
        $_SESSION['failure'] = 'Erro ao excluir prêmio!';
    }
    header('Location: '.$_SERVER['PHP_SELF'].'?raspadinha_id='.$raspadinha_id);
    exit;
}

$raspadinhas = $pdo->query("SELECT * FROM raspadinhas ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_raspadinhas = count($raspadinhas);
$valor_total_raspadinhas = array_sum(array_column($raspadinhas, 'valor'));

$premios = [];
$raspadinha_selecionada = null;
if (isset($_GET['raspadinha_id'])) {
    $raspadinha_id = $_GET['raspadinha_id'];
    $premios = $pdo->prepare("SELECT * FROM raspadinha_premios WHERE raspadinha_id = ? ORDER BY probabilidade DESC");
    $premios->execute([$raspadinha_id]);
    $premios = $premios->fetchAll(PDO::FETCH_ASSOC);
    
    $raspadinha_selecionada = $pdo->prepare("SELECT * FROM raspadinhas WHERE id = ?");
    $raspadinha_selecionada->execute([$raspadinha_id]);
    $raspadinha_selecionada = $raspadinha_selecionada->fetch(PDO::FETCH_ASSOC);
}

$total_premios = 0;
$valor_total_premios = 0;
if (!empty($premios)) {
    $total_premios = count($premios);
    $valor_total_premios = array_sum(array_column($premios, 'valor'));
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite ?? 'Admin'; ?> - Gerenciar Raspadinhas</title>
    
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
        
        .mini-stat-icon.purple {
            background: linear-gradient(135deg, rgba(147, 51, 234, 0.2) 0%, rgba(147, 51, 234, 0.1) 100%);
            border-color: rgba(147, 51, 234, 0.3);
            color: #9333ea;
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
        
        /* Form Section */
        .form-section {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(20px);
        }
        
        .form-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-icon-container {
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
        
        .form-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #ffffff;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            color: #e5e7eb;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .form-input {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: rgba(34, 197, 94, 0.5);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }
        
        .form-input::placeholder {
            color: #6b7280;
        }
        
        .form-button {
            width: 100%;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.875rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .form-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(34, 197, 94, 0.4);
        }
        
        .cancel-button {
            width: 100%;
            background: rgba(107, 114, 128, 0.3);
            color: #e5e7eb;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            display: block;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }
        
        .cancel-button:hover {
            background: rgba(107, 114, 128, 0.4);
        }
        
        /* Raspadinha Cards */
        .raspadinha-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
        }
        
        .raspadinha-card::before {
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
        
        .raspadinha-card:hover::before {
            opacity: 1;
        }
        
        .raspadinha-card:hover {
            transform: translateY(-4px);
            border-color: rgba(34, 197, 94, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .raspadinha-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .raspadinha-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }
        
        .raspadinha-description {
            font-size: 0.9rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }
        
        .raspadinha-banner {
            width: 80px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .raspadinha-value {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }
        
        .raspadinha-date {
            color: #9ca3af;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .raspadinha-date i {
            color: #6b7280;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
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
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .btn-manage {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        /* Prize Cards */
        .prize-card {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .prize-card:hover {
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.3);
            transform: translateY(-2px);
        }
        
        .prize-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .prize-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .prize-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .prize-info {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .prize-stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #e5e7eb;
            font-size: 0.9rem;
        }
        
        .prize-stat i {
            color: #22c55e;
            width: 16px;
            text-align: center;
        }
        
        .prize-actions {
            display: flex;
            gap: 1rem;
        }
        
        .prize-action-btn {
            flex: 1;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .prize-edit-btn {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .prize-delete-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .prize-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        /* Selected Raspadinha Header */
        .selected-raspadinha {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        
        .selected-raspadinha h3 {
            color: #22c55e;
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
        }
        
        .selected-raspadinha p {
            color: #9ca3af;
            margin: 0.5rem 0 0 0;
            font-size: 0.9rem;
        }
        
        .back-btn {
            background: rgba(107, 114, 128, 0.3);
            color: #e5e7eb;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-btn:hover {
            background: rgba(107, 114, 128, 0.4);
            transform: translateY(-1px);
        }
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem; /* Aumentar de 1.5rem para 2rem ou mais */
        }
        
        .raspadinha-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem; /* Adicionar esta linha */
            transition: all 0.3s ease;
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
        }
        
        /* Current Image Preview */
        .current-image {
            margin-top: 0.5rem;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .current-image p {
            color: #9ca3af;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }
        
        .current-image img {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
            max-height: 100px;
        }
        
        /* Empty States */
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
        
        /* Scroll Container */
        .scroll-container {
            max-height: 500px;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .scroll-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .scroll-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }
        
        .scroll-container::-webkit-scrollbar-thumb {
            background: rgba(34, 197, 94, 0.3);
            border-radius: 4px;
        }
        
        .scroll-container::-webkit-scrollbar-thumb:hover {
            background: rgba(34, 197, 94, 0.5);
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
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .raspadinhas-grid {
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
            
            .raspadinha-card {
                padding: 1.5rem;
            }
            
            .raspadinha-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
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
            
            .raspadinha-value {
                font-size: 1.5rem;
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
                <a href="cartelas.php" class="nav-item active">
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
                <h2 class="welcome-title">Gerenciar Raspadinhas</h2>
                <p class="welcome-subtitle">Crie e configure raspadinhas e seus prêmios de forma fácil e intuitiva</p>
            </section>
            
            <!-- Stats Grid -->
            <section class="stats-grid">
                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon">
                            <i class="fas fa-ticket"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value"><?= number_format($total_raspadinhas, 0, ',', '.') ?></div>
                    <div class="mini-stat-label">Total de Raspadinhas</div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon purple">
                            <i class="fas fa-gift"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value"><?= number_format($total_premios, 0, ',', '.') ?></div>
                    <div class="mini-stat-label">Total de Prêmios</div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value">R$ <?= number_format($valor_total_raspadinhas, 2, ',', '.') ?></div>
                    <div class="mini-stat-label">Valor Total Raspadinhas</div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="mini-stat-header">
                        <div class="mini-stat-icon purple">
                            <i class="fas fa-trophy"></i>
                        </div>
                    </div>
                    <div class="mini-stat-value">R$ <?= number_format($valor_total_premios, 2, ',', '.') ?></div>
                    <div class="mini-stat-label">Valor Total Prêmios</div>
                </div>
            </section>

            <?php if (isset($_GET['raspadinha_id'])): ?>
                <!-- Selected Raspadinha Header -->
                <div class="selected-raspadinha">
                    <div class="flex-1">
                        <h3>🎯 Gerenciando: <?= htmlspecialchars($raspadinha_selecionada['nome']) ?></h3>
                        <p>Configure os prêmios desta raspadinha</p>
                    </div>
                    <a href="?" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Main Content Grid -->
            <div class="content-grid">
                <!-- Form Section -->
                <section class="form-section">
                    <div class="form-header">
                        <div class="form-icon-container">
                            <i class="fas fa-<?= isset($_GET['editar_raspadinha']) ? 'edit' : 'plus' ?>"></i>
                        </div>
                        <h3 class="form-title">
                            <?= isset($_GET['editar_raspadinha']) ? 'Editar' : 'Adicionar' ?> Raspadinha
                        </h3>
                    </div>
                    
                    <?php
                    $raspadinha_edit = null;
                    if (isset($_GET['editar_raspadinha'])) {
                        $id = $_GET['id'];
                        $raspadinha_edit = $pdo->prepare("SELECT * FROM raspadinhas WHERE id = ?");
                        $raspadinha_edit->execute([$id]);
                        $raspadinha_edit = $raspadinha_edit->fetch(PDO::FETCH_ASSOC);
                    }
                    ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <?php if ($raspadinha_edit): ?>
                            <input type="hidden" name="id" value="<?= $raspadinha_edit['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-signature"></i>
                                Nome da Raspadinha
                            </label>
                            <input type="text" name="nome" value="<?= $raspadinha_edit ? htmlspecialchars($raspadinha_edit['nome']) : '' ?>" class="form-input" placeholder="Digite o nome da raspadinha..." required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-align-left"></i>
                                Descrição
                            </label>
                            <textarea name="descricao" class="form-input" rows="3" placeholder="Descreva a raspadinha..." required><?= $raspadinha_edit ? htmlspecialchars($raspadinha_edit['descricao']) : '' ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-dollar-sign"></i>
                                Valor (R$)
                            </label>
                            <input type="text" name="valor" value="<?= $raspadinha_edit ? htmlspecialchars($raspadinha_edit['valor']) : '' ?>" class="form-input" placeholder="0,00" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-image"></i>
                                Banner da Raspadinha
                            </label>
                            <input type="file" name="banner" accept="image/jpeg, image/png" class="form-input">
                            <?php if ($raspadinha_edit && $raspadinha_edit['banner']): ?>
                                <div class="current-image">
                                    <p>Banner atual:</p>
                                    <img src="<?= htmlspecialchars($raspadinha_edit['banner']) ?>" alt="Banner atual">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" name="<?= $raspadinha_edit ? 'editar_raspadinha' : 'adicionar_raspadinha' ?>" class="form-button">
                            <i class="fas fa-save"></i>
                            <?= $raspadinha_edit ? 'Atualizar' : 'Adicionar' ?> Raspadinha
                        </button>
                        
                        <?php if ($raspadinha_edit): ?>
                            <a href="?" class="cancel-button">
                                <i class="fas fa-times"></i>
                                Cancelar
                            </a>
                        <?php endif; ?>
                    </form>
                </section>
                
                <!-- List Section -->
                <section class="form-section">
                    <div class="form-header">
                        <div class="form-icon-container">
                            <i class="fas fa-list"></i>
                        </div>
                        <h3 class="form-title">Raspadinhas Cadastradas</h3>
                    </div>
                    
                    <div class="scroll-container">
                        <?php if (empty($raspadinhas)): ?>
                            <div class="empty-state">
                                <i class="fas fa-ticket"></i>
                                <h3>Nenhuma raspadinha cadastrada</h3>
                                <p>Comece criando sua primeira raspadinha</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($raspadinhas as $raspadinha): ?>
                                <div class="raspadinha-card">
                                    <div class="raspadinha-header">
                                        <div class="flex-1">
                                            <h3 class="raspadinha-name"><?= htmlspecialchars($raspadinha['nome']) ?></h3>
                                            <p class="raspadinha-description"><?= htmlspecialchars($raspadinha['descricao']) ?></p>
                                        </div>
                                        <?php if ($raspadinha['banner']): ?>
                                            <img src="<?= htmlspecialchars($raspadinha['banner']) ?>" alt="Banner" class="raspadinha-banner">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="raspadinha-value">
                                        R$ <?= number_format($raspadinha['valor'], 2, ',', '.') ?>
                                    </div>
                                    
                                    <div class="raspadinha-date">
                                        <i class="fas fa-calendar"></i>
                                        <span><?= date('d/m/Y H:i', strtotime($raspadinha['created_at'])) ?></span>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <a href="?raspadinha_id=<?= $raspadinha['id'] ?>" class="action-btn btn-manage">
                                            <i class="fas fa-cog"></i>
                                            Gerenciar
                                        </a>
                                        <a href="?editar_raspadinha&id=<?= $raspadinha['id'] ?>" class="action-btn btn-edit">
                                            <i class="fas fa-edit"></i>
                                            Editar
                                        </a>
                                        <a href="?excluir_raspadinha&id=<?= $raspadinha['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir esta raspadinha e todos os seus prêmios?')" class="action-btn btn-delete">
                                            <i class="fas fa-trash"></i>
                                            Excluir
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <?php if (isset($_GET['raspadinha_id'])): ?>
                <!-- Prêmios Section -->
                <div class="content-grid">
                    <!-- Add Prize Form -->
                    <section class="form-section">
                        <div class="form-header">
                            <div class="form-icon-container">
                                <i class="fas fa-<?= isset($_GET['editar_premio']) ? 'edit' : 'gift' ?>"></i>
                            </div>
                            <h3 class="form-title">
                                <?= isset($_GET['editar_premio']) ? 'Editar' : 'Adicionar' ?> Prêmio
                            </h3>
                        </div>
                        
                        <?php
                        $premio_edit = null;
                        if (isset($_GET['editar_premio'])) {
                            $id = $_GET['id'];
                            $premio_edit = $pdo->prepare("SELECT * FROM raspadinha_premios WHERE id = ?");
                            $premio_edit->execute([$id]);
                            $premio_edit = $premio_edit->fetch(PDO::FETCH_ASSOC);
                        }
                        ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="raspadinha_id" value="<?= $_GET['raspadinha_id'] ?>">
                            <?php if ($premio_edit): ?>
                                <input type="hidden" name="id" value="<?= $premio_edit['id'] ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-tag"></i>
                                    Nome do Prêmio
                                </label>
                                <input type="text" name="nome" value="<?= $premio_edit ? htmlspecialchars($premio_edit['nome']) : '' ?>" class="form-input" placeholder="Digite o nome do prêmio..." required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-dollar-sign"></i>
                                    Valor (R$)
                                </label>
                                <input type="text" name="valor" value="<?= $premio_edit ? htmlspecialchars($premio_edit['valor']) : '' ?>" class="form-input" placeholder="0,00" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-percentage"></i>
                                    Probabilidade (0.00 - 100.00)
                                </label>
                                <input type="text" name="probabilidade" value="<?= $premio_edit ? htmlspecialchars($premio_edit['probabilidade']) : '' ?>" class="form-input" placeholder="5.00" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-image"></i>
                                    Ícone do Prêmio
                                </label>
                                <input type="file" name="icone" accept="image/jpeg, image/png" class="form-input">
                                <?php if ($premio_edit && $premio_edit['icone']): ?>
                                    <div class="current-image">
                                        <p>Ícone atual:</p>
                                        <img src="<?= htmlspecialchars($premio_edit['icone']) ?>" alt="Ícone atual">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <button type="submit" name="<?= $premio_edit ? 'editar_premio' : 'adicionar_premio' ?>" class="form-button">
                                <i class="fas fa-save"></i>
                                <?= $premio_edit ? 'Atualizar' : 'Adicionar' ?> Prêmio
                            </button>
                            
                            <?php if ($premio_edit): ?>
                                <a href="?raspadinha_id=<?= $_GET['raspadinha_id'] ?>" class="cancel-button">
                                    <i class="fas fa-times"></i>
                                    Cancelar
                                </a>
                            <?php endif; ?>
                        </form>
                    </section>

                    <!-- Prizes List -->
                    <section class="form-section">
                        <div class="form-header">
                            <div class="form-icon-container">
                                <i class="fas fa-gift"></i>
                            </div>
                            <h3 class="form-title">Prêmios Cadastrados</h3>
                        </div>
                        
                        <div class="scroll-container">
                            <?php if (empty($premios)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-gift"></i>
                                    <h3>Nenhum prêmio cadastrado</h3>
                                    <p>Adicione prêmios para esta raspadinha</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($premios as $premio): ?>
                                    <div class="prize-card">
                                        <div class="prize-header">
                                            <div class="prize-name">
                                                <?php if ($premio['icone']): ?>
                                                    <img src="<?= htmlspecialchars($premio['icone']) ?>" alt="Ícone" class="prize-icon">
                                                <?php endif; ?>
                                                <?= htmlspecialchars($premio['nome']) ?>
                                            </div>
                                        </div>
                                        
                                        <div class="prize-info">
                                            <div class="prize-stat">
                                                <i class="fas fa-dollar-sign"></i>
                                                <span>R$ <?= number_format($premio['valor'], 2, ',', '.') ?></span>
                                            </div>
                                            <div class="prize-stat">
                                                <i class="fas fa-percentage"></i>
                                                <span><?= number_format($premio['probabilidade'], 2, ',', '.') ?>%</span>
                                            </div>
                                        </div>
                                        
                                        <div class="prize-actions">
                                            <a href="?raspadinha_id=<?= $_GET['raspadinha_id'] ?>&editar_premio&id=<?= $premio['id'] ?>" class="prize-action-btn prize-edit-btn">
                                                <i class="fas fa-edit"></i>
                                                Editar
                                            </a>
                                            <a href="?raspadinha_id=<?= $_GET['raspadinha_id'] ?>&excluir_premio&id=<?= $premio['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir este prêmio?')" class="prize-action-btn prize-delete-btn">
                                                <i class="fas fa-trash"></i>
                                                Excluir
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            <?php endif; ?>
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
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            console.log('%c🎯 Raspadinhas carregadas!', 'color: #22c55e; font-size: 16px; font-weight: bold;');
            
            // Auto-format currency inputs
            const currencyInputs = document.querySelectorAll('input[name="valor"]');
            currencyInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/[^\d,]/g, '');
                    e.target.value = value;
                });
            });
            
            // Auto-format percentage inputs
            const percentageInputs = document.querySelectorAll('input[name="probabilidade"]');
            percentageInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/[^\d,]/g, '');
                    e.target.value = value;
                });
            });

            // Check if mobile on load
            if (window.innerWidth <= 1024) {
                sidebar.classList.add('hidden');
            }
            
            // Animate cards on load
            const raspadinhaCards = document.querySelectorAll('.raspadinha-card, .prize-card');
            raspadinhaCards.forEach((card, index) => {
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

            // Scroll to top when editing (smooth behavior)
            const currentUrl = new URL(window.location.href);
            const hasEditParams = currentUrl.searchParams.has('editar_raspadinha') || 
                                 currentUrl.searchParams.has('editar_premio');
            
            if (hasEditParams) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
        
        // Smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html>