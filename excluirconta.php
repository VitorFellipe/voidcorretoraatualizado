<?php
session_start();

if(!isset($_SESSION['usuario_id'])){
    header("Location: teladelogin.html");
    exit();
}   

require_once 'conexao.php';

$usuario_id = $_SESSION['usuario_id'];

$sql = "DELETE FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $stmt->close();

}
session_unset();
session_destroy();

header("Location: teladelogin.html?msg=conta_excluida");
exit();
?> 