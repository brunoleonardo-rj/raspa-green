<?php
@session_start();

if (file_exists('./conexao.php')) {
    include('./conexao.php');
} elseif (file_exists('../conexao.php')) {
    include('../conexao.php');
} elseif (file_exists('../../conexao.php')) {
    include('../../conexao.php');
}

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você precisa estar logado para acessar esta página!'];
    header("Location: /login");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $_SESSION['message'] = ['type' => 'failure', 'text' => 'Usuário não encontrado!'];
        header("Location: /login");
        exit;
    }

    $stmt_depositos = $pdo->prepare("SELECT SUM(valor) as total_depositado FROM depositos WHERE user_id = :user_id AND status = 'PAID'");
    $stmt_depositos->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt_depositos->execute();
    $total_depositado = $stmt_depositos->fetch(PDO::FETCH_ASSOC)['total_depositado'] ?? 0;

    $stmt_saques = $pdo->prepare("SELECT SUM(valor) as total_sacado FROM saques WHERE user_id = :user_id AND status = 'PAID'");
    $stmt_saques->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt_saques->execute();
    $total_sacado = $stmt_saques->fetch(PDO::FETCH_ASSOC)['total_sacado'] ?? 0;

} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao carregar dados do usuário!'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (!password_verify($senha_atual, $usuario['senha'])) {
        $_SESSION['message'] = ['type' => 'failure', 'text' => 'Senha atual incorreta!'];
    } else {
        try {
            $dados = [
                'id' => $usuario_id,
                'nome' => $nome,
                'telefone' => $telefone,
                'email' => $email
            ];

            if (!empty($nova_senha)) {
                if ($nova_senha === $confirmar_senha) {
                    $dados['senha'] = password_hash($nova_senha, PASSWORD_BCRYPT);
                } else {
                    $_SESSION['message'] = ['type' => 'failure', 'text' => 'As novas senhas não coincidem!'];
                    header("Location: /perfil");
                    exit;
                }
            }

            $setParts = [];
            foreach ($dados as $key => $value) {
                if ($key !== 'id') {
                    $setParts[] = "$key = :$key";
                }
            }

            $query = "UPDATE usuarios SET " . implode(', ', $setParts) . " WHERE id = :id";
            $stmt = $pdo->prepare($query);

            if ($stmt->execute($dados)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Perfil atualizado com sucesso!'];
                header("Location: /perfil");
                exit;
            } else {
                $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao atualizar perfil!'];
            }

        } catch (PDOException $e) {
            $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao atualizar perfil: ' . $e->getMessage()];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite;?> - Meu Perfil</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/assets/style/globalStyles.css?id=<?= time(); ?>">
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">

    <style>
        /* Page Styles */
        .perfil-section {
            margin-top: 100px;
            padding: 4rem 0;
            background: #0a0a0a;
            min-height: calc(100vh - 200px);
        }

        .perfil-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Header */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 900;
            color: white;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff, #9ca3af);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-subtitle {
            font-size: 1.1rem;
            color: #6b7280;
            max-width: 500px;
            margin: 0 auto;
        }

        /* User Avatar */
        .user-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 50%;
            margin: 0 auto 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: 800;
            box-shadow: 0 8px 32px rgba(34, 197, 94, 0.3);
            position: relative;
        }

        .user-avatar::after {
            content: '';
            position: absolute;
            inset: -4px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 50%;
            z-index: -1;
            opacity: 0.3;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.1; }
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

/* Ajustes específicos para os cards de estatísticas */

.stat-card {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    min-height: 140px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.stat-card:hover {
    transform: translateY(-4px);
    border-color: rgba(34, 197, 94, 0.3);
    box-shadow: 0 10px 40px rgba(34, 197, 94, 0.1);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--accent-color);
}

