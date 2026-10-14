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
        <div class="inicionav">Bem-vindo a VoidCrypto </div>
    </header>
    <nav class="nav1">
             <div class="ativo">
        <h3>Acompanhe em tempo real </h3>
         <h3> O valor do <span class="destaque">Bitcoin</span></h3>
        <h3>suas altas e quedas!</h3>
        <span class="material-symbols-outlined seta">arrow_right_alt</span>

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
            fetch('https://economia.awesomeapi.com.br/json/last/BTC-USD')
            .then(response => response.json())
            .then(data =>{
                const precoAtual = parseFloat(data.BTCUSD.bid);
                graficoBtc.data.datasets[0].data[valores.length - 1] = precoAtual;
                graficoBtc.update();
            })
            .catch(erro => console.error('Erro ao buscar cotação', erro));

        </script>

        <footer>
            <h1>Direitos reservados a VoidCrypto</h1>
        </footer>
</body>
</html> 