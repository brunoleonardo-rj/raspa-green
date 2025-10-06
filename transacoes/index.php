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
    $stmt_depositos = $pdo->prepare("SELECT 
                                    created_at, 
                                    updated_at, 
                                    cpf, 
                                    valor, 
                                    status 
                                    FROM depositos 
                                    WHERE user_id = :user_id
                                    ORDER BY created_at DESC");
    $stmt_depositos->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt_depositos->execute();
    $depositos = $stmt_depositos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $depositos = [];
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao carregar depósitos'];
}

try {
    $stmt_saques = $pdo->prepare("SELECT 
                                created_at, 
                                updated_at, 
                                cpf, 
                                valor, 
                                status 
                                FROM saques 
                                WHERE user_id = :user_id
                                ORDER BY created_at DESC");
    $stmt_saques->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt_saques->execute();
    $saques = $stmt_saques->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $saques = [];
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao carregar saques'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite;?> - Minhas Transações</title>
    
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
        .transactions-section {
            margin-top: 100px;
            padding: 4rem 0;
            background: #0a0a0a;
            min-height: calc(100vh - 200px);
        }

        .transactions-container {
            max-width: 850px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Header Card */
        .header-card {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(16, 163, 74, 0.05));
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .header-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: linear-gradient(45deg, rgba(34, 197, 94, 0.1), transparent);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .header-card::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -50%;
            width: 150px;
            height: 150px;
            background: linear-gradient(45deg, rgba(34, 197, 94, 0.05), transparent);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .header-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(16, 163, 74, 0.1));
            border-radius: 50%;
            border: 1px solid rgba(34, 197, 94, 0.3);
            position: relative;
            z-index: 2;
        }

        .header-icon i {
            font-size: 2rem;
            color: #22c55e;
        }

        .header-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 900;
            text-align: center;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
        }

        .header-subtitle {
            color: #e5e7eb;
            font-size: 1.1rem;
            text-align: center;
            opacity: 0.8;
            position: relative;
            z-index: 2;
        }

        /* Tabs */
        .tabs-container {
            background: rgba(20, 20, 20, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 2rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .tabs-header {
            display: flex;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 16px;
            padding: 0.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .tab-button {
            flex: 1;
            background: transparent;
            border: none;
            color: #9ca3af;
            font-size: 1.1rem;
            font-weight: 600;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .tab-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(34, 197, 94, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .tab-button:hover::before {
            left: 100%;
        }

        .tab-button.active {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            box-shadow: 0 4px 16px rgba(34, 197, 94, 0.3);
        }

        .tab-button.active::before {
            display: none;
        }

        /* Content Area */
        .transactions-content {
            display: none;
        }

        .transactions-content.active {
            display: block;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 4rem;
            color: #22c55e;
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #e5e7eb;
        }

        .empty-state p {
            font-size: 1rem;
            opacity: 0.8;
        }

        /* Transaction Items */
        .transaction-item {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(34, 197, 94, 0.1);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .transaction-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .transaction-item:hover {
            border-color: rgba(34, 197, 94, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .transaction-item:hover::before {
            opacity: 1;
        }

        /* Desktop Layout */
        .transaction-header {
            display: none;
            grid-template-columns: 3fr 2fr 2fr 1.5fr;
            gap: 1rem;
            padding: 1rem 1.5rem;
            background: rgba(34, 197, 94, 0.1);
            border-radius: 12px;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #22c55e;
            font-size: 0.9rem;
        }

        .transaction-row {
            display: grid;
            grid-template-columns: 3fr 2fr 2fr 1.5fr;
            gap: 1rem;
            align-items: center;
        }

        .transaction-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #e5e7eb;
        }

        .transaction-date i {
            color: #22c55e;
            font-size: 0.9rem;
        }

        .transaction-cpf {
            color: #9ca3af;
            font-family: 'Courier New', monospace;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .transaction-cpf:hover {
            color: #22c55e;
        }

        .transaction-amount {
            font-weight: 700;
            color: #22c55e;
            font-size: 1.1rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            justify-self: end;
        }

        .status-badge.approved {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(16, 163, 74, 0.1));
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .status-badge.pending {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(245, 158, 11, 0.1));
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        /* Mobile Layout */
        .transaction-mobile {
            display: block;
        }

        .transaction-mobile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .transaction-mobile-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Responsive */
        @media (min-width: 768px) {
            .transaction-header {
                display: grid;
            }
            
            .transaction-mobile {
                display: none;
            }
            
            .transaction-row {
                display: grid;
            }
        }

        @media (max-width: 768px) {
            .transactions-container {
                padding: 0 1rem;
            }
            
            .header-title {
                font-size: 2rem;
            }
            
            .tabs-container {
                padding: 1.5rem;
            }
            
            .tab-button {
                font-size: 1rem;
                padding: 0.8rem 1rem;
            }
        }

        /* Loading Animation */
        .loading-pulse {
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <?php include('../inc/header.php'); ?>

    <section class="transactions-section">
        <div class="transactions-container">
            <!-- Header Card -->
            <div class="header-card">
                <div class="header-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <h1 class="header-title">Minhas Transações</h1>
                <p class="header-subtitle">Acompanhe seu histórico de depósitos e saques</p>
            </div>

            <!-- Tabs Container -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button id="tabDepositos" class="tab-button active">
                        <i class="bi bi-wallet2"></i>
                        Depósitos
                    </button>
                    <button id="tabSaques" class="tab-button">
                        <i class="bi bi-cash-coin"></i>
                        Saques
                    </button>
                </div>

                <!-- Depósitos Content -->
                <div id="depositosContent" class="transactions-content active">
                    <?php if (empty($depositos)): ?>
                        <div class="empty-state">
                            <i class="bi bi-wallet2"></i>
                            <h3>Nenhum depósito encontrado</h3>
                            <p>Quando você fizer um depósito, ele aparecerá aqui</p>
                        </div>
                    <?php else: ?>
                        <div class="transaction-header">
                            <div><i class="bi bi-calendar3"></i> Data/Hora</div>
                            <div><i class="bi bi-person-badge"></i> CPF</div>
                            <div><i class="bi bi-currency-dollar"></i> Valor</div>
                            <div><i class="bi bi-check-circle"></i> Status</div>
                        </div>

                        <?php foreach ($depositos as $deposito): ?>
                            <div class="transaction-item">
                                <div class="transaction-row">
                                    <div class="transaction-date">
                                        <i class="bi bi-calendar-event"></i>
                                        <span><?= date('d/m/Y H:i', strtotime($deposito['updated_at'])) ?></span>
                                    </div>
                                    <div class="transaction-cpf" onclick="toggleCPF(this)" data-full="<?= htmlspecialchars($deposito['cpf']) ?>">
                                        <?= substr($deposito['cpf'], 0, 3) ?>.***.***-**
                                    </div>
                                    <div class="transaction-amount">
                                        R$ <?= number_format($deposito['valor'], 2, ',', '.') ?>
                                    </div>
                                    <div class="status-badge <?= $deposito['status'] === 'PAID' ? 'approved' : 'pending' ?>">
                                        <i class="bi bi-<?= $deposito['status'] === 'PAID' ? 'check-circle-fill' : 'clock' ?>"></i>
                                        <?= $deposito['status'] === 'PAID' ? 'Aprovado' : 'Pendente' ?>
                                    </div>
                                </div>
                                
                                <!-- Mobile Layout -->
                                <div class="transaction-mobile">
                                    <div class="transaction-mobile-header">
                                        <div class="transaction-date">
                                            <i class="bi bi-calendar-event"></i>
                                            <span><?= date('d/m/Y H:i', strtotime($deposito['updated_at'])) ?></span>
                                        </div>
                                        <div class="transaction-amount">
                                            R$ <?= number_format($deposito['valor'], 2, ',', '.') ?>
                                        </div>
                                    </div>
                                    <div class="transaction-mobile-footer">
                                        <div class="transaction-cpf" onclick="toggleCPF(this)" data-full="<?= htmlspecialchars($deposito['cpf']) ?>">
                                            <?= substr($deposito['cpf'], 0, 3) ?>.***.***-**
                                        </div>
                                        <div class="status-badge <?= $deposito['status'] === 'PAID' ? 'approved' : 'pending' ?>">
                                            <i class="bi bi-<?= $deposito['status'] === 'PAID' ? 'check-circle-fill' : 'clock' ?>"></i>
                                            <?= $deposito['status'] === 'PAID' ? 'Aprovado' : 'Pendente' ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Saques Content -->
                <div id="saquesContent" class="transactions-content">
                    <?php if (empty($saques)): ?>
                        <div class="empty-state">
                            <i class="bi bi-cash-coin"></i>
                            <h3>Nenhum saque encontrado</h3>
                            <p>Quando você fizer um saque, ele aparecerá aqui</p>
                        </div>
                    <?php else: ?>
                        <div class="transaction-header">
                            <div><i class="bi bi-calendar3"></i> Data/Hora</div>
                            <div><i class="bi bi-person-badge"></i> CPF</div>
                            <div><i class="bi bi-currency-dollar"></i> Valor</div>
                            <div><i class="bi bi-check-circle"></i> Status</div>
                        </div>

                        <?php foreach ($saques as $saque): ?>
                            <div class="transaction-item">
                                <div class="transaction-row">
                                    <div class="transaction-date">
                                        <i class="bi bi-calendar-event"></i>
                                        <span><?= date('d/m/Y H:i', strtotime($saque['updated_at'])) ?></span>
                                    </div>
                                    <div class="transaction-cpf" onclick="toggleCPF(this)" data-full="<?= htmlspecialchars($saque['cpf']) ?>">
                                        <?= substr($saque['cpf'], 0, 3) ?>.***.***-**
                                    </div>
                                    <div class="transaction-amount">
                                        R$ <?= number_format($saque['valor'], 2, ',', '.') ?>
                                    </div>
                                    <div class="status-badge <?= $saque['status'] === 'PAID' ? 'approved' : 'pending' ?>">
                                        <i class="bi bi-<?= $saque['status'] === 'PAID' ? 'check-circle-fill' : 'clock' ?>"></i>
                                        <?= $saque['status'] === 'PAID' ? 'Aprovado' : 'Pendente' ?>
                                    </div>
                                </div>
                                
                                <!-- Mobile Layout -->
                                <div class="transaction-mobile">
                                    <div class="transaction-mobile-header">
                                        <div class="transaction-date">
                                            <i class="bi bi-calendar-event"></i>
                                            <span><?= date('d/m/Y H:i', strtotime($saque['updated_at'])) ?></span>
                                        </div>
                                        <div class="transaction-amount">
                                            R$ <?= number_format($saque['valor'], 2, ',', '.') ?>
                                        </div>
                                    </div>
                                    <div class="transaction-mobile-footer">
                                        <div class="transaction-cpf" onclick="toggleCPF(this)" data-full="<?= htmlspecialchars($saque['cpf']) ?>">
                                            <?= substr($saque['cpf'], 0, 3) ?>.***.***-**
                                        </div>
                                        <div class="status-badge <?= $saque['status'] === 'PAID' ? 'approved' : 'pending' ?>">
                                            <i class="bi bi-<?= $saque['status'] === 'PAID' ? 'check-circle-fill' : 'clock' ?>"></i>
                                            <?= $saque['status'] === 'PAID' ? 'Aprovado' : 'Pendente' ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include('../inc/footer.php'); ?>
    <?php include('../components/modals.php'); ?>

    <script>
        // Tab functionality
        document.getElementById('tabDepositos').addEventListener('click', function() {
            switchTab('depositos');
        });

        document.getElementById('tabSaques').addEventListener('click', function() {
            switchTab('saques');
        });

        function switchTab(tabName) {
            // Remove active class from all tabs
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.transactions-content').forEach(content => content.classList.remove('active'));

            // Add active class to selected tab
            document.getElementById(`tab${tabName.charAt(0).toUpperCase() + tabName.slice(1)}`).classList.add('active');
            document.getElementById(`${tabName}Content`).classList.add('active');
        }

        // CPF reveal functionality
        function toggleCPF(element) {
            const fullCPF = element.getAttribute('data-full');
            const maskedCPF = fullCPF.substring(0, 3) + '.***.***-**';
            
            if (element.textContent.includes('*')) {
                element.textContent = fullCPF;
                element.style.color = '#22c55e';
            } else {
                element.textContent = maskedCPF;
                element.style.color = '#9ca3af';
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('%c💳 Transações carregadas!', 'color: #22c55e; font-size: 16px; font-weight: bold;');
            
            // Add hover effects to transaction items
            document.querySelectorAll('.transaction-item').forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>