Testando, Aplicando, Estudando e aprendendo código, voltado para o aprendizado, não envolve valores reais é apenas um PROJETO feito em uma boa linguagem PHP em um excelente BANCO DE DADOS o MY SQL apenas estudos, codigos e nada mais!

endpoint: /corretora.php
 "http" => [
    "method" => "GET",
    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
    "timeout" => 5
 ]
 ];

 $context = stream_context_create($contextOptions);

  $apiUrl = "https://economia.awesomeapi.com.br/json/last/BTC-BRL,BTC-USD";

 $response = @file_get_contents($apiUrl, false, $context);

 if ($response !== FALSE){
    $data = json_decode($response, true);

O QUE FAZ? 
UTILIZA METODO GET DE BUSCA, BUSCA API E EXIBE NA TELA!

endpoint: /teladelogin.html
<?php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = $_POST['senha'] ?? '';

    if (empty($usuario) || empty($senha)) {
        header("Location: criarconta.html?erro=campos_vazios");
        exit();
    }
    
    try{
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE nome = ?");
        $stmtCheck->execute([$usuario]);

        if ($stmtCheck->fetch()) {
            header("Location: criarconta.html?erro=usuario_existente");
            exit();
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $saldo_inicial_brl = 100.00;
        $saldo_inicial_btc = 0.00000000;
         $chavenova = '0x' . bin2hex(random_bytes(16));

        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, senha, saldo_brl, saldo_btc, saldo_wallet_btc, chave_carteira) VALUES (?, ?, ?, ?, ?, ?)");


       $sucesso = $stmt->execute([
        $usuario,
        $senhaHash,
        $saldo_inicial_brl,
        $saldo_inicial_btc,
         0.00000000,
        $chavenova
    ]);

    if ($sucesso) {
       $_SESSION['usuario_id'] = $pdo->lastInsertId();
       $_SESSION['usuario_nome'] = $usuario;

          header("Location: teladelogin.html?sucesso=1");
        exit();
        } else {
          header("Location: criarconta.html?erro=falha_sql");
        exit();
    }

    } catch (PDOException $e){
        header("Location: criarconta.html?erro=falha_sql");
        exit();
    }


    O QUE FAZ?
    UTILIZA METODO DE POST PARA CRIAR, CRIAR UMA CONTA DE USUARIO, AI PARA CRIAR ELE JA DA AS VARIAVEIS DO PHP DE SENHA, SALDO PARA CADA USUARIO E ENVIA PARA 
 
