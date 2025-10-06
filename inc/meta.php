<link rel="stylesheet" href="/assets/style/globalStyles.css?id=<?php time();?>"/>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-vpKzT5cUqQlRuSPiOFLsTv6HgWmN4qkMOnREgIfw49N2oXah0iA6P9ybpIzR5I0DjXKU+7Y9KtDFuBuqD8zgVg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.8/inputmask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-confetti@latest/dist/js-confetti.browser.js"></script>
<title><?php echo $nomeSite;?> - Raspadinha Online</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
<?php if ($logoSite && file_exists($_SERVER['DOCUMENT_ROOT'] . $logoSite)): ?>
   <link rel="icon" href="<?= htmlspecialchars($logoSite) ?>"/>
<?php else: ?>
   <link rel="icon" href="data:image/svg+xml,<?= urlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#22c55e"/><text x="50" y="50" text-anchor="middle" dominant-baseline="middle" fill="white" font-family="Arial" font-size="40" font-weight="bold">' . strtoupper(substr($nomeSite, 0, 1)) . '</text></svg>') ?>"/>
<?php endif; ?>