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
 
} else {
    header("Location: criarconta.html");
    exit();
}
?>  