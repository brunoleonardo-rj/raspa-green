<?php
// Buscar raspadinhas do banco de dados ordenadas por valor (maior para menor)
try {
    $stmt = $pdo->prepare("SELECT * FROM raspadinhas ORDER BY valor DESC, created_at DESC LIMIT 8");
    $stmt->execute();
    $raspadinhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $raspadinhas = [];
}
?>

<section class="raspadinhas-showcase">
    <div class="showcase-container">
        <div class="showcase-header">
            <h2 class="showcase-title">Raspadinhas</h2>
            <div class="showcase-filters">
                <button class="filter-btn active" data-filter="todos">Todos</button>
                <button class="filter-btn" data-filter="dinheiro">Dinheiro</button>
            </div>
        </div>
        
        <div class="raspadinhas-grid">
            <?php if (!empty($raspadinhas)): ?>
                <?php foreach ($raspadinhas as $raspinha): ?>
                    <div class="raspinha-card" data-category="dinheiro">
                        <div class="card-banner">
                            <?php if ($raspinha['banner'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $raspinha['banner'])): ?>
                                <img src="<?= htmlspecialchars($raspinha['banner']) ?>" alt="<?= htmlspecialchars($raspinha['nome']) ?>" class="banner-image">
                            <?php else: ?>
                                <div class="banner-placeholder">
                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Category Badge -->
                            <div class="category-badge <?= rand(0, 1) ? 'dinheiro' : 'produtos' ?>">
                                <?= rand(0, 1) ? 'Dinheiro' : 'Produtos' ?>
                            </div>
                            
                            <!-- Play Button Overlay -->
                            <div class="play-overlay">
                                <div class="play-button">
                                    <i class="bi bi-play-fill"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-content">
                            <h3 class="card-title"><?= htmlspecialchars($raspinha['nome']) ?></h3>
                            <p class="card-description"><?= htmlspecialchars($raspinha['descricao'] ?: 'Raspe e ganhe prêmios incríveis!') ?></p>
                            
                            <div class="card-footer">
                                <div class="card-price">
                                    <span class="price-label">R$</span>
                                    <span class="price-value"><?= number_format($raspinha['valor'], 2, ',', '.') ?></span>
                                </div>
                                
                                <a href="/cartelas/" class="play-btn">
                                    Jogar
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback cards se não houver raspadinhas - também ordenados por valor decrescente -->
                <div class="raspinha-card" data-category="produtos">
                    <div class="card-banner">
                        <div class="banner-placeholder vehicle-theme">
                            <i class="bi bi-car-front"></i>
                        </div>
                        <div class="category-badge produtos">Produtos</div>
                        <div class="play-overlay">
                            <div class="play-button">
                                <i class="bi bi-play-fill"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Saga Motorizada</h3>
                        <p class="card-description">Ganhe até R$ 1.000,00</p>
                        <div class="card-footer">
                            <div class="card-price">
                                <span class="price-label">R$</span>
                                <span class="price-value">15,00</span>
                            </div>
                            <a href="/cartelas" class="play-btn">Jogar</a>
                        </div>
                    </div>
                </div>
                
                <div class="raspinha-card" data-category="produtos">
                    <div class="card-banner">
                        <div class="banner-placeholder fashion-theme">
                            <i class="bi bi-bag-heart"></i>
                        </div>
                        <div class="category-badge produtos">Produtos</div>
                        <div class="play-overlay">
                            <div class="play-button">
                                <i class="bi bi-play-fill"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Mimo caro!</h3>
                        <p class="card-description">Ganhe até R$ 20,00</p>
                        <div class="card-footer">
                            <div class="card-price">
                                <span class="price-label">R$</span>
                                <span class="price-value">15,00</span>
                            </div>
                            <a href="/cartelas" class="play-btn">Jogar</a>
                        </div>
                    </div>
                </div>
                
                <div class="raspinha-card" data-category="produtos">
                    <div class="card-banner">
                        <div class="banner-placeholder tech-theme">
                            <i class="bi bi-laptop"></i>
                        </div>
                        <div class="category-badge produtos">Produtos</div>
                        <div class="play-overlay">
                            <div class="play-button">
                                <i class="bi bi-play-fill"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Sonho de Consumo</h3>
                        <p class="card-description">Eletro, eletrônicos e comp...</p>
                        <div class="card-footer">
                            <div class="card-price">
                                <span class="price-label">R$</span>
                                <span class="price-value">10,00</span>
                            </div>
                            <a href="/cartelas" class="play-btn">Jogar</a>
                        </div>
                    </div>
                </div>
                
                <div class="raspinha-card" data-category="dinheiro">
                    <div class="card-banner">
                        <div class="banner-placeholder money-theme">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="category-badge dinheiro">Dinheiro</div>
                        <div class="play-overlay">
                            <div class="play-button">
                                <i class="bi bi-play-fill"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">PIX na Conta</h3>
                        <p class="card-description">Ganhe até R$ 2.000,00</p>
                        <div class="card-footer">
                            <div class="card-price">
                                <span class="price-label">R$</span>
                                <span class="price-value">5,00</span>
                            </div>
                            <a href="/cartelas" class="play-btn">Jogar</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (count($raspadinhas) > 4): ?>
        <div class="showcase-footer">
            <a href="/cartelas" class="view-all-btn">
                Ver todas as raspadinhas
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.raspadinhas-showcase {
    padding: 4rem 0;
    position: relative;
    overflow: hidden;
}

