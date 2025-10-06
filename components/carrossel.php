<?php
// Buscar banners ativos do banco de dados
$query = "SELECT * FROM banners WHERE ativo = 1 ORDER BY ordem ASC";
$banners = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="hero-section">
    <div class="hero-container">
        <div class="hero-banner carousel-container">
            <?php if (!empty($banners)): ?>
                <!-- Container dos slides -->
                <div class="carousel-wrapper">
                    <?php foreach ($banners as $index => $banner): ?>
                        <div class="hero-slide banner-slide <?= $index === 0 ? 'active' : '' ?>" 
                             data-slide="<?= $index ?>" 
                             style="background-image: url('<?= htmlspecialchars($banner['banner_img']) ?>');">
                            <div class="banner-overlay"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($banners) > 1): ?>
                    <!-- NAVEGAÇÃO DO CARROSSEL - ÍCONES CORRIGIDOS -->
                    <button class="carousel-nav prev" onclick="changeSlide(-1)" aria-label="Banner anterior">
                        <span class="nav-arrow">‹</span>
                    </button>
                    <button class="carousel-nav next" onclick="changeSlide(1)" aria-label="Próximo banner">
                        <span class="nav-arrow">›</span>
                    </button>
                    
                    <!-- Indicadores -->
                    <div class="carousel-indicators">
                        <?php foreach ($banners as $index => $banner): ?>
                            <button class="indicator <?= $index === 0 ? 'active' : '' ?>" 
                                    onclick="goToSlide(<?= $index ?>)"
                                    aria-label="Ir para banner <?= $index + 1 ?>"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Fallback caso não tenha banners -->
                <div class="carousel-wrapper">
                    <div class="hero-slide fallback-slide active">
                        <div class="hero-content">
                            <h1 class="hero-title">PRÊMIOS DE ATÉ</h1>
                            <h2 class="hero-subtitle">15 MIL REAIS</h2>
                            <a href="#games" class="hero-cta">RASPE AGORA!</a>
                        </div>
                        <div class="hero-visuals">
                            <div class="money-stack"></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
/* ==========================================
   BANNER CAROUSEL STYLES - NAVEGAÇÃO CORRIGIDA
   ========================================== */

/* Container principal do carrossel */
.carousel-container {
    position: relative;
    width: 100%;
    max-width: 100%;
    height: 500px;
    border-radius: 24px;
    overflow: hidden;
    margin: 0 auto;
}

/* Wrapper dos slides */
.carousel-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
}

/* Slides dos banners */
.hero-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    opacity: 0;
    visibility: hidden;
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1;
}

/* Slide ativo */
.hero-slide.active {
    opacity: 1;
    visibility: visible;
    z-index: 2;
}

/* Slide de fallback */
.hero-slide.fallback-slide {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 3rem 4rem;
}

/* Conteúdo do fallback */
.hero-content {
    color: white;
    z-index: 3;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.hero-subtitle {
    font-size: 3rem;
    font-weight: 900;
    color: #22c55e;
    margin-bottom: 1.5rem;
}

.hero-cta {
    display: inline-block;
    background: #22c55e;
    color: white;
    padding: 1rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    transition: transform 0.3s ease;
}

.hero-cta:hover {
    transform: translateY(-2px);
}

/* Slides com banners */
.hero-slide.banner-slide {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Overlay para banners */
.banner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    z-index: 1;
}

/* NAVEGAÇÃO DO CARROSSEL - ÍCONES CORRIGIDOS */
.carousel-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(10px);
    color: white;
    border: 2px solid rgba(34, 197, 94, 0.3);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.carousel-nav .nav-arrow {
    font-size: 24px;
    font-weight: bold;
    line-height: 1;
    user-select: none;
    pointer-events: none;
}

.carousel-nav:hover {
    background: rgba(34, 197, 94, 0.8);
    border-color: #22c55e;
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
}

.carousel-nav.prev {
    left: 2rem;
}

.carousel-nav.next {
    right: 2rem;
}

/* Indicadores */
.carousel-indicators {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 0.75rem;
    z-index: 10;
}

.indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.7);
    background: transparent;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(5px);
}

.indicator:hover {
    border-color: white;
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.2);
}

.indicator.active {
    background: #22c55e;
    border-color: #22c55e;
    box-shadow: 0 0 15px rgba(34, 197, 94, 0.6);
}

