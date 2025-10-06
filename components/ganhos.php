<?php
// Buscar prêmios do banco de dados
try {
    $stmt = $pdo->query("
        SELECT rp.nome, rp.icone, rp.valor 
        FROM raspadinha_premios rp 
        JOIN raspadinhas r ON rp.raspadinha_id = r.id 
        WHERE rp.valor > 0 
        ORDER BY RAND() 
        LIMIT 20
    ");
    $premios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $premios = [];
}

// Lista de nomes brasileiros aleatórios
$nomes = [
    'Ana', 'João', 'Maria', 'Pedro', 'Carla', 'Lucas', 'Fernanda', 'Rafael', 
    'Juliana', 'Bruno', 'Camila', 'Diego', 'Beatriz', 'Thiago', 'Larissa', 'André',
    'Patrícia', 'Gustavo', 'Isabela', 'Felipe', 'Amanda', 'Rodrigo', 'Natália', 'Gabriel',
    'Letícia', 'Mateus', 'Carolina', 'Leonardo', 'Vanessa', 'Marcelo', 'Priscila', 'Daniel',
    'Roberta', 'Vinícius', 'Mônica', 'Ricardo', 'Adriana', 'Fábio', 'Cristina', 'Alexandre',
    'Silvia', 'Eduardo', 'Renata', 'Carlos', 'Tatiane', 'Paulo', 'Débora', 'Antônio',
    'Sandra', 'José', 'Márcia', 'Roberto', 'Luciana', 'Marcos', 'Eliane', 'Francisco',
    'Regina', 'Fernando', 'Marta', 'Luiz', 'Denise', 'Sérgio', 'Cláudia', 'Jorge',
    'Vera', 'Raimundo', 'Solange', 'Manoel', 'Rosana', 'Edson', 'Lúcia', 'Wilson',
    'Simone', 'Sebastião', 'Teresa', 'Antônio', 'Aparecida', 'Valdir', 'Fátima', 'João',
    'Cleusa', 'Benedito', 'Rita', 'Nelson', 'Marlene', 'Davi', 'Célia', 'Geraldo',
    'Neusa', 'Ademir', 'Ivone', 'Miguel', 'Irene', 'Waldir', 'Sônia', 'Benedita',
    'Valter', 'Lourdes', 'Reinaldo', 'Terezinha', 'Alcides'
];

// Gerar ganhadores com dados aleatórios
$ganhadores = [];
$valor_total_distribuido = 0;

foreach ($premios as $premio) {
    $nome_aleatorio = $nomes[array_rand($nomes)];
    $tempo_aleatorio = rand(1, 60); // Entre 1 e 60 minutos
    
    $ganhadores[] = [
        'nome' => $nome_aleatorio,
        'premio' => $premio['nome'],
        'icone' => $premio['icone'],
        'valor' => $premio['valor'],
        'tempo' => $tempo_aleatorio
    ];
    
    $valor_total_distribuido += $premio['valor'];
}

// Duplicar para efeito infinito
$ganhadores_duplicados = array_merge($ganhadores, $ganhadores);
?>

<section class="winners-section">
    <div class="winners-container">
        <div class="winners-header">
            <h2 class="winners-title">Últimos Ganhadores</h2>
            <div class="total-distributed">
                <span class="distributed-label">Prêmios Distribuídos</span>
                <span class="distributed-value">R$ <?= number_format($valor_total_distribuido, 2, ',', '.') ?></span>
            </div>
        </div>
        
        <div class="winners-carousel">
            <div class="winners-track">
                <?php foreach ($ganhadores_duplicados as $ganhador): ?>
                    <div class="winner-item">
                        <div class="winner-avatar">
                            <?php if (!empty($ganhador['icone']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $ganhador['icone'])): ?>
                                <img src="<?= htmlspecialchars($ganhador['icone']) ?>" alt="<?= htmlspecialchars($ganhador['premio']) ?>" class="winner-image">
                            <?php else: ?>
                                <div class="winner-placeholder">
                                    <i class="bi bi-gift"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="winner-info">
                            <div class="winner-name">***<?= strtolower(substr($ganhador['nome'], 0, 1)) . str_repeat('*', strlen($ganhador['nome']) - 1) ?></div>
                            <div class="winner-time">há <?= $ganhador['tempo'] ?> min</div>
                        </div>
                        
                        <div class="winner-prize">
                            <div class="prize-value">R$ <?= number_format($ganhador['valor'], 0, ',', '.') ?></div>
                            <div class="prize-type">
                                <?php if ($ganhador['valor'] >= 1000): ?>
                                    <span class="prize-badge premium">PRÊMIO</span>
                                <?php else: ?>
                                    <span class="prize-badge standard">PIX</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<style>
/* Winners Section */
.winners-section {
    padding: 3rem 2rem;
    overflow: hidden;
}

.winners-container {
    max-width: 1400px;
    margin: 0 auto;
}

.winners-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.winners-title {
    font-size: 2rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
}

.total-distributed {
    text-align: right;
}

.distributed-label {
    display: block;
    font-size: 0.9rem;
    color: #9ca3af;
    margin-bottom: 0.25rem;
}

.distributed-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #22c55e;
    display: block;
}

