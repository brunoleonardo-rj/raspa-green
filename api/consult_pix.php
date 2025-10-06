<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$qrcode = $_POST['qrcode'] ?? '';

if (empty($qrcode)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetro qrcode ausente']);
    exit;
}

require_once __DIR__ . '/../conexao.php';

try {
    $stmt = $pdo->prepare("SELECT status FROM depositos WHERE qrcode = :qrcode LIMIT 1");
    $stmt->bindParam(':qrcode', $qrcode, PDO::PARAM_STR);
    $stmt->execute();

    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(['paid' => false]);
        exit;
    }

    $paid = ($row['status'] === 'PAID');
    echo json_encode(['paid' => $paid]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro na consulta']);
    exit;
}