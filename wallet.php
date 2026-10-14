<?php
session_start();
require_once 'conexao.php';

$usuario_id = $_SESSION['usuario_id'] ?? 1;

// Busca os dados atualizados incluindo o saldo da Wallet Web3
$stmt = $pdo->prepare("SELECT saldo_brl, saldo_btc, saldo_wallet_btc, chave_carteira FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

$saldo_brl = $usuario ? (float)$usuario['saldo_brl'] : 0.00;
$saldo_btc_corretora = $usuario ? (float)$usuario['saldo_btc'] : 0.00000000;
$saldo_wallet_btc = $usuario ? (float)($usuario['saldo_wallet_btc'] ?? 0.00000000) : 0.00000000;

$chave_hash = (!empty($usuario['chave_carteira'])) 
    ? $usuario['chave_carteira'] 
    : '0x' . bin2hex(random_bytes(16));

// Busca histórico específico da Wallet
$movimentacoes = [];
if ($usuario_id) {
    $stmt_trans = $pdo->prepare("SELECT tipo, valor_brl, quantidade_btc, data_transacao FROM transacoes WHERE usuarios_id = ? AND tipo IN ('deposito_wallet', 'resgate_corretora', 'envio_wallet', 'recebimento_wallet') ORDER BY data_transacao DESC LIMIT 6");
    $stmt_trans->execute([$usuario_id]);
    $movimentacoes = $stmt_trans->fetchAll();
} 
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Void Wallet</title>
    <link rel="stylesheet" href="saldo.css">
    <link rel="stylesheet" href="wallet.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="navbar">
        <div class="logo">Void<span class="badge-tag">WALLET</span></div>
        <nav class="nav-links">
            <a href="index.php" class="nav-item">Corretora</a>
            <a href="wallet.php" class="nav-item active">Minha Wallet</a>
        </nav>
    </header>

    <main class="dashboard-container">
        <!-- Cards Superiores de Métricas -->
        <section class="grid-cards">
            <div class="card highlight-card">
                <span class="card-label">Saldo em Custódia (Wallet)</span>
                <div class="card-valor highlight">
                    <?php echo number_format($saldo_wallet_btc, 8, '.', ','); ?> <span class="unit">BTC</span>
                </div>
                <small class="subtext-valor" id="estimativa-brl">≈ R$ 0,00</small>
            </div>

            <div class="card">
                <span class="card-label">Disponível na Corretora</span>
                <div class="card-valor">
                    <?php echo number_format($saldo_btc_corretora, 8, '.', ','); ?> <span class="unit">BTC</span>
                </div>
                <small class="subtext-valor">R$ <?php echo number_format($saldo_brl, 2, ',', '.'); ?> BRL</small>
            </div>

            <div class="card">
                <span class="card-label">Cotação Atual Bitcoin</span>
                <div class="card-valor" id="cotacao-btc-display">
                    R$ 400.000,00
                </div>
                <span class="badge-cotacao" id="status-cotacao">Carregando...</span>
            </div>
        </section>

        <!-- Ações Rápidas da Wallet -->
        <section class="acoes-section">
            <button class="btn btn-primary" onclick="abrirModal('modal-puxar-corretora')">Importar da Corretora</button>
            <button class="btn btn-secondary" onclick="abrirModal('modal-resgatar-corretora')">Converter & Enviar BRL</button>
            <button class="btn btn-outline" onclick="abrirModal('modal-receber-wallet')">Receber</button>
            <button class="btn btn-outline" onclick="abrirModal('modal-enviar-chave')">Enviar p/ Chave</button>
        </section>

        <!-- Conteúdo Principal -->
        <div class="grid-principal">
            <!-- Painel de Movimentações -->
            <section class="painel-box">
                <h2>Histórico da Wallet</h2>
                <div class="lista-movimentacoes">
                    <?php if (empty($movimentacoes)): ?>
                        <p class="empty-msg">Nenhuma movimentação na carteira até o momento.</p>
                    <?php else: ?>
                        <?php foreach ($movimentacoes as $m): ?>
                            <div class="item-movimentacao">
                                <span class="badge-tipo <?php echo $m['tipo']; ?>">
                                    <?php 
                                        $labels = [
                                            'deposito_wallet' => 'ENTRADA CORRETORA',
                                            'resgate_corretora' => 'SAÍDA BRL',
                                            'envio_wallet' => 'ENVIADO',
                                            'recebimento_wallet' => 'RECEBIDO'
                                        ];
                                        echo $labels[$m['tipo']] ?? $m['tipo'];
                                    ?>
                                </span>
                                <div class="detalhe-movimentacao">
                                    <?php if ($m['tipo'] == 'resgate_corretora'): ?>
                                        <span>R$ <?php echo number_format($m['valor_brl'], 2, ',', '.'); ?></span>
                                        <small>-<?php echo number_format($m['quantidade_btc'], 8, '.', ''); ?> BTC</small>
                                    <?php else: ?>
                                        <span><?php echo number_format($m['quantidade_btc'], 8, '.', ''); ?> BTC</span>
                                        <small><?php echo date('d/m/Y H:i', strtotime($m['data_transacao'])); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Endereço Público / Chave de Recebimento -->
            <section class="painel-box">
                <h2>Chave de Recebimento</h2>
                <p class="subtexto">Qualquer usuário pode enviar Bitcoin diretamente para você utilizando esta chave pública único.</p>
                
                <div class="qr-container text-center">
                    <div class="hash-box">
                        <span id="minha-chave-hash"><?php echo htmlspecialchars($chave_hash); ?></span>
                    </div>
                    <button class="btn btn-copy" onclick="copiarHash()">Copiar Endereço</button>
                </div>
            </section>
        </div>
    </main>

  
    <div id="modal-puxar-corretora" class="modal-overlay">
        <div class="modal-box">
            <h3>Importar da Corretora</h3>
            <p>Transfira BTC da sua conta na Corretora para a sua Wallet.</p>
            <label for="puxar-qtd-btc">Quantidade em BTC:</label>
            <input type="number" id="puxar-qtd-btc" placeholder="Ex: 0.00050000" step="0.00000001" min="0">
            <div class="modal-botoes">
                <button class="btn-cancel" onclick="fecharModal('modal-puxar-corretora')">Cancelar</button>
                <button class="btn-confirm" onclick="executarAcaoWallet('importar_corretora')">Confirmar Transferência</button>
            </div>
        </div>
    </div>

    <!-- Modal 2: Converter e Enviar para Corretora -->
    <div id="modal-resgatar-corretora" class="modal-overlay">
        <div class="modal-box">
            <h3>Converter & Enviar p/ Corretora</h3>
            <p>Converte BTC em R$ na cotação atual e credita no seu patrimônio na corretora.</p>
            <label for="resgatar-qtd-btc">Quantidade de BTC a converter:</label>
            <input type="number" id="resgatar-qtd-btc" placeholder="Ex: 0.00010000" step="0.00000001" min="0">
            <div class="modal-botoes">
                <button class="btn-cancel" onclick="fecharModal('modal-resgatar-corretora')">Cancelar</button>
                <button class="btn-confirm" onclick="executarAcaoWallet('converter_resgatar')">Converter para BRL</button>
            </div>
        </div>
    </div>

    <!-- Modal 3: Receber -->
    <div id="modal-receber-wallet" class="modal-overlay">
        <div class="modal-box text-center">
            <h3>Seu Endereço de Recebimento</h3>
            <p>Forneça a chave pública abaixo para receber de outros usuários:</p>
            <div class="hash-box"><?php echo htmlspecialchars($chave_hash); ?></div>
            <div class="modal-botoes">
                <button class="btn-cancel" onclick="fecharModal('modal-receber-wallet')">Fechar</button>
                <button class="btn-confirm" onclick="copiarHash()">Copiar Chave</button>
            </div>
        </div>
    </div>

    <!-- Modal 4: Enviar p/ Chave -->
    <div id="modal-enviar-chave" class="modal-overlay">
        <div class="modal-box">
            <h3>Enviar p/ Outra Carteira</h3>
            <label for="enviar-qtd-btc">Quantidade em BTC:</label>
            <input type="number" id="enviar-qtd-btc" placeholder="Ex: 0.00010000" step="0.00000001" min="0">
            <label for="enviar-chave-destino">Chave Pública do Destinatário:</label>
            <input type="text" id="enviar-chave-destino" placeholder="0x...">
            <div class="modal-botoes">
                <button class="btn-cancel" onclick="fecharModal('modal-enviar-chave')">Cancelar</button>
                <button class="btn-confirm" onclick="executarAcaoWallet('transferir_chave')">Enviar BTC</button>
            </div>
        </div>
    </div>

    <script>
        let cotacaoAtualBTC = 400000.00;
        const saldoWalletBtc = <?php echo $saldo_wallet_btc; ?>;

        function oscilarCotacao() {
            const variacao = (Math.random() * 4000 - 2000);
            cotacaoAtualBTC = Math.max(300000, cotacaoAtualBTC + variacao);
            
            const display = document.getElementById('cotacao-btc-display');
            const status = document.getElementById('status-cotacao');
            const estimativa = document.getElementById('estimativa-brl');
            
            display.innerText = 'R$ ' + cotacaoAtualBTC.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            const totalEmBrl = saldoWalletBtc * cotacaoAtualBTC;
            estimativa.innerText = '≈ R$ ' + totalEmBrl.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            if (variacao >= 0) {
                status.innerText = 'Alta (' + (variacao / 4000).toFixed(2) + '%)';
                status.className = 'badge-cotacao alta';
            } else {
                status.innerText = 'Queda (' + (variacao / 4000).toFixed(2) + '%)';
                status.className = 'badge-cotacao queda';
            }
        }
        
        oscilarCotacao();
        setInterval(oscilarCotacao, 4000);

        function abrirModal(idModal) {
            document.getElementById(idModal).style.display = 'flex';
        }

        function fecharModal(idModal) {
            document.getElementById(idModal).style.display = 'none';
        }

        function copiarHash() {
            const hashTexto = document.getElementById('minha-chave-hash').innerText;
            navigator.clipboard.writeText(hashTexto);
            alert('Chave de recebimento copiada com sucesso!');
        }

        function executarAcaoWallet(tipoAcao) {
            const formData = new FormData();
            formData.append('acao', tipoAcao);
            formData.append('cotacao_btc', cotacaoAtualBTC);

            if (tipoAcao === 'importar_corretora') {
                const qtd = document.getElementById('puxar-qtd-btc').value;
                if (!qtd) return alert('Insira a quantidade em BTC!');
                formData.append('quantidade_btc', qtd);
            } 
            else if (tipoAcao === 'converter_resgatar') {
                const qtd = document.getElementById('resgatar-qtd-btc').value;
                if (!qtd) return alert('Insira a quantidade em BTC!');
                formData.append('quantidade_btc', qtd);
            } 
            else if (tipoAcao === 'transferir_chave') {
                const qtd = document.getElementById('enviar-qtd-btc').value;
                const chave = document.getElementById('enviar-chave-destino').value;
                if (!qtd || !chave) return alert('Preencha todos os campos!');
                formData.append('quantidade_btc', qtd);
                formData.append('chave_destino', chave);
            }

            fetch('processar_wallet.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    alert(data.mensagem);
                    location.reload();
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(() => alert('Erro na comunicação com o servidor.'));
        }
    </script>
</body>
</html>