/* Loading state */
.banner-slide::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 40px;
    height: 40px;
    border: 3px solid rgba(34, 197, 94, 0.3);
    border-top-color: #22c55e;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 5;
}

.banner-slide.loading::before {
    opacity: 1;
}

@keyframes spin {
    to { transform: translate(-50%, -50%) rotate(360deg); }
}

/* Animações de transição */
@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.hero-slide.slide-right {
    animation: slideInRight 0.6s ease-out;
}

.hero-slide.slide-left {
    animation: slideInLeft 0.6s ease-out;
}

/* ==========================================
   RESPONSIVO
   ========================================== */

/* Desktop grande */
@media (min-width: 1440px) {
    .carousel-container {
        height: 600px;
        max-width: 95%;
    }
}

/* Desktop médio */
@media (min-width: 1025px) and (max-width: 1439px) {
    .carousel-container {
        height: 500px;
        max-width: 90%;
    }
}

/* Tablet */
@media (max-width: 1024px) {
    .carousel-container {
        height: 350px;
        border-radius: 20px;
        max-width: 95%;
    }
    
    .hero-slide.fallback-slide {
        padding: 2rem 3rem;
    }
    
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        font-size: 2.5rem;
    }
}

/* Mobile */
@media (max-width: 768px) {
    .hero-section {
        padding: 1rem 0;
    }
    
    .hero-container {
        padding: 0 1rem;
    }
    
    .carousel-container {
        height: 220px;
        border-radius: 16px;
        max-width: 100%;
        margin: 0;
    }
    
    .hero-slide {
        background-size: contain;
        background-position: center;
    }
    
    .hero-slide.fallback-slide {
        flex-direction: column;
        text-align: center;
        padding: 1.5rem 1rem;
        gap: 1rem;
        background-size: cover;
    }
    
    .hero-title {
        font-size: 1.5rem;
        margin-bottom: 0.25rem;
    }
    
    .hero-subtitle {
        font-size: 2rem;
        margin-bottom: 1rem;
    }
    
    .hero-cta {
        padding: 0.75rem 1.5rem;
        font-size: 0.9rem;
    }
    
    .carousel-nav {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .carousel-nav .nav-arrow {
        font-size: 18px;
    }
    
    .carousel-nav.prev {
        left: 1rem;
    }
    
    .carousel-nav.next {
        right: 1rem;
    }
    
    .carousel-indicators {
        bottom: 1rem;
        gap: 0.5rem;
    }
    
    .indicator {
        width: 8px;
        height: 8px;
    }
    
    /* Remover animações no mobile */
    .hero-slide.slide-right,
    .hero-slide.slide-left {
        animation: none;
    }
}

/* Mobile pequeno */
@media (max-width: 480px) {
    .hero-section {
        padding: 0.5rem 0;
    }
    
    .hero-container {
        padding: 0 0.5rem;
    }
    
    .carousel-container {
        height: 200px;
        border-radius: 12px;
        max-width: 100%;
        margin: 0;
    }
    
    .hero-slide {
        background-size: contain;
        background-position: center;
    }
    
    .hero-slide.fallback-slide {
        padding: 1rem;
        background-size: cover;
    }
    
    .hero-title {
        font-size: 1.25rem;
    }
    
    .hero-subtitle {
        font-size: 1.75rem;
    }
    
    .hero-cta {
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
    }
    
    .carousel-nav {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    .carousel-nav .nav-arrow {
        font-size: 14px;
    }
    
    .carousel-nav.prev {
        left: 0.5rem;
    }
    
    .carousel-nav.next {
        right: 0.5rem;
    }
    
    .carousel-indicators {
        bottom: 0.5rem;
        gap: 0.4rem;
    }
    
    .indicator {
        width: 6px;
        height: 6px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const indicators = document.querySelectorAll('.indicator');
    const totalSlides = slides.length;
    let isTransitioning = false;
    let autoPlayInterval;

    // Inicializar apenas se houver slides
    if (totalSlides > 0) {
        initializeCarousel();
    }

    function initializeCarousel() {
        // Precarregar imagens dos banners
        preloadImages();
        
        // Iniciar autoplay se houver mais de um banner
        if (totalSlides > 1) {
            startAutoPlay();
            
            // Controles de mouse para pausar autoplay
            const carouselContainer = document.querySelector('.carousel-container');
            if (carouselContainer) {
                carouselContainer.addEventListener('mouseenter', pauseAutoPlay);
                carouselContainer.addEventListener('mouseleave', startAutoPlay);
            }
        }
        
        // Configurar controles de teclado
        setupKeyboardControls();
        
        // Configurar controles touch para mobile
        setupTouchControls();
    }

    function preloadImages() {
        slides.forEach((slide) => {
            if (slide.classList.contains('banner-slide')) {
                const bgImage = slide.style.backgroundImage;
                if (bgImage) {
                    const imageUrl = bgImage.slice(4, -1).replace(/["']/g, "");
                    const img = new Image();
                    
                    slide.classList.add('loading');
                    
                    img.onload = function() {
                        slide.classList.remove('loading');
                        
                        // Detectar melhor fit baseado na proporção da imagem
                        const containerWidth = slide.offsetWidth;
                        const containerHeight = slide.offsetHeight;
                        const containerRatio = containerWidth / containerHeight;
                        const imageRatio = img.width / img.height;
                        
                        // Se a imagem for muito mais larga que o container, usar contain
                        // Se for similar, usar cover para preencher
                        if (Math.abs(imageRatio - containerRatio) > 0.5) {
                            slide.style.backgroundSize = 'contain';
                        } else {
                            slide.style.backgroundSize = 'cover';
                        }
                    };
                    
                    img.onerror = function() {
                        slide.classList.remove('loading');
                        // Fallback em caso de erro
                    };
                    
                    img.src = imageUrl;
                }
            }
        });
    }

    function showSlide(index, direction = 'right') {
        if (isTransitioning || index === currentSlide || index >= totalSlides) return;
        
        isTransitioning = true;
        
        // Remove classe ativa do slide atual
        slides[currentSlide].classList.remove('active');
        
        // Adiciona classe ativa ao novo slide
        slides[index].classList.add('active');
        
        // Adiciona animação apenas em desktop
        if (window.innerWidth > 768) {
            slides[index].classList.add(direction === 'right' ? 'slide-right' : 'slide-left');
            
            setTimeout(() => {
                slides[index].classList.remove('slide-right', 'slide-left');
            }, 600);
        }
        
        // Atualizar indicadores
        updateIndicators(index);
        
        currentSlide = index;
        
        setTimeout(() => {
            isTransitioning = false;
        }, 300);
    }

    function updateIndicators(activeIndex) {
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === activeIndex);
        });
    }

    // FUNÇÕES GLOBAIS PARA OS BOTÕES - CORRIGIDAS
    window.changeSlide = function(direction) {
        if (totalSlides <= 1) return;
        
        let newSlide = currentSlide + direction;
        
        if (newSlide >= totalSlides) {
            newSlide = 0;
        } else if (newSlide < 0) {
            newSlide = totalSlides - 1;
        }
        
        const slideDirection = direction > 0 ? 'right' : 'left';
        showSlide(newSlide, slideDirection);
    };

    window.goToSlide = function(index) {
        if (index < 0 || index >= totalSlides || index === currentSlide) return;
        
        const direction = index > currentSlide ? 'right' : 'left';
        showSlide(index, direction);
    };

    function startAutoPlay() {
        if (totalSlides > 1) {
            clearInterval(autoPlayInterval);
            autoPlayInterval = setInterval(() => {
                changeSlide(1);
            }, 5000);
        }
    }

    function pauseAutoPlay() {
        if (autoPlayInterval) {
            clearInterval(autoPlayInterval);
        }
    }

    function setupKeyboardControls() {
        document.addEventListener('keydown', function(e) {
            if (totalSlides > 1) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    changeSlide(-1);
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    changeSlide(1);
                }
            }
        });
    }

    function setupTouchControls() {
        let touchStartX = 0;
        let touchEndX = 0;
        const swipeThreshold = 50;

        const carouselContainer = document.querySelector('.carousel-container');
        if (!carouselContainer || totalSlides <= 1) return;

        carouselContainer.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        carouselContainer.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });

        function handleSwipe() {
            const diff = touchStartX - touchEndX;

            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    // Swipe left - próximo slide
                    changeSlide(1);
                } else {
                    // Swipe right - slide anterior
                    changeSlide(-1);
                }
            }
        }
    }

    // Pausar autoplay quando a aba não está visível
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            pauseAutoPlay();
        } else if (totalSlides > 1) {
            startAutoPlay();
        }
    });
});
</script>