<?php
@session_start();
require_once '../conexao.php';
header('Content-Type: application/json');

$userId  = $_SESSION['usuario_id'] ?? 0;
$orderId = (int)($_POST['order_id'] ?? 0);

if (!$userId || !$orderId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Dados inválidos']));
}

$stmt = $pdo->prepare("
    SELECT o.*, r.valor AS custo_raspadinha
      FROM orders o
      JOIN raspadinhas r ON r.id = o.raspadinha_id
     WHERE o.id = ? AND o.user_id = ?
     LIMIT 1
");

$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order || $order['status'] == 1) {
    http_response_code(400);
    exit(json_encode(['error' => 'Ordem inválida']));
}

$gridIds  = json_decode($order['premios_json'], true);
$contagem = array_count_values($gridIds);

$premioId   = null;
$valorPremio = 0.00;
$resultado  = 'loss';

// ✅ CORREÇÃO: Buscar o MAIOR prêmio ao invés do primeiro
$maiorPremio = 0.00;
$melhorPremioId = null;

foreach ($contagem as $id => $qtd) {
    // ✅ CORREÇÃO: Aceita 3 OU MAIS imagens iguais (>= 3)
    if ($qtd >= 3) {
        $p = $pdo->prepare("SELECT valor FROM raspadinha_premios WHERE id = ?");
        $p->execute([$id]);
        $valorEncontrado = (float)$p->fetchColumn();

        if ($valorEncontrado > 0) {
            // ✅ Verifica se este prêmio é maior que o anterior
            if ($valorEncontrado > $maiorPremio) {
                $maiorPremio = $valorEncontrado;
                $melhorPremioId = $id;
            }
        }
    }
}

// ✅ Define o resultado baseado no maior prêmio encontrado
if ($maiorPremio > 0) {
    $premioId = $melhorPremioId;
    $valorPremio = $maiorPremio;
    $resultado = 'gain';
}

if ($resultado === 'gain') {
    $valorTotalACreditar = $valorPremio + (float)$order['custo_raspadinha'];

    $pdo->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?")
        ->execute([$valorTotalACreditar, $userId]);
    
    // ✅ Revshare baseado no valor ganho
    processarRevshareGanho($pdo, $userId, $valorPremio);
} else {
    // ✅ Revshare baseado no valor apostado
    processarRevsharePerdas($pdo, $userId, (float)$order['custo_raspadinha']);
}

$pdo->prepare("
    UPDATE orders
       SET status       = 1,
           resultado     = ?,
           valor_ganho   = ?,
           updated_at    = NOW()
     WHERE id = ?
")->execute([$resultado, $valorPremio, $orderId]);

echo json_encode([
    'success'   => true,
    'resultado' => $resultado,
    'valor'     => $valorPremio 
]);

/**
 * ✅ Quando o usuário GANHA, afiliado PERDE (baseado no valor ganho)
 */
function processarRevshareGanho($pdo, $userId, $valorGanho) {
    try {
        $stmt = $pdo->prepare("SELECT indicacao FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario || !$usuario['indicacao']) return false;
        
        $stmt = $pdo->prepare("SELECT id, comissao_revshare, saldo FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario['indicacao']]);
        $afiliado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$afiliado) return false;
        
        $percentualRevshare = $afiliado['comissao_revshare'];

        if ($percentualRevshare == 0) {
            $stmt = $pdo->query("SELECT revshare_padrao FROM config LIMIT 1");
            $percentualRevshare = $stmt->fetchColumn() ?: 0;
        }
        
        if ($percentualRevshare <= 0) return false;

        // ✅ Dedução baseada no valor ganho
        $valorDeduzir = ($valorGanho * $percentualRevshare) / 100;
        if ($valorDeduzir <= 0) return false;

        $novoSaldo = $afiliado['saldo'] - $valorDeduzir;
        $stmt = $pdo->prepare("UPDATE usuarios SET saldo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$novoSaldo, $afiliado['id']]);
        
        registrarTransacaoRevshare($pdo, $afiliado['id'], $userId, $valorGanho, -$valorDeduzir, $percentualRevshare, 'ganho_usuario');
        return true;
        
    } catch (PDOException $e) {
        error_log("Erro ao processar revshare ganho: " . $e->getMessage());
        return false;
    }
}

/**
 * ✅ Quando o usuário PERDE, afiliado GANHA (baseado no valor apostado)
 */
function processarRevsharePerdas($pdo, $userId, $valorPerdido) {
    try {
        $stmt = $pdo->prepare("SELECT indicacao FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario || !$usuario['indicacao']) return false;

        $stmt = $pdo->prepare("SELECT id, comissao_revshare, saldo FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario['indicacao']]);
        $afiliado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$afiliado) return false;

        $percentualRevshare = $afiliado['comissao_revshare'];
        if ($percentualRevshare == 0) {
            $stmt = $pdo->query("SELECT revshare_padrao FROM config LIMIT 1");
            $percentualRevshare = $stmt->fetchColumn() ?: 0;
        }
        
        if ($percentualRevshare <= 0) return false;

        $comissao = ($valorPerdido * $percentualRevshare) / 100;
        if ($comissao <= 0) return false;

        $novoSaldo = $afiliado['saldo'] + $comissao;
        $stmt = $pdo->prepare("UPDATE usuarios SET saldo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$novoSaldo, $afiliado['id']]);
        
        registrarTransacaoRevshare($pdo, $afiliado['id'], $userId, $valorPerdido, $comissao, $percentualRevshare, 'perda_usuario');
        return true;
        
    } catch (PDOException $e) {
        error_log("Erro ao processar revshare perda: " . $e->getMessage());
        return false;
    }
}

/**
 * Histórico de transações de revshare
 */
function registrarTransacaoRevshare($pdo, $afiliadoId, $usuarioId, $valorBase, $valorRevshare, $percentual, $tipo) {
    try {
        criarTabelaHistoricoRevshare($pdo);
        $stmt = $pdo->prepare("
            INSERT INTO historico_revshare 
            (afiliado_id, usuario_id, valor_apostado, valor_revshare, percentual, tipo, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$afiliadoId, $usuarioId, $valorBase, $valorRevshare, $percentual, $tipo]);
    } catch (PDOException $e) {
        error_log("Erro ao registrar histórico revshare: " . $e->getMessage());
    }
}

/**
 * Criação da tabela de histórico se necessário
 */
function criarTabelaHistoricoRevshare($pdo) {
    try {
        $sql = "
            CREATE TABLE IF NOT EXISTS `historico_revshare` (
                `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `afiliado_id` int(11) NOT NULL,
                `usuario_id` int(11) NOT NULL,
                `valor_apostado` decimal(10,2) NOT NULL,
                `valor_revshare` decimal(10,2) NOT NULL,
                `percentual` float NOT NULL,
                `tipo` enum('perda_usuario','ganho_usuario') NOT NULL,
                `created_at` datetime DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_afiliado_id` (`afiliado_id`),
                KEY `idx_usuario_id` (`usuario_id`),
                KEY `idx_tipo` (`tipo`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";
        $pdo->exec($sql);
    } catch (PDOException $e) {
        error_log("Erro ao criar tabela historico_revshare: " . $e->getMessage());
    }
}