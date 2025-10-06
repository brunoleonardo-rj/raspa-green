<?php
require_once '../conexao.php';
header('Content-Type: application/json');

$ids = array_map('intval', explode(',', $_GET['ids'] ?? ''));
if (!$ids) {
    echo '[]';
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, nome, icone, valor FROM raspadinha_premios WHERE id IN ($placeholders)");
$stmt->execute($ids);
$premios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mapa = [];
foreach ($premios as $premio) {
    $mapa[$premio['id']] = $premio;
}

$resultado = [];
foreach ($ids as $id) {
    if (isset($mapa[$id])) {
        $resultado[] = $mapa[$id];
    }
}

echo json_encode($resultado);