.raspadinhas-showcase::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%2322c55e" fill-opacity="0.03"><circle cx="30" cy="30" r="2"/></g></svg>') repeat;
    pointer-events: none;
}

.showcase-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
    position: relative;
    z-index: 1;
}

.showcase-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 3rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.showcase-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.showcase-filters {
    display: flex;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 50px;
    padding: 0.25rem;
    backdrop-filter: blur(20px);
}

.filter-btn {
    background: none;
    border: none;
    color: #9ca3af;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.filter-btn:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.05);
}

.filter-btn.active {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
}

.raspadinhas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.raspinha-card {
    background: linear-gradient(145deg, #1e1e1e 0%, #2a2a2a 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.4s ease;
    position: relative;
    transform-style: preserve-3d;
}

.raspinha-card:hover {
    transform: translateY(-8px) rotateX(5deg);
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.3),
        0 8px 20px rgba(34, 197, 94, 0.2);
    border-color: rgba(34, 197, 94, 0.3);
}

.card-banner {
    position: relative;
    height: 150px;
    overflow: hidden;
    background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
}

.banner-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.raspinha-card:hover .banner-image {
    transform: scale(1.1);
}

.banner-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: rgba(255, 255, 255, 0.3);
    background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
}

.banner-placeholder.money-theme {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: rgba(255, 255, 255, 0.8);
}

.banner-placeholder.tech-theme {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: rgba(255, 255, 255, 0.8);
}

.banner-placeholder.vehicle-theme {
    background: linear-gradient(135deg, #10b981, #047857);
    color: rgba(255, 255, 255, 0.8);
}

.banner-placeholder.fashion-theme {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: rgba(255, 255, 255, 0.8);
}

.category-badge {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    backdrop-filter: blur(20px);
    z-index: 2;
}

.category-badge.dinheiro {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
}

.category-badge.produtos {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
}

.play-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: all 0.3s ease;
    backdrop-filter: blur(4px);
}

.raspinha-card:hover .play-overlay {
    opacity: 1;
}

.play-button {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 1.5rem;
    transform: scale(0.8);
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
}

.raspinha-card:hover .play-button {
    transform: scale(1);
}

.card-content {
    padding: 1.5rem;
}

.card-title {
    color: #ffffff;
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    line-height: 1.3;
}

.card-description {
    color: #9ca3af;
    font-size: 0.9rem;
    margin: 0 0 1.5rem 0;
    line-height: 1.4;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.card-price {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
}

.price-label {
    color: #22c55e;
    font-size: 0.9rem;
    font-weight: 600;
}

.price-value {
    color: #22c55e;
    font-size: 1.25rem;
    font-weight: 800;
}

.play-btn {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #ffffff;
    text-decoration: none;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
    white-space: nowrap;
}

.play-btn:hover {
    background: linear-gradient(135deg, #16a34a, #22c55e);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);  
    color: #ffffff;
}

.showcase-footer {
    text-align: center;
}

.view-all-btn {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #ffffff;
    text-decoration: none;
    padding: 1rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
}

.view-all-btn:hover {
    background: linear-gradient(135deg, #16a34a, #15803d);
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(34, 197, 94, 0.4);
    color: #ffffff;
}

/* Filter Animation */
.raspinha-card {
    transition: all 0.4s ease, opacity 0.3s ease, transform 0.3s ease;
}

.raspinha-card.hidden {
    opacity: 0;
    transform: scale(0.8);
    pointer-events: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    .raspadinhas-showcase {
        padding: 2rem 0;
    }
    
    .showcase-container {
        padding: 0 1rem;
    }
    
    .showcase-title {
        font-size: 2rem;
    }
    
    .showcase-header {
        flex-direction: column;
        align-items: flex-start;
        margin-bottom: 2rem;
    }
    
    .raspadinhas-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .filter-btn {
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
    }
    
    .card-content {
        padding: 1rem;
    }
    
    .card-title {
        font-size: 1.1rem;
    }
}

@media (max-width: 480px) {
    .showcase-filters {
        width: 100%;
        justify-content: center;
    }
    
    .raspadinhas-grid {
        grid-template-columns: 1fr;
    }
    
    .card-footer {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }
    
    .play-btn {
        text-align: center;
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality
    const filterBtns = document.querySelectorAll('.filter-btn');
    const cards = document.querySelectorAll('.raspinha-card');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.dataset.filter;
            
            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Filter cards
            cards.forEach(card => {
                const category = card.dataset.category;
                
                if (filter === 'todos' || category === filter) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        });
    });
});
</script>