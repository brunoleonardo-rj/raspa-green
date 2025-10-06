<?php
@session_start();
require_once '../conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você precisa estar logado para acessar esta página!'];
  header("Location: /login");
  exit;
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM raspadinhas WHERE id = ?");
$stmt->execute([$id]);
$cartela = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cartela) {
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Cartela não encontrada.'];
    header("Location: /raspadinhas");
    exit;
}

$premios = $pdo->prepare("SELECT * FROM raspadinha_premios WHERE raspadinha_id = ? ORDER BY valor DESC");
$premios->execute([$id]);
$premios = $premios->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite;?> - <?= htmlspecialchars($cartela['nome']); ?></title>
    
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
    <script src="https://cdn.jsdelivr.net/npm/js-confetti@latest/dist/js-confetti.browser.js"></script>

    <style>
        /* Page Styles */
        .raspadinha-section {
            margin-top: 100px;
            padding: 4rem 0;
            background: #0a0a0a;
            min-height: calc(100vh - 200px);
        }

        .raspadinha-container {
            max-width: 800px;
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

        .cartela-banner {
            width: 100%;
            height: 200px;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            margin-bottom: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .cartela-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cartela-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(45deg, rgba(0, 0, 0, 0.3), rgba(34, 197, 94, 0.1));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cartela-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 900;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
            padding: 0 1rem;
        }

        .price-badge {
            position: absolute;
            bottom: 1rem;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 16px rgba(34, 197, 94, 0.4);
        }

        /* Instructions */
        .instructions {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .instructions h3 {
            color: #22c55e;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .instructions-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            color: #e5e7eb;
        }

        .instruction-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .instruction-icon {
            color: #22c55e;
            font-size: 1.1rem;
        }

        /* Prizes Section */
        .prizes-section {
            background: rgba(10, 10, 10, 0.6);
            border: 1px solid rgba(34, 197, 94, 0.1);
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .prizes-title {
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .prizes-title i {
            color: #22c55e;
            font-size: 1.2rem;
        }

        .prizes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 1rem;
            max-height: 320px;
            overflow-y: auto;
            padding: 0.5rem;
        }

        /* Custom scrollbar for prizes grid */
        .prizes-grid::-webkit-scrollbar {
            width: 6px;
        }

        .prizes-grid::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
        }

        .prizes-grid::-webkit-scrollbar-thumb {
            background: #22c55e;
            border-radius: 3px;
        }

        .prizes-grid::-webkit-scrollbar-thumb:hover {
            background: #16a34a;
        }

        .prize-card {
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .prize-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .prize-card:hover::before {
            opacity: 1;
        }

        .prize-card:hover {
            border-color: rgba(34, 197, 94, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.15);
        }

        .prize-image {
            width: 64px;
            height: 64px;
            margin: 0 auto 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            position: relative;
            z-index: 2;
        }

        .prize-image img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

        .prize-info {
            position: relative;
            z-index: 2;
        }

        .prize-name {
            color: #e5e7eb;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            line-height: 1.2;
        }

        .prize-value {
            color: #22c55e;
            font-size: 0.9rem;
            font-weight: 700;
        }

        /* Game Container */
        .game-container {
            background: rgba(20, 20, 20, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 2rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        .game-title {
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

        /* Scratch Container */
        #scratch-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            aspect-ratio: 1 / 1;
            margin: 0 auto 2rem;
            border-radius: 20px;
            user-select: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        #prizes-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 8px;
            padding: 12px;
            background: linear-gradient(135deg, #1f2937, #374151);
            color: white;
            border-radius: 20px;
            z-index: 1;
        }

        #prizes-grid > div {
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        #prizes-grid > div::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(45deg, rgba(34, 197, 94, 0.1), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        #prizes-grid > div:hover::before {
            opacity: 1;
        }

        #prizes-grid img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            margin-bottom: 6px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

        #scratch-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 20px;
            z-index: 10;
            touch-action: none;
            cursor: pointer;
            user-select: none;
        }

        #btn-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
            z-index: 30;
            border-radius: 20px;
            text-align: center;
            gap: 1rem;
        }

        .overlay-icon {
            font-size: 3rem;
            color: #22c55e;
            margin-bottom: 1rem;
        }

        /* Buy Button */
        .buy-button {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 8px 30px rgba(34, 197, 94, 0.4);
            position: relative;
            overflow: hidden;
        }

        .buy-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(34, 197, 94, 0.5);
        }

        .buy-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .buy-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .buy-button:hover::before {
            left: 100%;
        }

        /* Result Message */
        #result-msg {
            margin-top: 2rem;
            font-weight: 700;
            text-align: center;
            min-height: 2rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Loading States */
        .loading-pulse {
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Prize animations */
        .prize-reveal {
            animation: prizeReveal 0.5s ease-out forwards;
        }

        @keyframes prizeReveal {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Success animations */
        .win-animation {
            animation: winPulse 1s ease-in-out infinite;
        }

        @keyframes winPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .raspadinha-container {
                padding: 0 1rem;
            }
            
            .cartela-title {
                font-size: 2rem;
            }
            
            .game-container {
                padding: 1.5rem;
            }
            
            .instructions-list {
                grid-template-columns: 1fr;
            }

            .prizes-section {
                padding: 1.5rem;
                margin-top: 1.5rem;
            }

            .prizes-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 0.75rem;
                max-height: 280px;
            }

            .prize-card {
                padding: 0.75rem;
            }

            .prize-image {
                width: 56px;
                height: 56px;
                margin-bottom: 0.5rem;
            }

            .prize-image img {
                width: 40px;
                height: 40px;
            }

            .prize-name {
                font-size: 0.75rem;
            }

            .prize-value {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            .cartela-title {
                font-size: 1.5rem;
            }
            
            #scratch-container {
                max-width: 300px;
            }

            .prizes-section {
                padding: 1rem;
            }

            .prizes-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 0.5rem;
                max-height: 240px;
            }

            .prize-card {
                padding: 0.5rem;
            }

            .prize-image {
                width: 48px;
                height: 48px;
                margin-bottom: 0.5rem;
            }

            .prize-image img {
                width: 32px;
                height: 32px;
            }

            .prize-name {
                font-size: 0.7rem;
            }

            .prize-value {
                font-size: 0.75rem;
            }

            .prizes-title {
                font-size: 1rem;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include('../inc/header.php'); ?>
    <?php include('../components/modals.php'); ?>

    <section class="raspadinha-section">
        <div class="raspadinha-container">
            <!-- Header Card -->
            <div class="header-card">
                <div class="cartela-banner">
                    <img src="<?= htmlspecialchars($cartela['banner']); ?>" 
                         class="cartela-image" 
                         alt="Banner <?= htmlspecialchars($cartela['nome']); ?>">
                    
                    <div class="cartela-overlay">
                        <h1 class="cartela-title"><?= htmlspecialchars($cartela['nome']); ?></h1>
                    </div>
                    
                    <div class="price-badge">
                        <i class="bi bi-tag-fill"></i>
                        R$ <?= number_format($cartela['valor'], 2, ',', '.'); ?>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="instructions">
                    <h3><i class="bi bi-info-circle"></i> Como Jogar</h3>
                    <div class="instructions-list">
                        <div class="instruction-item">
                            <i class="bi bi-1-circle instruction-icon"></i>
                            <span>Clique em "Comprar e Raspar"</span>
                        </div>
                        <div class="instruction-item">
                            <i class="bi bi-2-circle instruction-icon"></i>
                            <span>Raspe a cartela com o mouse/dedo</span>
                        </div>
                        <div class="instruction-item">
                            <i class="bi bi-3-circle instruction-icon"></i>
                            <span>Descubra se você ganhou!</span>
                        </div>
                        <div class="instruction-item">
                            <i class="bi bi-4-circle instruction-icon"></i>
                            <span>Prêmios são creditados na hora</span>
                        </div>
                    </div>
                </div>

                <!-- Prizes Section -->
                <?php if (!empty($premios)): ?>
                <div class="prizes-section">
                    <h3 class="prizes-title">
                        <i class="bi bi-gift-fill"></i>
                        CONTEÚDO DESSA RASPADINHA:
                    </h3>
                    
                    <div class="prizes-grid">
                        <?php foreach ($premios as $premio): ?>
                            <div class="prize-card">
                                <div class="prize-image">
                                    <img src="<?= htmlspecialchars($premio['icone']); ?>" 
                                         alt="<?= htmlspecialchars($premio['nome']); ?>"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiByeD0iMTIiIGZpbGw9IiMyMmM1NWUiLz4KPHN2ZyB4PSIxNiIgeT0iMTYiIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgZmlsbD0id2hpdGUiPgo8cGF0aCBkPSJNMTYgOGMwLTQuNDExIDMuNTg5LTggOC04czggMy41ODkgOCA4djJjMCAxLjEwNS0uODk1IDItMiAySDJjLTEuMTA1IDAtMi0uODk1LTItMlY4eiIvPgo8L3N2Zz4KPC9zdmc+'">
                                </div>
                                <div class="prize-info">
                                    <div class="prize-name"><?= htmlspecialchars($premio['nome']); ?></div>
                                    <div class="prize-value">R$ <?= number_format($premio['valor'], 2, ',', '.'); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Game Container -->
            <div class="game-container">
                <h2 class="game-title">
                    <i class="bi bi-diamond-fill"></i>
                    Sua Raspadinha
                </h2>

                <div id="scratch-container">
                    <div id="prizes-grid"></div>
                    <canvas id="scratch-canvas"></canvas>
                    <div id="btn-overlay">
                        <i class="bi bi-play-circle overlay-icon"></i>
                        <div>Clique em "Comprar" para jogar</div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">Boa sorte! 🍀</div>
                    </div>
                </div>

                <button id="btn-buy" class="buy-button">
                    <i class="bi bi-credit-card"></i>
                    Comprar e Raspar (R$ <?= number_format($cartela['valor'], 2, ',', '.'); ?>)
                </button>

                <div id="result-msg"></div>
            </div>
        </div>
    </section>

    <?php include('../inc/footer.php'); ?>

    <script>
        let container = document.getElementById('scratch-container');
        let canvas = document.getElementById('scratch-canvas');
        let ctx = canvas.getContext('2d');
        let prizesGrid = document.getElementById('prizes-grid');
        let btnBuy = document.getElementById('btn-buy');
        let resultMsg = document.getElementById('result-msg');
        let overlay = document.getElementById('btn-overlay');
        let scratchImage = new Image();
        scratchImage.src = '/assets/img/raspe.png?id=122';

        let orderId = null;
        let brushRadius = 55;
        let isDrawing = false;
        let scratchedPercentage = 0;
        let isScratchEnabled = false;

        function ajustarCanvas() {
            const size = container.clientWidth;
            canvas.width = size;
            canvas.height = size;
            drawScratchImage();
        }

        function resetCanvas() {
            if (canvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);

            const newCanvas = document.createElement('canvas');
            newCanvas.id = 'scratch-canvas';
            newCanvas.className = canvas.className;    
            container.appendChild(newCanvas);

            canvas = newCanvas;
            ctx = newCanvas.getContext('2d');

            ajustarCanvas();
            addCanvasListeners();
        }

        function addCanvasListeners() {
            canvas.replaceWith(canvas.cloneNode(true));
            canvas = document.getElementById('scratch-canvas');
            ctx = canvas.getContext('2d');

            canvas.addEventListener('mousedown', handleStart);
            canvas.addEventListener('mousemove', handleMove);
            canvas.addEventListener('mouseup', handleEnd);
            canvas.addEventListener('mouseleave', handleEnd);
            canvas.addEventListener('touchstart', handleStart, {passive:false});
            canvas.addEventListener('touchmove', handleMove, {passive:false});
            canvas.addEventListener('touchend', handleEnd);
            canvas.addEventListener('touchcancel', handleEnd);
        }

        window.addEventListener('resize', ajustarCanvas);
        scratchImage.onload = () => {
            ajustarCanvas();
        };

        function drawScratchImage() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.globalCompositeOperation = 'source-over';
            ctx.drawImage(scratchImage, 0, 0, canvas.width, canvas.height);
        }

        function scratch(x, y) {
            if (!isScratchEnabled) return;
            ctx.globalCompositeOperation = 'destination-out';
            ctx.beginPath();
            ctx.arc(x, y, brushRadius, 0, Math.PI * 2);
            ctx.fill();
        }

        function getScratchedPercentage() {
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const pixels = imageData.data;
            let transparentPixels = 0;

            for (let i = 3; i < pixels.length; i += 4) {
                if (pixels[i] === 0) transparentPixels++;
            }
            return (transparentPixels / (canvas.width * canvas.height)) * 100;
        }

        function getMousePos(e) {
            const rect = canvas.getBoundingClientRect();
            if (e.touches) {
                return {
                    x: e.touches[0].clientX - rect.left,
                    y: e.touches[0].clientY - rect.top
                };
            } else {
                return {
                    x: e.clientX - rect.left,
                    y: e.clientY - rect.top
                };
            }
        }

        function handleStart(e) {
            if (!isScratchEnabled) return;
            isDrawing = true;
            const pos = getMousePos(e);
            scratch(pos.x, pos.y);
        }

        function handleMove(e) {
            if (!isDrawing || !isScratchEnabled) return;
            const pos = getMousePos(e);
            scratch(pos.x, pos.y);
            scratchedPercentage = getScratchedPercentage();
            if (scratchedPercentage > 75) {
                autoFinishScratch();
            }
        }

        function handleEnd() {
            isDrawing = false;
        }

        function buildCell(prize) {
            return `
                <div class="prize-reveal">
                    <img src="${prize.icone}" alt="${prize.nome}" />
                    <span>${prize.valor > 0 ? 'R$ ' + prize.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : prize.nome}</span>
                </div>
            `;
        }

        let fadeInterval = null;

        async function autoFinishScratch() {
            isScratchEnabled = false;
            fadeInterval = setInterval(() => {
                ctx.globalCompositeOperation = 'destination-out';
                ctx.fillStyle = 'rgba(0,0,0,0.1)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
            }, 50);

            setTimeout(() => {
                clearInterval(fadeInterval);
                fadeInterval = null;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }, 500);

            finishScratch();
        }

        async function finishScratch() {
            resultMsg.innerHTML = '<i class="bi bi-hourglass-split loading-pulse"></i> Verificando resultado...';
            
            const fd = new FormData();
            fd.append('order_id', orderId);
            const response = await fetch('/raspadinhas/finish.php', { method: 'POST', body: fd });
            const json = await response.json();

            if (!json.success) {
                Notiflix.Notify.failure('Erro ao finalizar.');
                return;
            }

            const jsConfetti = new JSConfetti();
            
            if (json.valor === 0 || json.resultado === 'lose') {
                resultMsg.innerHTML = `
                    <div style="color: #ef4444;">
                        <i class="bi bi-emoji-frown"></i>
                        Não foi dessa vez. Tente novamente!
                    </div>
                `;
                Notiflix.Notify.info('Não foi dessa vez. 😢');
                clearInterval(fadeInterval);
                fadeInterval = 0;
                await atualizarSaldoUsuario();
            } else {
                container.classList.add('win-animation');
                resultMsg.innerHTML = `
                    <div style="color: #22c55e;">
                        <i class="bi bi-trophy-fill"></i>
                        🎉 Parabéns! Você ganhou R$ ${json.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}!
                    </div>
                `;
                Notiflix.Notify.success(`🎉 Você ganhou R$ ${json.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}!`);
                clearInterval(fadeInterval);
                fadeInterval = 0;

                jsConfetti.addConfetti({
                    emojis: ['🎉', '✨', '🎊', '🥳', '💰', '🍀'],
                    emojiSize: 20,
                    confettiNumber: 300,
                    confettiRadius: 6,
                    confettiColors: ['#22c55e', '#16a34a', '#15803d', '#166534', '#14532d']
                });

                await atualizarSaldoUsuario();
            }

            btnBuy.style.opacity = '1';
            btnBuy.disabled = false;
            btnBuy.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Jogar Novamente';
        }

        function reiniciarJogo() {
            if (fadeInterval) { 
                clearInterval(fadeInterval); 
                fadeInterval = null; 
            }

            container.classList.remove('win-animation');
            prizesGrid.innerHTML = '';
            resultMsg.innerHTML = '';
            overlay.style.display = 'flex';
            orderId = null;
            scratchedPercentage = 0;
            isScratchEnabled = false;
            isDrawing = false;
            ctx.globalCompositeOperation = 'source-over';
            ajustarCanvas();
            resetCanvas();
            btnBuy.disabled = false;
            btnBuy.innerHTML = '<i class="bi bi-credit-card"></i> Comprar e Raspar (R$ <?= number_format($cartela['valor'], 2, ',', '.'); ?>)';
            btnBuy.style.opacity = '1';
        }

        btnBuy.addEventListener('click', async () => {
            if (btnBuy.innerHTML.includes('Jogar Novamente')) {
                reiniciarJogo();
                setTimeout(() => btnBuy.click(), 100);
                return;
            }

            btnBuy.disabled = true;
            btnBuy.innerHTML = '<i class="bi bi-hourglass-split loading-pulse"></i> Gerando...';
            resultMsg.innerHTML = '';
            prizesGrid.innerHTML = '';
            overlay.style.display = 'none';

            const fd = new FormData();
            fd.append('raspadinha_id', <?= $cartela['id']; ?>);
            const res = await fetch('/raspadinhas/buy.php', { method: 'POST', body: fd });
            const json = await res.json();

            if (!json.success) {
                Notiflix.Notify.failure(json.error);
                btnBuy.disabled = false;
                btnBuy.innerHTML = '<i class="bi bi-credit-card"></i> Comprar e Raspar';
                overlay.style.display = 'flex';
                return;
            }

            orderId = json.order_id;
            const premiosRes = await fetch('/raspadinhas/prizes.php?ids=' + json.grid.join(','));
            const premios = await premiosRes.json();

            prizesGrid.innerHTML = premios.map(buildCell).join('');
            drawScratchImage();
            isScratchEnabled = true;
            btnBuy.style.opacity = '0.6';
            btnBuy.innerHTML = '<i class="bi bi-hand-index"></i> Raspe a cartela!';
        });

        // Canvas event listeners
        canvas.addEventListener('mousedown', handleStart);
        canvas.addEventListener('mousemove', handleMove);
        canvas.addEventListener('mouseup', handleEnd);
        canvas.addEventListener('mouseleave', handleEnd);
        canvas.addEventListener('touchstart', handleStart);
        canvas.addEventListener('touchmove', handleMove);
        canvas.addEventListener('touchend', handleEnd);
        canvas.addEventListener('touchcancel', handleEnd);

        async function atualizarSaldoUsuario() {
            try {
                const res = await fetch('/api/get_saldo.php');
                const json = await res.json();

                if (json.success) {
                    const saldoFormatado = 'R$ ' + json.saldo.toFixed(2).replace('.', ',');
                    const el = document.getElementById('headerSaldo');
                    if (el) {
                        el.textContent = saldoFormatado;
                    }
                } else {
                    console.warn('Erro ao buscar saldo:', json.error);
                }
            } catch (e) {
                console.error('Erro na requisição de saldo:', e);
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('%c🎮 Raspadinha carregada!', 'color: #22c55e; font-size: 16px; font-weight: bold;');
            console.log(`Cartela: ${<?= json_encode($cartela['nome']); ?>}`);
        });
    </script>
</body>
</html>