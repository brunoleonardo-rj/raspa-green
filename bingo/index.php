<?php
session_start();

$_SESSION['message'] = [
    'type' => 'warning',
    'text' => 'Em breve!'
];

header('Location: /');
exit;