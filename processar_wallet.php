<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

$usuario_id = $_SESSION['usuario_id'] ?? 1;
$acao = $_POST['acao'] ?? '';

if (!$usuario_id || empty($acao)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Requisição inválida.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Busca dados do usuário atual
    $stmt = $pdo->prepare("SELECT saldo_brl, saldo_btc, saldo_wallet_btc, chave_carteira FROM usuarios WHERE id = ? FOR UPDATE");
    $stmt->execute([$usuario_id]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("Usuário não encontrado.");
    }

    if ($acao === 'importar_corretora') {
        // Puxa BTC do saldo da corretora para a Wallet
        $qtd_btc = (float)($_POST['quantidade_btc'] ?? 0);
        if ($qtd_btc <= 0 || $user['saldo_btc'] < $qtd_btc) {
            throw new Exception("Saldo de BTC insuficiente na Corretora.");
        }

        $stmtUp = $pdo->prepare("UPDATE usuarios SET saldo_btc = saldo_btc - ?, saldo_wallet_btc = saldo_wallet_btc + ? WHERE id = ?");
        $stmtUp->execute([$qtd_btc, $qtd_btc, $usuario_id]);

        $stmtLog = $pdo->prepare("INSERT INTO transacoes (usuarios_id, tipo, valor_brl, quantidade_btc, data_transacao) VALUES (?, 'deposito_wallet', 0, ?, NOW())");
        $stmtLog->execute([$usuario_id, $qtd_btc]);

    } elseif ($acao === 'converter_resgatar') {
        // Converte BTC da Wallet e envia como R$ para a Corretora
        $qtd_btc = (float)($_POST['quantidade_btc'] ?? 0);
        $cotacao = (float)($_POST['cotacao_btc'] ?? 400000);

        if ($qtd_btc <= 0 || $user['saldo_wallet_btc'] < $qtd_btc) {
            throw new Exception("Saldo de BTC insuficiente na Wallet.");
        }

        $valor_brl = $qtd_btc * $cotacao;

        $stmtUp = $pdo->prepare("UPDATE usuarios SET saldo_wallet_btc = saldo_wallet_btc - ?, saldo_brl = saldo_brl + ? WHERE id = ?");
        $stmtUp->execute([$qtd_btc, $valor_brl, $usuario_id]);

        $stmtLog = $pdo->prepare("INSERT INTO transacoes (usuarios_id, tipo, valor_brl, quantidade_btc, data_transacao) VALUES (?, 'resgate_corretora', ?, ?, NOW())");
        $stmtLog->execute([$usuario_id, $valor_brl, $qtd_btc]);

    } elseif ($acao === 'transferir_chave') {
        // Transfere BTC da Wallet atual para outra Wallet via Chave Hash
        $qtd_btc = (float)($_POST['quantidade_btc'] ?? 0);
        $chave_destino = trim($_POST['chave_destino'] ?? '');

        if ($qtd_btc <= 0 || $user['saldo_wallet_btc'] < $qtd_btc) {
            throw new Exception("Saldo em BTC insuficiente na sua Wallet.");
        }

        $stmtDest = $pdo->prepare("SELECT id FROM usuarios WHERE chave_carteira = ? FOR UPDATE");
        $stmtDest->execute([$chave_destino]);
        $destinatario = $stmtDest->fetch();

        if (!$destinatario) {
            throw new Exception("Chave de carteira de destino inválida ou não encontrada.");
        }

        if ($destinatario['id'] == $usuario_id) {
            throw new Exception("Você não pode transferir para a sua própria chave.");
        }

        // Subtrai do remetente
        $stmtSub = $pdo->prepare("UPDATE usuarios SET saldo_wallet_btc = saldo_wallet_btc - ? WHERE id = ?");
        $stmtSub->execute([$qtd_btc, $usuario_id]);

        // Adiciona ao destinatário
        $stmtAdd = $pdo->prepare("UPDATE usuarios SET saldo_wallet_btc = saldo_wallet_btc + ? WHERE id = ?");
        $stmtAdd->execute([$qtd_btc, $destinatario['id']]);

        // Registrar logs de transação
        $stmtLog1 = $pdo->prepare("INSERT INTO transacoes (usuarios_id, tipo, valor_brl, quantidade_btc, data_transacao) VALUES (?, 'envio_wallet', 0, ?, NOW())");
        $stmtLog1->execute([$usuario_id, $qtd_btc]);

        $stmtLog2 = $pdo->prepare("INSERT INTO transacoes (usuarios_id, tipo, valor_brl, quantidade_btc, data_transacao) VALUES (?, 'recebimento_wallet', 0, ?, NOW())");
        $stmtLog2->execute([$destinatario['id'], $qtd_btc]);
    } else {
        throw new Exception("Ação desconhecida.");
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true, 'mensagem' => 'Operação realizada com sucesso!']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
} 