.winners-carousel {
    position: relative;
    overflow: hidden;
    mask: linear-gradient(90deg, transparent, black 5%, black 95%, transparent);
    -webkit-mask: linear-gradient(90deg, transparent, black 5%, black 95%, transparent);
}

.winners-track {
    display: flex;
    gap: 1rem;
    animation: scroll-winners 60s linear infinite;
    width: fit-content;
}

@keyframes scroll-winners {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

.winner-item {
    flex-shrink: 0;
    width: 280px;
    background: linear-gradient(145deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.9) 100%);
    border: 1px solid rgba(34, 197, 94, 0.2);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    backdrop-filter: blur(20px);
    transition: all 0.3s ease;
}

.winner-item:hover {
    border-color: rgba(34, 197, 94, 0.4);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.winner-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(16, 185, 129, 0.1));
    border: 2px solid rgba(34, 197, 94, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}

.winner-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.winner-placeholder {
    color: #22c55e;
    font-size: 1.5rem;
}

.winner-info {
    flex: 1;
    min-width: 0;
}

.winner-name {
    font-size: 1rem;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 0.25rem;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
}

.winner-time {
    font-size: 0.8rem;
    color: #9ca3af;
}

.winner-prize {
    text-align: right;
    flex-shrink: 0;
}

.prize-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #22c55e;
    margin-bottom: 0.25rem;
}

.prize-type {
    display: flex;
    justify-content: flex-end;
}

.prize-badge {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.prize-badge.premium {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.prize-badge.standard {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
}

/* Pause animation on hover */
.winners-carousel:hover .winners-track {
    animation-play-state: paused;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .winners-section {
        padding: 2rem 1rem;
    }
    
    .winners-header {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }
    
    .winners-title {
        font-size: 1.6rem;
    }
    
    .total-distributed {
        text-align: left;
    }
    
    .distributed-value {
        font-size: 1.3rem;
    }
    
    .winner-item {
        width: 250px;
        padding: 1rem;
    }
    
    .winner-avatar {
        width: 40px;
        height: 40px;
    }
    
    .winner-placeholder {
        font-size: 1.2rem;
    }
    
    .winner-name {
        font-size: 0.9rem;
    }
    
    .winner-time {
        font-size: 0.75rem;
    }
    
    .prize-value {
        font-size: 1rem;
    }
    
    .winners-track {
        animation-duration: 45s;
    }
}

@media (max-width: 480px) {
    .winner-item {
        width: 220px;
        padding: 0.875rem;
        gap: 0.75rem;
    }
    
    .winner-avatar {
        width: 35px;
        height: 35px;
    }
    
    .winner-name {
        font-size: 0.85rem;
    }
    
    .prize-value {
        font-size: 0.9rem;
    }
    
    .prize-badge {
        font-size: 0.65rem;
        padding: 0.15rem 0.4rem;
    }
}

/* Loading state for images */
.winner-image {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.winner-image.loaded {
    opacity: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lazy load images
    const images = document.querySelectorAll('.winner-image');
    images.forEach(img => {
        img.onload = function() {
            this.classList.add('loaded');
        };
        
        // If image is already loaded
        if (img.complete) {
            img.classList.add('loaded');
        }
    });
    
    console.log('%c🏆 Últimos Ganhadores carregados!', 'color: #22c55e; font-size: 14px; font-weight: bold;');
});
</script>