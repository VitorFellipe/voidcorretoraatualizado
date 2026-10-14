<?php
$host    = "localhost";
$usuario = "root";
$senha   = "";
$banco   = "void_db"; // Mantive o nome 'void_db' que está no seu arquivo

try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Falha na conexão: " . $e->getMessage());
}
?> 