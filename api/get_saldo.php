<?php
@session_start();
require_once '../conexao.php';

header('Content-Type: application/json');

$userId = $_SESSION['usuario_id'] ?? 0;

if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

$stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);

$saldo = $stmt->fetchColumn();

if ($saldo === false) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuário não encontrado']);
    exit;
}

echo json_encode([
    'success' => true,
    'saldo' => (float)$saldo,
]);
