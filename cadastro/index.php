<?php
session_start();

if(isset($_SESSION['usuario_id'])){
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você já está logado!'];
    header("Location: /");
    exit;
}

require_once '../conexao.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $telefone = trim($_POST['telefone']);
    $email = trim($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $ref = $_POST['ref'] ?? null;

    try {
        $stmt_config = $pdo->query("SELECT cpa_padrao, revshare_padrao FROM config LIMIT 1");
        $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
        
        $cpa_padrao = $config['cpa_padrao'] ?? 0.00;
        $revshare_padrao = $config['revshare_padrao'] ?? 0.00;
    } catch (PDOException $e) {
        $cpa_padrao = 0.00;
        $revshare_padrao = 0.00;
    }

    $stmt = $pdo->prepare("INSERT INTO usuarios 
                          (nome, telefone, email, senha, saldo, indicacao, banido, comissao_cpa, comissao_revshare, created_at, updated_at) 
                          VALUES (?, ?, ?, ?, 0, ?, 0, ?, ?, NOW(), NOW())");

    try {
        $stmt->execute([$nome, $telefone, $email, $senha, $ref, $cpa_padrao, $revshare_padrao]);
        
        $usuarioId = $pdo->lastInsertId();
        $_SESSION['usuario_id'] = $usuarioId;
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Cadastro realizado com sucesso!'];
        
        header("Location: /");
        exit;
    } catch (PDOException $e) {
        error_log("Erro ao cadastrar: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao realizar cadastro!'];
        header("Location: /cadastro");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite;?> - Cadastro</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/assets/style/globalStyles.css?id=<?php echo time();?>"/>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">

    <style>
        /* Page Styles */
        .cadastro-section {
            margin-top: 100px;
            padding: 4rem 0;
            background: #0a0a0a;
            min-height: calc(100vh - 100px);
            position: relative;
        }

        .cadastro-container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            min-height: calc(100vh - 200px);
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

        .benefits-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #e5e7eb;
            font-size: 1.1rem;
        }

        .benefit-icon {
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

        .cadastro-card {
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

        .cadastro-card::before {
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

        .cadastro-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 2;
        }

        .cadastro-icon {
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

        .cadastro-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .cadastro-subtitle {
            color: #9ca3af;
            font-size: 1rem;
        }

        /* Form Styles */
        .cadastro-form {
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

        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin: 1rem 0;
        }

        .custom-checkbox {
            width: 18px;
            height: 18px;
            background: #22c55e;
            border: 1px solid #22c55e;
            border-radius: 4px;
            cursor: pointer;
            position: relative;
            flex-shrink: 0;
            margin-top: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-checkbox input {
            opacity: 0;
            position: absolute;
            width: 100%;
            height: 100%;
            cursor: pointer;
            margin: 0;
            z-index: 2;
        }

        .custom-checkbox::after {
            content: '✓';
            color: white;
            font-size: 11px;
            font-weight: bold;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .custom-checkbox input:not(:checked) + .checkmark {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .custom-checkbox input:not(:checked) ~ .checkmark::after {
            display: none;
        }

        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 4px;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .checkbox-label {
            color: #9ca3af;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .checkbox-label a {
            color: #22c55e;
            text-decoration: none;
        }

        .checkbox-label a:hover {
            text-decoration: underline;
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
            .cadastro-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                max-width: 600px;
            }

            .brand-content {
                display: none !important;
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
            
            .benefits-list {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }
            
            .benefit-item {
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            .cadastro-section {
                padding: 2rem 0;
            }

            .brand-content {
                display: none !important;
            }
            
            .cadastro-container {
                padding: 0 1rem;
            }
            
            .cadastro-card {
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
            
            .benefits-list {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .cadastro-card {
                padding: 1.5rem;
            }
            
            .form-input {
                padding: 0.8rem 0.8rem 0.8rem 2.5rem;
            }

            .brand-content {
                display: none !important;
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

    <section class="cadastro-section">
        <!-- Floating Elements -->
        <div class="floating-elements">
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
        </div>

        <div class="cadastro-container fade-in">
            <!-- Left Section -->
            <div class="left-section">
                <div class="brand-content">
                    <h1 class="brand-title">Comece a ganhar hoje!</h1>
                    <p class="brand-subtitle">
                        Junte-se a milhares de usuários que já estão ganhando 
                        <span class="highlight-text">prêmios incríveis</span> 
                        com a RaspaGreen!
                    </p>
                    
                    <div class="benefits-list">
                        <div class="benefit-item">
                            <div class="benefit-icon">
                                <i class="bi bi-gift"></i>
                            </div>
                            <span>Prêmios de até R$ 15.000</span>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">
                                <i class="bi bi-lightning-charge"></i>
                            </div>
                            <span>PIX instantâneo</span>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <span>Plataforma 100% segura</span>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">
                                <i class="bi bi-clock"></i>
                            </div>
                            <span>Disponível 24/7</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Section -->
            <div class="right-section">
                <div class="cadastro-card">
                    <div class="cadastro-header">
                        <div class="cadastro-icon">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <h2 class="cadastro-title">Cadastre-se</h2>
                        <p class="cadastro-subtitle">
                            Crie sua conta grátis em menos de 1 minuto
                        </p>
                    </div>

                    <form id="cadastroForm" method="POST" class="cadastro-form">
                        <input id="ref" name="ref" type="hidden" value="">
                        
                        <div class="form-group">
                            <div class="input-icon">
                                <i class="bi bi-person"></i>
                            </div>
                            <input type="text" name="nome" class="form-input" placeholder="Nome completo" required>
                        </div>

                        <div class="form-group">
                            <div class="input-icon">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <input type="text" id="telefone" name="telefone" class="form-input" placeholder="(11) 99999-9999" required>
                        </div>

                        <div class="form-group">
                            <div class="input-icon">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <input type="email" name="email" class="form-input" placeholder="seu@email.com" required>
                        </div>

                        <div class="form-group">
                            <div class="input-icon">
                                <i class="bi bi-lock"></i>
                            </div>
                            <input type="password" name="senha" class="form-input" placeholder="Senha segura" required>
                        </div>

                        <div class="checkbox-group">
                            <div class="custom-checkbox">
                                <input id="aceiteTermos" name="aceiteTermos" type="checkbox" checked required>
                                <div class="checkmark"></div>
                            </div>
                            <label for="aceiteTermos" class="checkbox-label">
                                Li e aceito os <a href="/politica" target="_blank">termos e políticas de privacidade</a>
                            </label>
                        </div>

                        <button type="submit" class="submit-btn" id="submitBtn">
                            <i class="bi bi-check-circle"></i>
                            Criar Conta Grátis
                        </button>
                    </form>

                    <div class="form-footer">
                        <div class="divider">
                            <span>ou</span>
                        </div>
                        
                        <p class="footer-text">
                            Já tem uma conta?
                        </p>
                        <a href="/login" class="footer-link">
                            Faça login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include('../inc/footer.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ref parameter handling
            function getRefParam() {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get('ref');
            }

            const refParam = getRefParam();
            if (refParam) {
                localStorage.setItem('ref', refParam);
            }

            const params = new URLSearchParams(window.location.search);
            let refValue = params.get('ref');

            if (!refValue) {
                refValue = localStorage.getItem('ref') || '';
            } else {
                localStorage.setItem('ref', refValue);
            }

            const refInput = document.getElementById('ref');
            if (refInput) refInput.value = refValue;

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

            // Form submission
            const cadastroForm = document.getElementById('cadastroForm');
            const submitBtn = document.getElementById('submitBtn');

            cadastroForm.addEventListener('submit', function(e) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite;"></i> Criando conta...';
                cadastroForm.classList.add('loading');
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

        console.log('%c🎯 RaspaGreen - Cadastro Split Screen', 'color: #22c55e; font-size: 16px; font-weight: bold;');
    </script>
</body>
</html>