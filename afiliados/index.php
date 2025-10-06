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
    $link_indicacao = "https://" . $_SERVER['HTTP_HOST'] . "/cadastro?ref=" . $usuario_id;
    
    $stmt_indicados = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE indicacao = ?");
    $stmt_indicados->execute([$usuario_id]);
    $total_indicados = $stmt_indicados->fetch()['total'];
    
    $stmt_depositos = $pdo->prepare("SELECT SUM(d.valor) as total 
                                    FROM depositos d
                                    JOIN usuarios u ON d.user_id = u.id
                                    WHERE u.indicacao = ? AND d.status = 'PAID'");
    $stmt_depositos->execute([$usuario_id]);
    $total_depositado = $stmt_depositos->fetch()['total'] ?? 0;
    
    // Buscar comissões CPA (se a tabela existir)
    $total_comissoes_cpa = 0;
    try {
        $stmt_comissoes_cpa = $pdo->prepare("SELECT SUM(valor) as total FROM transacoes_afiliados WHERE afiliado_id = ?");
        $stmt_comissoes_cpa->execute([$usuario_id]);
        $total_comissoes_cpa = $stmt_comissoes_cpa->fetch()['total'] ?? 0;
    } catch (PDOException $e) {
        // Tabela transacoes_afiliados não existe
        $total_comissoes_cpa = 0;
    }
    
    // Buscar comissões RevShare (separando ganhos, perdas e saldo líquido)
    $total_comissoes_revshare = 0;
    $total_deducoes_revshare = 0;
    $saldo_revshare_liquido = 0;
    try {
        // Comissões ganhas (apenas valores positivos)
        $stmt_comissoes_revshare = $pdo->prepare("SELECT SUM(valor_revshare) as total FROM historico_revshare WHERE afiliado_id = ? AND valor_revshare > 0");
        $stmt_comissoes_revshare->execute([$usuario_id]);
        $total_comissoes_revshare = $stmt_comissoes_revshare->fetch()['total'] ?? 0;
        
        // Deduções (apenas valores negativos, convertidos para positivo para exibição)
        $stmt_deducoes = $pdo->prepare("SELECT SUM(ABS(valor_revshare)) as total FROM historico_revshare WHERE afiliado_id = ? AND valor_revshare < 0");
        $stmt_deducoes->execute([$usuario_id]);
        $total_deducoes_revshare = $stmt_deducoes->fetch()['total'] ?? 0;
        
        // Saldo líquido (ganhos - perdas)
        $stmt_saldo_liquido = $pdo->prepare("SELECT SUM(valor_revshare) as total FROM historico_revshare WHERE afiliado_id = ?");
        $stmt_saldo_liquido->execute([$usuario_id]);
        $saldo_revshare_liquido = $stmt_saldo_liquido->fetch()['total'] ?? 0;
        
    } catch (PDOException $e) {
        // Tabela historico_revshare não existe ainda
        $total_comissoes_revshare = 0;
        $total_deducoes_revshare = 0;
        $saldo_revshare_liquido = 0;
    }
    
    // Total de comissões (CPA + RevShare - apenas valores ganhos, não o saldo líquido)
    $total_comissoes = $total_comissoes_cpa + $total_comissoes_revshare;
    
    $stmt_lista = $pdo->prepare("SELECT u.id, u.nome, u.email, u.created_at,
                                (SELECT SUM(valor) FROM depositos WHERE user_id = u.id AND status = 'PAID') as total_depositado
                                FROM usuarios u
                                WHERE u.indicacao = ?
                                ORDER BY u.created_at DESC");
    $stmt_lista->execute([$usuario_id]);
    $indicados = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao carregar dados de afiliado'];
    $total_indicados = 0;
    $total_depositado = 0;
    $total_comissoes = 0;
    $total_comissoes_cpa = 0;
    $total_comissoes_revshare = 0;
    $total_deducoes_revshare = 0;
    $saldo_revshare_liquido = 0;
    $indicados = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite; ?> - Programa de Afiliados</title>
        <?php 
    // Se as variáveis não estiverem definidas, buscar do banco
    if (!isset($faviconSite)) {
        try {
            $stmt = $pdo->prepare("SELECT favicon FROM config WHERE id = 1 LIMIT 1");
            $stmt->execute();
            $config_favicon = $stmt->fetch(PDO::FETCH_ASSOC);
            $faviconSite = $config_favicon['favicon'] ?? null;
            
            // Se $nomeSite não estiver definido, buscar também
            if (!isset($nomeSite)) {
                $stmt = $pdo->prepare("SELECT nome_site FROM config WHERE id = 1 LIMIT 1");
                $stmt->execute();
                $config_nome = $stmt->fetch(PDO::FETCH_ASSOC);
                $nomeSite = $config_nome['nome_site'] ?? 'Raspadinha';
            }
        } catch (PDOException $e) {
            $faviconSite = null;
            $nomeSite = $nomeSite ?? 'Raspadinha';
        }
    }
    ?>
    <?php if ($faviconSite && file_exists($_SERVER['DOCUMENT_ROOT'] . $faviconSite)): ?>
        <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($faviconSite) ?>"/>
        <link rel="shortcut icon" href="<?= htmlspecialchars($faviconSite) ?>"/>
        <link rel="apple-touch-icon" href="<?= htmlspecialchars($faviconSite) ?>"/>
    <?php else: ?>
        <link rel="icon" href="data:image/svg+xml,<?= urlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#22c55e"/><text x="50" y="50" text-anchor="middle" dominant-baseline="middle" fill="white" font-family="Arial" font-size="40" font-weight="bold">' . strtoupper(substr($nomeSite, 0, 1)) . '</text></svg>') ?>"/>
    <?php endif; ?>
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
        .afiliados-section {
            margin-top: 100px;
            padding: 4rem 0;
            background: #0a0a0a;
            min-height: calc(100vh - 200px);
        }

        .afiliados-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Header */
        .page-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 900;
            color: white;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff, #9ca3af);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: #6b7280;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .highlight-text {
            color: #22c55e;
            font-weight: 700;
        }

        /* Main Card */
        .main-card {
            background: rgba(20, 20, 20, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 3rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        .main-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), transparent);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .card-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            z-index: 2;
        }

        .card-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 20px;
            margin: 0 auto 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3);
        }

        .card-title {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }

        .card-description {
            color: #9ca3af;
            font-size: 1.1rem;
        }

        /* Link Section */
        .link-section {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 3rem;
            position: relative;
            z-index: 2;
        }

        .link-title {
            color: #22c55e;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .link-input-group {
            display: flex;
            gap: 1rem;
            align-items: stretch;
        }

        .link-input-wrapper {
            flex: 1;
            position: relative;
        }

        .link-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 0.9rem;
            font-family: monospace;
            transition: all 0.3s ease;
        }

        .link-input:focus {
            outline: none;
            border-color: #22c55e;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }

        .link-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #22c55e;
            font-size: 1rem;
        }

        .copy-btn {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            box-shadow: 0 4px 16px rgba(34, 197, 94, 0.3);
        }

        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(34, 197, 94, 0.4);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
            position: relative;
            z-index: 2;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
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

        .stat-card.indicados::before { background: #22c55e; }
        .stat-card.depositos::before { background: #3b82f6; }
        .stat-card.comissoes::before { background: #a855f7; }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .stat-info h3 {
            color: #9ca3af;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            line-height: 1;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.indicados { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .stat-icon.depositos { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .stat-icon.comissoes { background: rgba(168, 85, 247, 0.2); color: #a855f7; }

        .stat-footer {
            color: #6b7280;
            font-size: 0.85rem;
            margin-top: 1rem;
        }

        /* Detalhamento das comissões */
        .commission-breakdown {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .commission-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
        }

        .commission-label {
            color: #9ca3af;
        }

        .commission-value {
            color: #a855f7;
            font-weight: 600;
        }

        /* Indicados Section */
        .indicados-section {
            position: relative;
            z-index: 2;
        }

        .section-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 2rem;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: white;
            margin-bottom: 1rem;
        }

        .empty-description {
            font-size: 1rem;
            line-height: 1.6;
            max-width: 400px;
            margin: 0 auto;
        }

        /* Indicados Cards */
        .indicados-grid {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .indicado-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .indicado-card:hover {
            transform: translateY(-2px);
            border-color: rgba(34, 197, 94, 0.3);
            box-shadow: 0 8px 32px rgba(34, 197, 94, 0.1);
        }

        .indicado-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #22c55e;
        }

        .indicado-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            align-items: center;
        }

        .indicado-field {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .field-label {
            color: #9ca3af;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .field-value {
            color: white;
            font-weight: 600;
            font-size: 1rem;
        }

        .field-value.email {
            font-family: monospace;
            font-size: 0.9rem;
            word-break: break-all;
        }

        .field-value.money {
            color: #22c55e;
            font-weight: 700;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .afiliados-container {
                padding: 0 1rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .main-card {
                padding: 2rem 1.5rem;
                border-radius: 20px;
            }
            
            .link-input-group {
                flex-direction: column;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .indicado-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .main-card {
                padding: 1.5rem 1rem;
            }
            
            .link-section {
                padding: 1.5rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .indicado-card {
                padding: 1.5rem;
            }
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

        .stats-grid .stat-card {
            opacity: 0;
            animation: fadeIn 0.6s ease-out forwards;
        }

        .stats-grid .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stats-grid .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stats-grid .stat-card:nth-child(3) { animation-delay: 0.3s; }

        .success-animation {
            animation: successPulse 0.6s ease-out;
        }

        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <?php include('../inc/header.php'); ?>
    <?php include('../components/modals.php'); ?>

    <section class="afiliados-section">
        <div class="afiliados-container">
            <!-- Page Header -->
            <div class="page-header fade-in">
                <h1 class="page-title">Programa de Afiliados</h1>
                <p class="page-subtitle">
                    Ganhe <span class="highlight-text">comissões</span> indicando amigos para a <?php echo $nomeSite;?>. 
                    Quanto mais eles jogarem, mais você ganha!
                </p>
            </div>

            <!-- Main Card -->
            <div class="main-card">
                <!-- Card Header -->
                <div class="card-header">
                    <div class="card-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h2 class="card-title">Área do Afiliado</h2>
                    <p class="card-description">
                        Compartilhe seu link e ganhe comissões por cada indicação
                    </p>
                </div>

                <!-- Link Section -->
                <div class="link-section">
                    <h3 class="link-title">
                        <i class="bi bi-link-45deg"></i>
                        Seu Link de Indicação
                    </h3>
                    
                    <div class="link-input-group">
                        <div class="link-input-wrapper">
                            <i class="bi bi-link link-icon"></i>
                            <input type="text" 
                                   id="linkIndicacao" 
                                   class="link-input"
                                   value="<?= $link_indicacao ?>" 
                                   readonly>
                        </div>
                        
                        <button onclick="copiarLink()" class="copy-btn" id="copyBtn">
                            <i class="bi bi-clipboard"></i>
                            Copiar Link
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card indicados">
                        <div class="stat-header">
                            <div class="stat-info">
                                <h3>Indicados</h3>
                                <div class="stat-value"><?= $total_indicados ?></div>
                            </div>
                            <div class="stat-icon indicados">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                        <div class="stat-footer">
                            Pessoas que você indicou
                        </div>
                    </div>

                    <div class="stat-card depositos">
                        <div class="stat-header">
                            <div class="stat-info">
                                <h3>Total Depositado</h3>
                                <div class="stat-value">R$ <?= number_format($total_depositado, 0, ',', '.') ?></div>
                            </div>
                            <div class="stat-icon depositos">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </div>
                        <div class="stat-footer">
                            Por seus indicados
                        </div>
                    </div>

                    <div class="stat-card comissoes">
                        <div class="stat-header">
                            <div class="stat-info">
                                <h3>Suas Comissões</h3>
                                <div class="stat-value">R$ <?= number_format($total_comissoes, 2, ',', '.') ?></div>
                            </div>
                            <div class="stat-icon comissoes">
                                <i class="bi bi-wallet2"></i>
                            </div>
                        </div>
                        
                        <?php if ($total_comissoes_cpa > 0 || $total_comissoes_revshare > 0 || $total_deducoes_revshare > 0): ?>
                        <div class="commission-breakdown">
                            <?php if ($total_comissoes_cpa > 0): ?>
                            <div class="commission-item">
                                <span class="commission-label">CPA (Cadastros):</span>
                                <span class="commission-value">R$ <?= number_format($total_comissoes_cpa, 2, ',', '.') ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($total_comissoes_revshare > 0): ?>
                            <div class="commission-item">
                                <span class="commission-label">RevShare (Ganhos):</span>
                                <span class="commission-value">R$ <?= number_format($total_comissoes_revshare, 2, ',', '.') ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($total_deducoes_revshare > 0): ?>
                            <div class="commission-item">
                                <span class="commission-label">Deduções (Perdas):</span>
                                <span class="commission-value" style="color: #ef4444;">-R$ <?= number_format($total_deducoes_revshare, 2, ',', '.') ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($total_comissoes_revshare > 0 || $total_deducoes_revshare > 0): ?>
                            <hr style="border-color: rgba(255,255,255,0.1); margin: 0.5rem 0;">
                            <div class="commission-item">
                                <span class="commission-label"><strong>Saldo RevShare:</strong></span>
                                <span class="commission-value" style="color: <?= $saldo_revshare_liquido >= 0 ? '#22c55e' : '#ef4444' ?>;">
                                    R$ <?= number_format($saldo_revshare_liquido, 2, ',', '.') ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="stat-footer">
                            Total de comissões ganhas
                        </div>
                    </div>
                </div>

                <!-- Indicados Section -->
                <div class="indicados-section">
                    <h3 class="section-title">
                        <i class="bi bi-list-ul"></i>
                        Seus Indicados
                    </h3>
                    
                    <?php if (empty($indicados)): ?>
                        <div class="empty-state">
                            <i class="bi bi-people empty-icon"></i>
                            <h4 class="empty-title">Nenhum indicado ainda</h4>
                            <p class="empty-description">
                                Compartilhe seu link de indicação com amigos e familiares para começar a ganhar comissões!
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="indicados-grid">
                            <?php foreach ($indicados as $indicado): ?>
                                <div class="indicado-card">
                                    <div class="indicado-grid">
                                        <div class="indicado-field">
                                            <span class="field-label">Nome</span>
                                            <span class="field-value"><?= htmlspecialchars($indicado['nome']) ?></span>
                                        </div>
                                        
                                        <div class="indicado-field">
                                            <span class="field-label">E-mail</span>
                                            <span class="field-value email"><?= htmlspecialchars($indicado['email']) ?></span>
                                        </div>
                                        
                                        <div class="indicado-field">
                                            <span class="field-label">Cadastro</span>
                                            <span class="field-value"><?= date('d/m/Y', strtotime($indicado['created_at'])) ?></span>
                                        </div>
                                        
                                        <div class="indicado-field">
                                            <span class="field-label">Total Depositado</span>
                                            <span class="field-value money">
                                                R$ <?= number_format($indicado['total_depositado'] ?? 0, 2, ',', '.') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include('../inc/footer.php'); ?>

    <script>
        function copiarLink() {
            const linkInput = document.getElementById('linkIndicacao');
            const copyBtn = document.getElementById('copyBtn');
            
            // Seleciona e copia o texto
            linkInput.select();
            linkInput.setSelectionRange(0, 99999); // Para mobile
            
            try {
                document.execCommand('copy');
                
                // Feedback visual
                copyBtn.innerHTML = '<i class="bi bi-check-circle"></i> Copiado!';
                copyBtn.classList.add('success-animation');
                
                // Notificação
                Notiflix.Notify.success('Link copiado para a área de transferência!');
                
                // Restaura o botão após 2 segundos
                setTimeout(() => {
                    copyBtn.innerHTML = '<i class="bi bi-clipboard"></i> Copiar Link';
                    copyBtn.classList.remove('success-animation');
                }, 2000);
                
            } catch (err) {
                Notiflix.Notify.failure('Erro ao copiar o link');
                console.error('Erro ao copiar:', err);
            }
        }

        // Clipboard API moderna (fallback)
        async function copiarLinkModerno() {
            const linkInput = document.getElementById('linkIndicacao');
            
            try {
                await navigator.clipboard.writeText(linkInput.value);
                Notiflix.Notify.success('Link copiado!');
            } catch (err) {
                // Fallback para método antigo
                copiarLink();
            }
        }

        // Detecta se suporta Clipboard API
        if (navigator.clipboard) {
            document.querySelector('.copy-btn').onclick = copiarLinkModerno;
        }

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
            }
        });

        // Console log
        document.addEventListener('DOMContentLoaded', function() {
            console.log('%c💰 Programa de Afiliados carregado!', 'color: #22c55e; font-size: 16px; font-weight: bold;');
            console.log(`Indicados: ${<?= $total_indicados ?>}, Comissões Total: R$ ${<?= $total_comissoes ?>}`);
            console.log(`CPA: R$ ${<?= $total_comissoes_cpa ?>}, RevShare: R$ ${<?= $total_comissoes_revshare ?>}`);
            console.log(`Deduções: R$ ${<?= $total_deducoes_revshare ?>}, Saldo RevShare: R$ ${<?= $saldo_revshare_liquido ?>}`);
        });
    </script>
</body>
</html>