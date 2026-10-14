<?php
session_start();
require_once 'conexao.php';

// Oculta avisos em produção
ini_set('display_errors', 0);
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($usuario) || empty($senha)) {
        die("Faltou preencher todos os campos, caro Investidor!");
    }

    try {
        // Busca o usuário usando PDO
        $stmt = $pdo->prepare("SELECT id, nome, senha FROM usuarios WHERE nome = ?");
        $stmt->execute([$usuario]);
        $dadosusuario = $stmt->fetch();

        // Verifica se encontrou o usuário e se a senha hash bate
        if ($dadosusuario && password_verify($senha, $dadosusuario['senha'])) {
            // Previne fixação de sessão por segurança
            session_regenerate_id(true);

            // Salva as variáveis cruciais na sessão para a corretora e wallet encontrarem o usuário
            $_SESSION['usuario_id'] = $dadosusuario['id'];
            $_SESSION['usuario_nome'] = $dadosusuario['nome'];

            // Redireciona para o painel principal (index.php)
            header("Location: corretora.php");
            exit();
        } else {
            echo "<h3 style='color:red; text-align:center;'>Usuário ou senha incorretos!</h3>";
            echo "<div style='text-align:center;'><a href='teladelogin.html'>Tente novamente, Magnata</a></div>";
        }
    } catch (PDOException $e) {
        die("Erro ao conectar ao banco de dados!");
    }
}
?>  