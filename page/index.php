<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite; ?></title>
    <link rel="stylesheet" href="/assets/style/globalStyles.css?id=<?php time();?>"/>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-vpKzT5cUqQlRuSPiOFLsTv6HgWmN4qkMOnREgIfw49N2oXah0iA6P9ybpIzR5I0DjXKU+7Y9KtDFuBuqD8zgVg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">

</head>
<body>
<?php include('../inc/header.php'); ?>



<?php include('../components/ganhos.php'); ?>

<?php include('../inc/footer.php'); ?>
</body>
</html>