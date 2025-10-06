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
$porPagina = 10; // apostas por página
$paginaAtual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($paginaAtual - 1) * $porPagina;

// Obter total de apostas
try {
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :user_id");
    $stmtTotal->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmtTotal->execute();
    $totalApostas = $stmtTotal->fetchColumn();
    $totalPaginas = ceil($totalApostas / $porPagina);
} catch (PDOException $e) {
    $totalApostas = 0;
    $totalPaginas = 1;
}

// Buscar apostas paginadas
try {
    $stmt = $pdo->prepare("
        SELECT o.created_at, o.resultado, o.valor_ganho, r.nome, r.valor AS valor_apostado
        FROM orders o
        JOIN raspadinhas r ON o.raspadinha_id = r.id
        WHERE o.user_id = :user_id
        ORDER BY o.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $porPagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $apostas = [];
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao carregar apostas'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite;?> - Minhas Apostas</title>
    
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
        .apostas-section {
            margin-top: 100px;
            padding: 4rem 0;
            background: #0a0a0a;
            min-height: calc(100vh - 200px);
        }

        .apostas-container {
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: rgba(34, 197, 94, 0.4);
            transform: translateY(-2px);
        }

        .stat-icon {
            font-size: 2rem;
            color: #22c55e;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #9ca3af;
            font-size: 0.9rem;
        }

        /* Main Container */
        .main-container {
            background: rgba(20, 20, 20, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 2rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .main-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Empty State */
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

        /* Bet Items */
        .bet-item {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(34, 197, 94, 0.1);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .bet-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .bet-item.win::before {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            opacity: 1;
        }

        .bet-item.lose::before {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            opacity: 1;
        }

        .bet-item:hover {
            border-color: rgba(34, 197, 94, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .bet-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .bet-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #9ca3af;
            font-size: 0.9rem;
        }

        .bet-date i {
            color: #22c55e;
        }

        .bet-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bet-status.win {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(16, 163, 74, 0.1));
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .bet-status.lose {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.1));
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .bet-content {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: center;
        }

        .bet-details {
            color: #e5e7eb;
        }

        .bet-game {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .bet-values {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            font-size: 0.9rem;
        }

        .bet-value {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .bet-value-label {
            color: #9ca3af;
        }

        .bet-value-amount {
            font-weight: 600;
        }

        .bet-value-amount.win {
            color: #22c55e;
        }

        .bet-value-amount.lose {
            color: #ef4444;
        }

        .bet-summary {
            text-align: right;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .bet-summary-value {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .bet-summary-label {
            color: #9ca3af;
            font-size: 0.8rem;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .pagination-item {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .pagination-item:hover {
            transform: translateY(-1px);
        }

        .pagination-item.active {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border-color: rgba(34, 197, 94, 0.3);
            box-shadow: 0 4px 16px rgba(34, 197, 94, 0.2);
        }

        .pagination-item:not(.active) {
            background: rgba(0, 0, 0, 0.3);
            color: #9ca3af;
        }

        .pagination-item:not(.active):hover {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            border-color: rgba(34, 197, 94, 0.2);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .apostas-container {
                padding: 0 1rem;
            }
            
            .header-title {
                font-size: 2rem;
            }
            
            .main-container {
                padding: 1.5rem;
            }
            
            .bet-content {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .bet-summary {
                text-align: center;
            }
            
            .bet-values {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            .header-title {
                font-size: 1.8rem;
            }
            
            .bet-header {
                flex-direction: column;
                gap: 0.5rem;
                align-items: stretch;
            }
            
            .bet-status {
                align-self: flex-end;
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

    <section class="apostas-section">
        <div class="apostas-container">
            <!-- Header Card -->
            <div class="header-card">
                <div class="header-icon">
                    <i class="bi bi-dice-5"></i>
                </div>
                <h1 class="header-title">Minhas Apostas</h1>
                <p class="header-subtitle">Acompanhe seu histórico de jogos e resultados</p>
            </div>

            <!-- Main Container -->
            <div class="main-container">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-collection"></i>
                        </div>
                        <div class="stat-value"><?= $totalApostas ?></div>
                        <div class="stat-label">Total de Apostas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="stat-value"><?= $paginaAtual ?>/<?= $totalPaginas ?></div>
                        <div class="stat-label">Página Atual</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-grid-3x3"></i>
                        </div>
                        <div class="stat-value"><?= count($apostas) ?></div>
                        <div class="stat-label">Nesta Página</div>
                    </div>
                </div>

                <h2 class="main-title">
                    <i class="bi bi-trophy"></i>
                    Histórico de Jogos
                </h2>

                <?php if (empty($apostas)): ?>
                    <div class="empty-state">
                        <i class="bi bi-dice-1"></i>
                        <h3>Nenhuma aposta encontrada</h3>
                        <p>Quando você jogar uma raspadinha, ela aparecerá aqui</p>
                    </div>
                <?php else: ?>
                    <div class="bets-list">
                        <?php foreach ($apostas as $aposta): ?>
                            <?php
                                $data = date('d/m/Y H:i', strtotime($aposta['created_at']));
                                $isWin = ($aposta['resultado'] === 'gain');
                                $status = $isWin ? 'GANHOU' : 'PERDEU';
                                $statusClass = $isWin ? 'win' : 'lose';
                                $valorGanho = number_format($aposta['valor_ganho'], 2, ',', '.');
                                $valorApostado = number_format($aposta['valor_apostado'], 2, ',', '.');
                            ?>
                            <div class="bet-item <?= $statusClass ?>">
                                <div class="bet-header">
                                    <div class="bet-date">
                                        <i class="bi bi-calendar-event"></i>
                                        <span><?= $data ?></span>
                                    </div>
                                    <div class="bet-status <?= $statusClass ?>">
                                        <i class="bi bi-<?= $isWin ? 'trophy-fill' : 'x-circle-fill' ?>"></i>
                                        <?= $status ?>
                                    </div>
                                </div>
                                
                                <div class="bet-content">
                                    <div class="bet-details">
                                        <div class="bet-game">
                                            <i class="bi bi-gem"></i>
                                            <?= htmlspecialchars($aposta['nome']) ?>
                                        </div>
                                        <div class="bet-values">
                                            <div class="bet-value">
                                                <span class="bet-value-label">Valor Apostado:</span>
                                                <span class="bet-value-amount">R$ <?= $valorApostado ?></span>
                                            </div>
                                            <div class="bet-value">
                                                <span class="bet-value-label">Valor Ganho:</span>
                                                <span class="bet-value-amount <?= $statusClass ?>">R$ <?= $valorGanho ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="bet-summary">
                                        <div class="bet-summary-value <?= $statusClass ?>">
                                            <?= $isWin ? '+' : '' ?>R$ <?= number_format($aposta['valor_ganho'] - $aposta['valor_apostado'], 2, ',', '.') ?>
                                        </div>
                                        <div class="bet-summary-label">
                                            <?= $isWin ? 'Lucro' : 'Perda' ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPaginas > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <a href="?pagina=<?= $i ?>" 
                                   class="pagination-item <?= $i == $paginaAtual ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include('../inc/footer.php'); ?>
    <?php include('../components/modals.php'); ?>

    <script>
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('%c🎲 Apostas carregadas!', 'color: #22c55e; font-size: 16px; font-weight: bold;');
            
            // Add hover effects to bet items
            document.querySelectorAll('.bet-item').forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Add click effect to pagination
            document.querySelectorAll('.pagination-item').forEach(item => {
                item.addEventListener('click', function() {
                    if (!this.classList.contains('active')) {
                        this.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 150);
                    }
                });
            });
        });
    </script>
</body>
</html>