<?php
session_start();

if (!isset($_SESSION['usuario_id'])){
    header("Location: teladelogin.html");
    exit();
}
?>



<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Void Corretora</title>
    <link rel="stylesheet" href="corretora.css">
        
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=arrow_right_alt" />
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
      
<body>
    <header class="nav">
        <div class="inicionav">Bem-vindo a VoidCrypto</div>
        
        <div class="user-info">
            <span>Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></span>

            <div class="dropdown">
                <button id="btn-menu" class="btn-hamburguer" aria-label="Abrir menu">
                    <span></span>
                    <span></span>
                    <span></span>   
                </button>

                <div id="dropdown-conteudo" class="dropdown-menu">
                    <a href="index.php">Comprar</a>
                    <a href="renomear.php">Renomear</a>
                    <a href="excluirconta.php" onclick="return confirm('Tem certeja que quer deixar de ser um Magnata da Void?');">Excluir Conta</a>
                   <a href="logout.php" class="btn-sair">Sair</a>
        </div>
          </div>
       </div>
    </header>
 
    <nav class="nav1">  
        <div class="ativo">
            <h3>Acompanhe em tempo real </h3>
            <h3> O valor do <span class="destaque">Bitcoin</span></h3>
            <h3>suas altas e quedas!</h3>
        </div>

        <div class="cotacao-box">
            <h3>Cotação em Tempo Real</h3>
            <div class="preco-item">
                <span>USD:</span> <strong id="price-usd">Carregando...</strong>
            </div>
            <div class="preco-item">
                <span>BRL:</span> <strong id="price-brl">Carregando...</strong>
            </div>
            <div class="preco-item">
                <span>Variação (24h):</span> <strong id="change-24h">--%</strong>
            </div>
            <small id="last-updated">Aguardando atualização...</small>
        </div>
    </nav>

    <div class="melhor">
        <h4 class="melhorcripto">Por que o <span class="destaque">Bitcoin</span> é a melhor Criptomoeda?</h4>
    </div>

    <div style="position: relative; height: 350px; width: 100%; max-width: 800px; margin: 0 auto;">
        <canvas id="graficobitcoin"></canvas>
    </div>
      
    <script>

        const ctx = document.getElementById('graficobitcoin').getContext('2d');
        const anos = ['2009', '2013', '2017', '2021', '2024', 'Hoje'];
        const valores = [0.00076, 100, 19000, 68000, 65000, 90000];
        const graficoBtc = new Chart(ctx, {  
            type: 'line',
            data: {
                labels: anos,
                datasets: [{
                    label: 'Preço do Bitcoin (USD)',
                    data: valores,
                    borderColor: '#00ff00',
                    backgroundColor: 'rgba(6, 78, 59, 0.6)',   
                    fill: true, 
                    tension: 0.3             
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        async function atualizarCotacao() {
            try {
                const response = await fetch('cotacao.php');
                const data = await response.json();
                
                if (data.status === 'sucess' || data.status === 'fallback') {
                    const btc = data.bitcoin;

                    document.getElementById('price-usd').textContent = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(btc.usd);
                    document.getElementById('price-brl').textContent = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(btc.brl);
                    
                    const elVariacao = document.getElementById('change-24h');
                    elVariacao.textContent = (btc.usd_24h_change >= 0 ? '+' : '') + btc.usd_24h_change.toFixed(2) + '%';
                    elVariacao.style.color = btc.usd_24h_change >= 0 ? '#00ff00' : '#ff4d4d';

                    const agora = new Date();
                    document.getElementById('last-updated').textContent = `Atualizado às ${agora.toLocaleTimeString()}`;

                    graficoBtc.data.datasets[0].data[valores.length - 1] = btc.usd;
                    graficoBtc.update();
                }
            } catch (erro) {
                console.error('Erro ao buscar cotação:', erro);
            }
        }

        atualizarCotacao();
        setInterval(atualizarCotacao, 10000);


        const btnMenu = document.getElementById('btn-menu');
        const menuDropdown = document.getElementById('dropdown-conteudo');

        btnMenu.addEventListener('click', (event) => {
            event.stopPropagation();
            menuDropdown.classList.toggle('ativo');
        });

        document.addEventListener('click', () =>{
            menuDropdown.classList.remove('ativo');
        });
    </script>

    <footer>
        <h1>Direitos reservados a VoidCrypto</h1>
    </footer>
</body>
</html>