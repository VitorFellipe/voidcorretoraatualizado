<?php
session_start();
require_once 'conexao.php';

// Redireciona para o login caso não esteja logado (evita o erro de Usuário não encontrado)
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php'); // Altere para o nome da sua tela de login
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Garante a busca do usuário no banco (incluindo o nome)
$stmt = $pdo->prepare("SELECT nome, saldo_brl, saldo_btc, chave_carteira FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$nome_usuario = $usuario['nome'] ?? $_SESSION['usuario_nome'] ?? 'Usuário';
$saldo_brl = (float)$usuario['saldo_brl'];
$saldo_btc = (float)$usuario['saldo_btc'];

// Chave Hash dinâmica ou fallback
$chave_hash = (!empty($usuario['chave_carteira'])) 
    ? $usuario['chave_carteira'] 
    : '0x' . bin2hex(random_bytes(16));

// Busca histórico de movimentações
$movimentacoes = [];
if ($usuario_id) {
    $stmt_trans = $pdo->prepare("SELECT tipo, valor_brl, quantidade_btc, data_transacao FROM transacoes WHERE usuarios_id = ? ORDER BY data_transacao DESC LIMIT 6");
    $stmt_trans->execute([$usuario_id]);
    $movimentacoes = $stmt_trans->fetchAll();
} 
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corretora Void</title>
    <link rel="stylesheet" href="saldo.css">
    <link rel="stylesheet" href="wallet.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="navbar">
        <div class="logo">Corretora Void</div>
        
        <!-- Menu com botão para a Wallet e Nome do Usuário -->
        <nav class="nav-links" style="display: flex; align-items: center; gap: 15px;">
            <a href="wallet.php" class="btn btn-outline" style="text-decoration: none; padding: 6px 14px; font-size: 0.85rem;">Wallet</a>
            <div class="user-profile" style="color: #00e9ab; font-weight: 600; font-size: 0.9rem;">
                Olá, <?php echo htmlspecialchars($nome_usuario); ?>
            </div>
        </nav>
    </header>

    <main class="dashboard-container">
        <!-- Cards Superiores de Métricas -->
        <section class="grid-cards">
            <div class="card">
                <span class="card-label">Patrimônio</span>
                <div class="card-valor highlight">
                    R$ <?php echo number_format($saldo_brl, 2, ',', '.'); ?>
                </div>
            </div>

            <div class="card">
                <span class="card-label">Cotação Bitcoin</span>
                <div class="card-valor" id="cotacao-btc-display">
                    R$ 400.000,00
                </div>
                <span class="badge-cotacao" id="status-cotacao">Carregando...</span>
            </div>

            <div class="card">
                <span class="card-label">Meus Ativos</span>
                <div class="card-valor">
                    <?php echo number_format($saldo_btc, 8, '.', ','); ?> <span class="unit">BTC</span>
                </div>
            </div>
        </section>

        <!-- Ações Rápidas -->
        <section class="acoes-section">
            <button class="btn btn-primary" onclick="abrirModal('modal-comprar')">Comprar</button>
            <button class="btn btn-secondary" onclick="abrirModal('modal-vender')">Vender</button>
            <button class="btn btn-outline" onclick="abrirModal('modal-receber')">Receber</button>
            <button class="btn btn-outline" onclick="abrirModal('modal-transferir')">Transferir</button>
        </section>

        <!-- Conteúdo Principal em 2 Colunas -->
        <div class="grid-principal">
            <!-- Painel de Movimentações -->
            <section class="painel-box">
                <h2>Histórico de Movimentações</h2>
                <div class="lista-movimentacoes">
                    <?php if (empty($movimentacoes)): ?>
                        <p class="empty-msg">Nenhuma movimentação até o momento.</p>
                    <?php else: ?>
                        <?php foreach ($movimentacoes as $m): ?>
                            <div class="item-movimentacao">
                                <span class="badge-tipo <?php echo $m['tipo']; ?>"><?php echo strtoupper($m['tipo']); ?></span>
                                <div class="detalhe-movimentacao">
                                    <?php if ($m['tipo'] == 'compra'): ?>
                                        <span>R$ <?php echo number_format($m['valor_brl'], 2, ',', '.'); ?></span>
                                        <small>+<?php echo number_format($m['quantidade_btc'], 6, '.', ''); ?> BTC</small>
                                    <?php elseif ($m['tipo'] == 'venda'): ?>
                                        <span>R$ <?php echo number_format($m['valor_brl'], 2, ',', '.'); ?></span>
                                        <small>-<?php echo number_format($m['quantidade_btc'], 6, '.', ''); ?> BTC</small>
                                    <?php else: ?>
                                        <span>Transferência</span>
                                        <small>- <?php echo number_format($m['quantidade_btc'], 6, '.', ''); ?> BTC</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Painel de Informações da Carteira -->
            <section class="painel-box">
                <h2>Sua Carteira</h2>
                <p class="subtexto">Utilize seu endereço público para receber Bitcoin.</p>
                <div class="hash-box">
                    <span id="minha-chave-hash"><?php echo htmlspecialchars($chave_hash); ?></span>
                </div>
                <button class="btn btn-copy" onclick="copiarHash()">Copiar Chave</button>
            </section>
        </div>

        <!-- Seção de Dúvidas Frequentes -->
        <section class="painel-box faq-section">
            <h2>Dúvidas Frequentes</h2>
            <div class="faq-grid">
                <details>
                    <summary>O que é o Bitcoin?</summary>
                    <p>O Bitcoin é uma moeda digital descentralizada que permite transações diretas sem intermediários.</p>
                </details>
                <details>
                    <summary>A Void é segura?</summary>
                    <p>Utilizamos os padrões mais modernos de criptografia para proteger seus ativos e dados.</p>
                </details>
                <details>
                    <summary>Por que apenas Bitcoin?</summary>
                    <p>Focamos no ativo digital mais robusto e seguro do mercado para garantir estabilidade.</p>
                </details>
            </div>
        </section>
    </main>

    <!-- MODAIS -->
    <div id="modal-comprar" class="modal-overlay">
        <div class="modal-box">
            <h3>Comprar Bitcoin</h3>
            <p>Converter BRL do Patrimônio para BTC em meus ativos.</p>
            <label for="compra-valor-brl">Valor a investir em R$:</label>
            <input type="number" id="compra-valor-brl" placeholder="Ex: 50.00" step="1.0" min="0">
            <div class="modal-botoes">
                <button class="btn-cancel" onclick="fecharModal('modal-comprar')">Cancelar</button>
                <button class="btn-confirm" onclick="executarAcao('comprar')">Confirmar Compra</button>
            </div>
        </div>
    </div>

    <div id="modal-vender" class="modal-overlay">
        <div class="modal-box">
            <h3>Vender Bitcoin</h3>
            <p>Converter BTC de Meus Ativos para R$ no Patrimônio.</p>
            <label for="venda-qtd-btc">Quantidade da venda:</label>
            <input type="number" id="venda-qtd-btc" placeholder="Ex: 0.0001" step="0.0001" min="0">
            <div class="modal-botoes">
                <button class="btn-cancel" onclick="fecharModal('modal-vender')">Cancelar</button>
                <button class="btn-confirm" onclick="executarAcao('vender')">Confirmar Venda</button>
            </div>
        </div>
    </div>

    <div id="modal-receber" class="modal-overlay">
        <div class="modal-box text-center">
            <h3>Endereço da Wallet</h3>
            <p>Utilize a Chave Hash abaixo para receber transferências:</p>
            <div class="hash-box"><?php echo htmlspecialchars($chave_hash); ?></div>
            <div class="modal-botoes">
                <button class="btn-cancel" onclick="fecharModal('modal-receber')">Fechar</button>
                <button class="btn-confirm" onclick="copiarHash()">Copiar Chave</button>
            </div>
        </div>
    </div>

    <div id="modal-transferir" class="modal-overlay">
        <div class="modal-box">
            <h3>Transferir Bitcoin</h3>
            <label for="transf-qtd-btc">Quantidade em BTC:</label>
            <input type="number" id="transf-qtd-btc" placeholder="Ex: 0.00005000" step="0.00000001" min="0">
            <label for="transf-chave-destino">Chave de Destino:</label>
            <input type="text" id="transf-chave-destino" placeholder="Cole a chave aqui:">
            <div class="modal-botoes">
                <button class="btn-cancel" onclick="fecharModal('modal-transferir')">Cancelar</button>
                <button class="btn-confirm" onclick="executarAcao('transferir')">Enviar</button>
            </div>
        </div>
    </div>

    <script>
        let cotacaoAtualBTC = 400000.00;

        function oscilarCotacao() {
            const variacao = (Math.random() * 4000 - 2000);
            cotacaoAtualBTC = Math.max(300000, cotacaoAtualBTC + variacao);
            
            const display = document.getElementById('cotacao-btc-display');
            const status = document.getElementById('status-cotacao');
            
            display.innerText = 'R$ ' + cotacaoAtualBTC.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            if (variacao >= 0) {
                status.innerText = 'Alta (' + (variacao / 4000).toFixed(2) + '%)';
                status.className = 'badge-cotacao alta';
            } else {
                status.innerText = 'Queda (' + (variacao / 4000).toFixed(2) + '%)';
                status.className = 'badge-cotacao queda';
            }
        }
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
            alert('Chave Hash copiada para a área de transferência!');
        }

        function executarAcao(tipoAcao) {
            const formData = new FormData();
            formData.append('acao', tipoAcao);
            formData.append('cotacao_btc', cotacaoAtualBTC); // Envia a cotação atualizada

            if (tipoAcao === 'comprar') {
                const valor = document.getElementById('compra-valor-brl').value;
                if (!valor) return alert('Insira um valor!');
                formData.append('valor_brl', valor);
            } 
            else if (tipoAcao === 'vender') {
                const qtd = document.getElementById('venda-qtd-btc').value;
                if (!qtd) return alert('Insira uma quantidade de BTC!');
                formData.append('quantidade_btc', qtd);
            } 
            else if (tipoAcao === 'transferir') { 
                const qtd = document.getElementById('transf-qtd-btc').value;
                const chave = document.getElementById('transf-chave-destino').value;
                if (!qtd || !chave) return alert('Preencha a quantidade e a chave!');
                formData.append('quantidade_btc', qtd);
                formData.append('chave_destino', chave);
            }

            fetch('processar_operacao.php', {
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
            .catch(() => alert('Ocorreu um erro ao processar a requisição.'));
        }
    </script>
</body>
</html>