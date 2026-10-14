<?php 
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

$usuario_id = $_SESSION['usuario_id'] ?? 1; // Fallback para ID 1 se não houver sessão

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $cotacao_btc = (float)($_POST['cotacao_btc'] ?? 400000.00);

    try {
        // Inicia a transação no banco
        $pdo->beginTransaction();

        // Busca o usuário atual com bloqueio para atualização (FOR UPDATE)
        $stmt = $pdo->prepare("SELECT saldo_brl, saldo_btc FROM usuarios WHERE id = ? FOR UPDATE");
        $stmt->execute([$usuario_id]);
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            throw new Exception('Usuário não encontrado.');
        }

        $saldo_brl = (float)$usuario['saldo_brl'];
        $saldo_btc = (float)$usuario['saldo_btc'];

        if ($acao === 'comprar') {
            $valor_brl = (float)($_POST['valor_brl'] ?? 0);

            if ($valor_brl <= 0) {
                throw new Exception('Informe um valor válido.');
            }

            if ($valor_brl > $saldo_brl) {
                throw new Exception('Saldo em BRL insuficiente.');
            }

            $btc_comprado = $valor_brl / $cotacao_btc;
            $novo_brl = $saldo_brl - $valor_brl;
            $novo_btc = $saldo_btc + $btc_comprado;
            
            // Atualiza saldos da Corretora
            $upd = $pdo->prepare("UPDATE usuarios SET saldo_brl = ?, saldo_btc = ? WHERE id = ?");
            $upd->execute([$novo_brl, $novo_btc, $usuario_id]);

            // Regstra transação
            $log = $pdo->prepare("INSERT INTO transacoes (usuarios_id, tipo, valor_brl, quantidade_btc, data_transacao) VALUES (?, 'compra', ?, ?, NOW())");
            $log->execute([$usuario_id, $valor_brl, $btc_comprado]);

            $pdo->commit();
            echo json_encode(['sucesso' => true, 'mensagem' => 'Compra realizada com sucesso!']);
            exit;
        }

        if ($acao === 'vender') {
            $qtd_btc = (float)($_POST['quantidade_btc'] ?? 0);

            if ($qtd_btc <= 0) {
                throw new Exception('Informe uma quantidade válida de Bitcoin.');
            }

            if ($qtd_btc > $saldo_btc) {
                throw new Exception('Saldo em Bitcoin insuficiente para venda.');
            }

            $valor_brl_recebido = $qtd_btc * $cotacao_btc;
            $novo_brl = $saldo_brl + $valor_brl_recebido;
            $novo_btc = $saldo_btc - $qtd_btc;

            // Atualiza saldos
            $upd = $pdo->prepare("UPDATE usuarios SET saldo_brl = ?, saldo_btc = ? WHERE id = ?");
            $upd->execute([$novo_brl, $novo_btc, $usuario_id]);

            // Registra transação
            $log = $pdo->prepare("INSERT INTO transacoes (usuarios_id, tipo, valor_brl, quantidade_btc, data_transacao) VALUES (?, 'venda', ?, ?, NOW())");
            $log->execute([$usuario_id, $valor_brl_recebido, $qtd_btc]);

            $pdo->commit();
            echo json_encode(['sucesso' => true, 'mensagem' => 'Venda realizada com sucesso!']);
            exit;
        }

        if ($acao === 'transferir') {
            $qtd_btc = (float)($_POST['quantidade_btc'] ?? 0);
            $chave_destino = trim($_POST['chave_destino'] ?? '');
        
            if ($qtd_btc <= 0 || empty($chave_destino)) {
                throw new Exception('Preencha a chave de destino e a quantidade de Bitcoin.');
            }

            if ($qtd_btc > $saldo_btc) {
                throw new Exception('Saldo de Bitcoin insuficiente!');
            }

            // Busca usuário de destino pela chave pública
            $stmtDest = $pdo->prepare("SELECT id FROM usuarios WHERE chave_carteira = ? FOR UPDATE");
            $stmtDest->execute([$chave_destino]);
            $destinatario = $stmtDest->fetch();

            if (!$destinatario) {
                throw new Exception('Chave de destino não encontrada.');
            }

            if ($destinatario['id'] == $usuario_id) {
                throw new Exception('Você não pode transferir para você mesmo.');
            }

            // Subtrai do pagador
            $updRemetente = $pdo->prepare("UPDATE usuarios SET saldo_btc = saldo_btc - ? WHERE id = ?");
            $updRemetente->execute([$qtd_btc, $usuario_id]);

            // Credita no destinatário (no saldo da corretora)
            $updDestinatario = $pdo->prepare("UPDATE usuarios SET saldo_btc = saldo_btc + ? WHERE id = ?");
            $updDestinatario->execute([$qtd_btc, $destinatario['id']]);

            // Registra histórico para os dois lados
            $log1 = $pdo->prepare("INSERT INTO transacoes (usuarios_id, tipo, valor_brl, quantidade_btc, data_transacao) VALUES (?, 'transferencia', 0, ?, NOW())");
            $log1->execute([$usuario_id, $qtd_btc]);

            $log2 = $pdo->prepare("INSERT INTO transacoes (usuarios_id, tipo, valor_brl, quantidade_btc, data_transacao) VALUES (?, 'recebimento_corretora', 0, ?, NOW())");
            $log2->execute([$destinatario['id'], $qtd_btc]);

            $pdo->commit();
            echo json_encode(['sucesso' => true, 'mensagem' => 'Transferência realizada com sucesso!']);
            exit;
        }

        throw new Exception('Ação não reconhecida.');

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
        exit;
    }
}
?>