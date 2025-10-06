<?php
@session_start();

if (isset($_SESSION['usuario_id'])) {
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você já está logado!'];
    header("Location: /");
    exit;
}

require_once '../conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
      
      if($usuario['banido'] == 1){
        $_SESSION['message'] = ['type' => 'failure', 'text' => 'Você está banido!'];
        header("Location: /");
        exit;
      }
      
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Login realizado com sucesso!'];
        header("Location: /");
        exit;
    } else {
        $_SESSION['message'] = ['type' => 'failure', 'text' => 'E-mail ou senha inválidos.'];
        header("Location: /login");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite;?> - Login</title>
    
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
        .login-section {
            margin-top: 100px;
            padding: 4rem 0;
            background: #0a0a0a;
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        /* Left Section */
        .left-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .brand-content {
            position: relative;
            z-index: 2;
        }

        .brand-logo {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 24px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: 900;
            box-shadow: 0 20px 40px rgba(34, 197, 94, 0.3);
            position: relative;
        }

        .brand-logo::after {
            content: '';
            position: absolute;
            inset: -4px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 28px;
            z-index: -1;
            opacity: 0.3;
            animation: pulse 3s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.05); opacity: 0.1; }
        }

        .brand-title {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff, #9ca3af);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.1;
        }

        .brand-subtitle {
            font-size: 1.3rem;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 3rem;
        }

        .highlight-text {
            color: #22c55e;
            font-weight: 700;
        }

        .features-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #e5e7eb;
            font-size: 1.1rem;
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(34, 197, 94, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #22c55e;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        /* Right Section */
        .right-section {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: rgba(20, 20, 20, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
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

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 2;
        }

        .login-icon {
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

        .login-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: #9ca3af;
            font-size: 1rem;
        }

        /* Form Styles */
        .login-form {
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

        /* Submit Button */
        .submit-btn {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            padding: 1rem;
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
            position: relative;
            overflow: hidden;
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

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        /* Footer Links */
        .form-footer {
            text-align: center;
            margin-top: 2rem;
            position: relative;
            z-index: 2;
        }

        .footer-text {
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .footer-link {
            color: #22c55e;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: #16a34a;
            text-decoration: underline;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }

        .divider span {
            padding: 0 1rem;
        }

        /* Floating Elements */
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .floating-element {
            position: absolute;
            width: 8px;
            height: 8px;
            background: rgba(34, 197, 94, 0.3);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            top: 40%;
            right: 15%;
            animation-delay: 1s;
        }

        .floating-element:nth-child(3) {
            bottom: 30%;
            left: 20%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(4) {
            bottom: 20%;
            right: 25%;
            animation-delay: 3s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
                opacity: 0.3;
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
                opacity: 0.8;
            }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .login-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                max-width: 600px;
            }

            .brand-content {
                display: none;
            }
            
            .left-section {
                order: 2;
                text-align: center;
            }
            
            .right-section {
                order: 1;
            }
            
            .brand-title {
                font-size: 2.5rem;
            }
            
            .features-list {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }
            
            .feature-item {
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            .login-section {
                padding: 2rem 0;
            }
            
            .login-container {
                padding: 0 1rem;
            }
            
            .login-card {
                padding: 2rem;
                border-radius: 20px;
            }
            
            .brand-logo {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
            
            .brand-title {
                font-size: 2rem;
            }
            
            .brand-subtitle {
                font-size: 1.1rem;
            }
            
            .features-list {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 1.5rem;
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

    <section class="login-section">
        <!-- Floating Elements -->
        <div class="floating-elements">
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
        </div>

        <div class="login-container fade-in">
            <!-- Left Section -->
            <div class="left-section">
                <div class="brand-content">
                    <h1 class="brand-title">Bem-vindo de volta!</h1>
                    <p class="brand-subtitle">
                        Entre na sua conta e continue ganhando 
                        <span class="highlight-text">prêmios incríveis</span> 
                        com nossas raspadinhas!
                    </p>
                    
                    <div class="features-list">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <span>Login 100% seguro</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-lightning"></i>
                            </div>
                            <span>PIX instantâneo</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-trophy"></i>
                            </div>
                            <span>Prêmios de até R$ 15.000</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-headset"></i>
                            </div>
                            <span>Suporte 24/7</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Section -->
            <div class="right-section">
                <div class="login-card">
                    <div class="login-header">
                        <div class="login-icon">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <h2 class="login-title">Acesse sua conta</h2>
                        <p class="login-subtitle">
                            Digite suas credenciais para continuar
                        </p>
                    </div>

                    <form method="POST" class="login-form" id="loginForm">
                        <div class="form-group">
                            <div class="input-icon">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <input type="email" 
                                   name="email" 
                                   class="form-input"
                                   placeholder="seu@email.com" 
                                   required>
                        </div>

                        <div class="form-group">
                            <div class="input-icon">
                                <i class="bi bi-lock"></i>
                            </div>
                            <input type="password" 
                                   name="senha" 
                                   class="form-input"
                                   placeholder="Sua senha" 
                                   required>
                        </div>

                        <button type="submit" class="submit-btn" id="submitBtn">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Entrar
                        </button>
                    </form>

                    <div class="form-footer">
                        <div class="divider">
                            <span>ou</span>
                        </div>
                        
                        <p class="footer-text">
                            Ainda não tem uma conta?
                        </p>
                        <a href="/cadastro" class="footer-link">
                            Cadastre-se grátis
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include('../inc/footer.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form submission
            const loginForm = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');

            loginForm.addEventListener('submit', function(e) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite;"></i> Entrando...';
                loginForm.classList.add('loading');
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

            // Focus enhancements
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
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
            },
            warning: {
                background: '#f59e0b',
                textColor: '#fff',
            }
        });

        // Show messages if any
        <?php if (isset($_SESSION['message'])): ?>
            Notiflix.Notify.<?php echo $_SESSION['message']['type']; ?>('<?php echo $_SESSION['message']['text']; ?>');
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        console.log('%c🔐 Página de Login carregada!', 'color: #22c55e; font-size: 16px; font-weight: bold;');
    </script>
</body>
</html>