.stat-card.saldo::before { 
    background: linear-gradient(180deg, #22c55e, #16a34a); 
}

.stat-card.depositos::before { 
    background: linear-gradient(180deg, #3b82f6, #2563eb); 
}

.stat-card.saques::before { 
    background: linear-gradient(180deg, #f59e0b, #d97706); 
}

.stat-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    gap: 1.25rem;
}

.stat-info {
    flex: 1;
    min-width: 0;
}

.stat-info h3 {
    color: #9ca3af;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 1rem;
    line-height: 1.2;
}

.stat-value {
    font-size: 2rem;
    font-weight: 900;
    color: white;
    line-height: 1.1;
    margin-bottom: 0.5rem;
}

.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    margin-top: 0.25rem;
}

.stat-icon.saldo { 
    background: rgba(34, 197, 94, 0.15); 
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.stat-icon.depositos { 
    background: rgba(59, 130, 246, 0.15); 
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.stat-icon.saques { 
    background: rgba(245, 158, 11, 0.15); 
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.stat-footer {
    color: #6b7280;
    font-size: 0.8rem;
    margin-top: auto;
    padding-top: 0.75rem;
}

/* Ajustes responsivos melhorados */
@media (max-width: 768px) {
    .stat-card {
        padding: 1.75rem;
        min-height: 120px;
    }
    
    .stat-header {
        gap: 1rem;
        margin-bottom: 0.25rem;
    }
    
    .stat-info h3 {
        font-size: 0.75rem;
        margin-bottom: 0.75rem;
    }
    
    .stat-value {
        font-size: 1.7rem;
        margin-bottom: 0.25rem;
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        font-size: 1.2rem;
    }
    
    .stat-footer {
        font-size: 0.75rem;
        padding-top: 0.5rem;
    }
}

@media (max-width: 480px) {
    .stat-card {
        padding: 1.5rem;
        min-height: 110px;
    }
    
    .stat-header {
        gap: 0.75rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .stat-icon {
        width: 44px;
        height: 44px;
        font-size: 1.1rem;
        margin-top: 0;
    }
}

/* Melhorias no grid das estatísticas */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

@media (max-width: 900px) {
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.25rem;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

/* Animações melhoradas */
.stat-card {
    opacity: 0;
    animation: slideInUp 0.6s ease-out forwards;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

        /* Main Form Card */
        .form-card {
            background: rgba(20, 20, 20, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 3rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), transparent);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .form-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            z-index: 2;
        }

        .form-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 16px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3);
        }

        .form-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .form-description {
            color: #9ca3af;
            font-size: 1rem;
        }

        /* Form Styles */
        .form-grid {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            position: relative;
            z-index: 2;
        }

        .form-group {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #22c55e;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }

        .form-input::placeholder {
            color: #6b7280;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .form-group:focus-within .input-icon {
            color: #22c55e;
        }

        /* Password Toggle */
        .password-toggle {
            background: none;
            border: none;
            color: #22c55e;
            cursor: pointer;
            padding: 0.5rem 0;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s ease;
            margin: 1rem 0;
        }

        .password-toggle:hover {
            color: #16a34a;
        }

        .password-fields {
            display: none;
            flex-direction: column;
            gap: 1.5rem;
            margin: 1.5rem 0;
            padding: 1.5rem;
            background: rgba(34, 197, 94, 0.05);
            border: 1px solid rgba(34, 197, 94, 0.1);
            border-radius: 16px;
        }

        .password-fields.active {
            display: flex;
        }

        .password-fields-title {
            color: #22c55e;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Submit Button */
        .submit-btn {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.3);
            margin-top: 1rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(34, 197, 94, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Success Message */
        .success-message {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 12px;
            padding: 1rem;
            color: #22c55e;
            text-align: center;
            margin-bottom: 2rem;
            display: none;
        }

        .success-message.active {
            display: block;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Security Tips */
        .security-tips {
            background: rgba(59, 130, 246, 0.05);
            border: 1px solid rgba(59, 130, 246, 0.1);
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .security-title {
            color: #3b82f6;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .security-list {
            list-style: none;
            padding: 0;
        }

        .security-list li {
            color: #9ca3af;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .security-list i {
            color: #3b82f6;
            font-size: 0.75rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .perfil-container {
                padding: 0 1rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .form-card {
                padding: 2rem 1.5rem;
                border-radius: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .stat-header {
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .stat-value {
                font-size: 1.5rem;
                line-height: 1.3;
            }
            
            .stat-icon {
                width: 45px;
                height: 45px;
                font-size: 1.1rem;
            }
            
            .user-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .form-card {
                padding: 1.5rem 1rem;
            }
            
            .form-input {
                padding: 0.8rem 0.8rem 0.8rem 2.5rem;
            }
            
            .input-icon {
                left: 0.8rem;
            }
        }

        /* Loading States */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading .submit-btn {
            background: #6b7280;
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <?php include('../inc/header.php'); ?>
    <?php include('../components/modals.php'); ?>

    <section class="perfil-section">
        <div class="perfil-container">
            <!-- Page Header -->
            <div class="page-header fade-in">
                <div class="user-avatar">
                    <?= strtoupper(substr($usuario['nome'], 0, 2)) ?>
                </div>
                <h1 class="page-title">Meu Perfil</h1>
                <p class="page-subtitle">
                    Gerencie suas informações pessoais e configurações da conta
                </p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card saldo">
                    <div class="stat-header">
                        <div class="stat-info">
                            <h3>Saldo Atual</h3>
                            <div class="stat-value">R$ <?= number_format($usuario['saldo'] ?? 0, 2, ',', '.') ?></div>
                        </div>
                        <div class="stat-icon saldo">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card depositos">
                    <div class="stat-header">
                        <div class="stat-info">
                            <h3>Total Depositado</h3>
                            <div class="stat-value">R$ <?= number_format($total_depositado, 2, ',', '.') ?></div>
                        </div>
                        <div class="stat-icon depositos">
                            <i class="bi bi-arrow-down-circle"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card saques">
                    <div class="stat-header">
                        <div class="stat-info">
                            <h3>Total Sacado</h3>
                            <div class="stat-value">R$ <?= number_format($total_sacado, 2, ',', '.') ?></div>
                        </div>
                        <div class="stat-icon saques">
                            <i class="bi bi-arrow-up-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="form-card">
                <div class="form-header">
                    <div class="form-icon">
                        <i class="bi bi-person-gear"></i>
                    </div>
                    <h2 class="form-title">Editar Perfil</h2>
                    <p class="form-description">
                        Atualize suas informações pessoais com segurança
                    </p>
                </div>

                <form method="POST" class="form-grid" id="perfilForm">
                    <div class="form-group">
                        <div class="input-icon">
                            <i class="bi bi-person"></i>
                        </div>
                        <input type="text" 
                               name="nome" 
                               class="form-input"
                               value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" 
                               placeholder="Nome completo" 
                               required>
                    </div>

                    <div class="form-group">
                        <div class="input-icon">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <input type="text" 
                               id="telefone" 
                               name="telefone" 
                               class="form-input"
                               value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>" 
                               placeholder="(11) 99999-9999" 
                               required>
                    </div>

                    <div class="form-group">
                        <div class="input-icon">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <input type="email" 
                               name="email" 
                               class="form-input"
                               value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" 
                               placeholder="seu@email.com" 
                               required>
                    </div>

                    <!-- Password Toggle -->
                    <button type="button" class="password-toggle" id="toggleSenha">
                        <i class="bi bi-key"></i>
                        Alterar senha
                    </button>

                    <!-- Password Fields -->
                    <div class="password-fields" id="camposSenha">
                        <div class="password-fields-title">
                            <i class="bi bi-shield-lock"></i>
                            Nova Senha
                        </div>
                        
                        <div class="form-group">
                            <div class="input-icon">
                                <i class="bi bi-lock"></i>
                            </div>
                            <input type="password" 
                                   name="nova_senha" 
                                   class="form-input"
                                   placeholder="Digite a nova senha">
                        </div>

                        <div class="form-group">
                            <div class="input-icon">
                                <i class="bi bi-lock-fill"></i>
                            </div>
                            <input type="password" 
                                   name="confirmar_senha" 
                                   class="form-input"
                                   placeholder="Confirme a nova senha">
                        </div>
                    </div>

                    <!-- Current Password -->
                    <div class="form-group">
                        <div class="input-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <input type="password" 
                               name="senha_atual" 
                               class="form-input"
                               placeholder="Senha atual (para confirmar alterações)" 
                               required>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="bi bi-check-circle"></i>
                        Atualizar Perfil
                    </button>
                </form>

                <!-- Security Tips -->
                <div class="security-tips">
                    <div class="security-title">
                        <i class="bi bi-info-circle"></i>
                        Dicas de Segurança
                    </div>
                    <ul class="security-list">
                        <li>
                            <i class="bi bi-check"></i>
                            Use uma senha forte com pelo menos 8 caracteres
                        </li>
                        <li>
                            <i class="bi bi-check"></i>
                            Nunca compartilhe sua senha com terceiros
                        </li>
                        <li>
                            <i class="bi bi-check"></i>
                            Mantenha seus dados sempre atualizados
                        </li>
                        <li>
                            <i class="bi bi-check"></i>
                            Use um e-mail válido para recuperação da conta
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <?php include('../inc/footer.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Phone mask
            const telefoneInput = document.getElementById('telefone');
            telefoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 11) value = value.slice(0, 11);

                let formatted = '';
                if (value.length > 0) {
                    formatted += '(' + value.substring(0, 2);
                }
                if (value.length >= 3) {
                    formatted += ') ' + value.substring(2, 7);
                }
                if (value.length >= 8) {
                    formatted += '-' + value.substring(7);
                }
                e.target.value = formatted;
            });

            // Password toggle
            const toggleSenha = document.getElementById('toggleSenha');
            const camposSenha = document.getElementById('camposSenha');

            toggleSenha.addEventListener('click', function() {
                camposSenha.classList.toggle('active');
                
                const icon = this.querySelector('i');
                const text = camposSenha.classList.contains('active') ? 'Cancelar' : 'Alterar senha';
                const iconClass = camposSenha.classList.contains('active') ? 'bi-x-circle' : 'bi-key';
                
                icon.className = `bi ${iconClass}`;
                this.innerHTML = `<i class="bi ${iconClass}"></i> ${text}`;
            });

            // Form submission
            const perfilForm = document.getElementById('perfilForm');
            const submitBtn = document.getElementById('submitBtn');

            perfilForm.addEventListener('submit', function(e) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite;"></i> Atualizando...';
                perfilForm.classList.add('loading');
            });

            // Add spin animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        });

        // Notiflix configuration
        Notiflix.Notify.init({
            width: '300px',
            position: 'right-top',
            distance: '20px',
            opacity: 1,
            borderRadius: '12px',
            timeout: 4000,
            success: {
                background: '#22c55e',
                textColor: '#fff',
            },
            failure: {
                background: '#ef4444',
                textColor: '#fff',
            }
        });

        // Show messages if any
        <?php if (isset($_SESSION['message'])): ?>
            Notiflix.Notify.<?php echo $_SESSION['message']['type']; ?>('<?php echo $_SESSION['message']['text']; ?>');
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        console.log('%c👤 Perfil do usuário carregado!', 'color: #22c55e; font-size: 16px; font-weight: bold;');
    </script>
</body>
</html>