<?php
@session_start();
require_once '../conexao.php';

// Ordenar por valor decrescente (maior para menor) primeiro
$sql = "
    SELECT r.*, 
           MAX(p.valor) AS maior_premio
      FROM raspadinhas r
 LEFT JOIN raspadinha_premios p ON p.raspadinha_id = r.id
  GROUP BY r.id
  ORDER BY r.valor DESC, r.created_at DESC
";
$cartelas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite;?> - Raspadinhas</title>
    
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
        /* Page Specific Styles */
        .cartelas-section {
            margin-top: 100px;
            padding: 4rem 0;
            background: #0a0a0a;
            min-height: calc(100vh - 200px);
        }

        .cartelas-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

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

        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 2rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: #22c55e;
            display: block;
        }

        .stat-label {
            color: #9ca3af;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .cartelas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .cartela-card {
            background: rgba(20, 20, 20, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
            text-decoration: none;
            color: inherit;
            backdrop-filter: blur(20px);
        }

        .cartela-card:hover {
            transform: translateY(-8px);
            border-color: rgba(34, 197, 94, 0.3);
            box-shadow: 0 20px 60px rgba(34, 197, 94, 0.2);
        }

        .cartela-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .cartela-card:hover .cartela-image {
            transform: scale(1.05);
        }

        .cartela-content {
            padding: 1.5rem;
            position: relative;
        }

        .price-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 4px 16px rgba(34, 197, 94, 0.3);
        }

        .cartela-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .cartela-description {
            color: #9ca3af;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .prize-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .prize-label {
            color: #9ca3af;
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }

        .prize-value {
            color: #22c55e;
            font-weight: 800;
            font-size: 1.1rem;
        }

        .play-button {
            width: 100%;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .play-button:hover {
            background: linear-gradient(135deg, #16a34a, #15803d);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(34, 197, 94, 0.4);
        }

        .cartela-features {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .feature-tag {
            background: rgba(99, 102, 241, 0.1);
            color: #a5b4fc;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .loading-skeleton {
            background: linear-gradient(90deg, rgba(255,255,255,0.1) 25%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0.1) 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Improved Filter Bar */
        .filter-bar {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: linear-gradient(145deg, #1e1e1e, #2a2a2a);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #9ca3af;
            padding: 0.75rem 1.25rem;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(20px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .filter-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            transition: left 0.3s ease;
            z-index: -1;
        }

        .filter-btn:hover {
            color: #ffffff;
            border-color: rgba(34, 197, 94, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.2);
        }

        .filter-btn:hover::before {
            left: 0;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-color: #22c55e;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
        }

        .filter-btn.active::before {
            left: 0;
        }

        .filter-btn i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }

        .filter-btn:hover i,
        .filter-btn.active i {
            transform: scale(1.1);
        }

        /* Filter button animations */
        .filter-btn.low {
            --hover-color: #22c55e;
        }

        .filter-btn.medium {
            --hover-color: #f59e0b;
        }

        .filter-btn.high {
            --hover-color: #ef4444;
        }

        .filter-btn.medium::before {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .filter-btn.high::before {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .filter-btn.medium.active {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-color: #f59e0b;
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
        }

        .filter-btn.high.active {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-color: #ef4444;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .stats-bar {
                gap: 1.5rem;
                flex-wrap: wrap;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .cartelas-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .cartela-content {
                padding: 1rem;
            }
            
            .filter-bar {
                gap: 0.5rem;
            }
            
            .filter-btn {
                padding: 0.6rem 1rem;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .cartelas-container {
                padding: 0 1rem;
            }
            
            .cartelas-grid {
                grid-template-columns: 1fr;
            }

            .filter-bar {
                flex-direction: column;
                align-items: center;
            }

            .filter-btn {
                width: 200px;
                justify-content: center;
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

        .stagger-animation .cartela-card {
            opacity: 0;
            animation: fadeIn 0.6s ease-out forwards;
        }

        .stagger-animation .cartela-card:nth-child(1) { animation-delay: 0.1s; }
        .stagger-animation .cartela-card:nth-child(2) { animation-delay: 0.2s; }
        .stagger-animation .cartela-card:nth-child(3) { animation-delay: 0.3s; }
        .stagger-animation .cartela-card:nth-child(4) { animation-delay: 0.4s; }
        .stagger-animation .cartela-card:nth-child(5) { animation-delay: 0.5s; }
        .stagger-animation .cartela-card:nth-child(6) { animation-delay: 0.6s; }

        /* Sparkle animation for premium buttons */
        @keyframes sparkle {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; }
            50% { transform: scale(1.05) rotate(2deg); opacity: 0.9; }
        }

        .filter-btn.high.active {
            animation: sparkle 2s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <?php include('../inc/header.php'); ?>
    <?php include('../components/modals.php'); ?>

    <section class="cartelas-section">
        <div class="cartelas-container">
            <!-- Page Header -->
            <div class="page-header fade-in">
                <h1 class="page-title">Escolha sua Raspadinha</h1>
                <p class="page-subtitle">
                    Centenas de prêmios esperando por você! Raspe e ganhe na hora com PIX instantâneo.
                </p>
                
                <div class="stats-bar">
                    <div class="stat-item">
                        <span class="stat-number"><?= count($cartelas); ?></span>
                        <span class="stat-label">Raspadinhas</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">R$ <?= number_format(array_sum(array_column($cartelas, 'maior_premio')), 0, ',', '.'); ?></span>
                        <span class="stat-label">Em Prêmios</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">Disponível</span>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced Filter Bar -->
            <div class="filter-bar fade-in">
                <button class="filter-btn active" data-filter="all">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                    <span>Todas as Raspadinhas</span>
                </button>
                <button class="filter-btn low" data-filter="low">
                    <i class="bi bi-coin"></i>
                    <span>Até R$ 10</span>
                </button>
                <button class="filter-btn medium" data-filter="medium">
                    <i class="bi bi-cash-stack"></i>
                    <span>R$ 10 - R$ 50</span>
                </button>
                <button class="filter-btn high" data-filter="high">
                    <i class="bi bi-gem"></i>
                    <span>Acima de R$ 50</span>
                </button>
            </div>

            <!-- Cartelas Grid -->
            <?php if (empty($cartelas)): ?>
                <div class="empty-state">
                    <i class="bi bi-grid-3x3-gap empty-icon"></i>
                    <h3 style="color: white; margin-bottom: 1rem;">Nenhuma raspadinha disponível</h3>
                    <p>Novas raspadinhas em breve! Fique atento às atualizações.</p>
                </div>
            <?php else: ?>
                <div class="cartelas-grid stagger-animation" id="cartelasGrid">
                    <?php foreach ($cartelas as $c): ?>
                        <a href="/raspadinhas/show.php?id=<?= $c['id']; ?>" 
                           class="cartela-card" 
                           data-price="<?= $c['valor']; ?>"
                           data-aos="fade-up">
                            
                            <div style="position: relative; overflow: hidden;">
                                <img src="<?= htmlspecialchars($c['banner']); ?>"
                                     alt="Banner <?= htmlspecialchars($c['nome']); ?>"
                                     class="cartela-image" 
                                     loading="lazy"
                                     onerror="this.src='/assets/img/placeholder-raspadinha.jpg'">
                                
                                <div class="price-badge">
                                    <i class="bi bi-tag-fill"></i>
                                    R$ <?= number_format($c['valor'], 2, ',', '.'); ?>
                                </div>
                            </div>

                            <div class="cartela-content">
                                <div class="cartela-features">
                                    <span class="feature-tag">
                                        <i class="bi bi-lightning-fill"></i>
                                        PIX Instantâneo
                                    </span>
                                    <?php if($c['maior_premio'] >= 1000): ?>
                                        <span class="feature-tag">
                                            <i class="bi bi-star-fill"></i>
                                            Premium
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h2 class="cartela-title">
                                    <?= htmlspecialchars($c['nome']); ?>
                                </h2>
                                
                                <p class="cartela-description">
                                    <?= htmlspecialchars($c['descricao']); ?>
                                </p>

                                <div class="prize-info">
                                    <div>
                                        <div class="prize-label">Prêmio máximo</div>
                                        <div class="prize-value">
                                            <i class="bi bi-trophy-fill"></i>
                                            R$ <?= number_format($c['maior_premio'], 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div class="prize-label">Via PIX</div>
                                        <div style="color: #22c55e; font-size: 0.9rem;">
                                            <i class="bi bi-check-circle-fill"></i>
                                            Instantâneo
                                        </div>
                                    </div>
                                </div>

                                <div class="play-button">
                                    <i class="bi bi-play-circle-fill"></i>
                                    Jogar Agora
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include('../inc/footer.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter functionality
            const filterBtns = document.querySelectorAll('.filter-btn');
            const cartelasGrid = document.getElementById('cartelasGrid');
            const cartelas = document.querySelectorAll('.cartela-card');

            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    // Update active button
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');

                    const filter = btn.dataset.filter;
                    
                    cartelas.forEach(cartela => {
                        const price = parseFloat(cartela.dataset.price);
                        let show = false;

                        switch(filter) {
                            case 'all':
                                show = true;
                                break;
                            case 'low':
                                show = price <= 10;
                                break;
                            case 'medium':
                                show = price > 10 && price <= 50;
                                break;
                            case 'high':
                                show = price > 50;
                                break;
                        }

                        if (show) {
                            cartela.style.display = 'block';
                            cartela.style.animation = 'fadeIn 0.5s ease-out forwards';
                        } else {
                            cartela.style.display = 'none';
                        }
                    });

                    // Add click feedback
                    btn.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        btn.style.transform = '';
                    }, 150);
                });
            });

            // Image lazy loading fallback
            const images = document.querySelectorAll('.cartela-image');
            images.forEach(img => {
                img.addEventListener('error', function() {
                    this.src = '/assets/img/placeholder-raspadinha.jpg';
                });
            });

            // Smooth scroll animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Performance optimization: only observe visible cards
            const visibleCards = Array.from(cartelas).slice(0, 6);
            visibleCards.forEach(card => observer.observe(card));

            // Add hover sound effect (optional)
            cartelas.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    // Optional: Add subtle hover sound
                    // new Audio('/assets/sounds/hover.mp3').play().catch(() => {});
                });
            });

            // Enhanced button interactions
            filterBtns.forEach(btn => {
                btn.addEventListener('mouseenter', () => {
                    if (!btn.classList.contains('active')) {
                        btn.style.transform = 'translateY(-3px) scale(1.02)';
                    }
                });

                btn.addEventListener('mouseleave', () => {
                    if (!btn.classList.contains('active')) {
                        btn.style.transform = '';
                    }
                });
            });

            console.log('%c🎮 Raspadinhas carregadas!', 'color: #22c55e; font-size: 16px; font-weight: bold;');
            console.log(`Total de ${cartelas.length} raspadinhas disponíveis`);
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
            }
        });
    </script>
</body>
</html>