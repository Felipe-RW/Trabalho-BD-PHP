<?php

$host = "localhost";
$banco = "embrapa_bd";
$usuario = "root";
$senhaBanco = "";

try {

    $conexao = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senhaBanco
    );

    $conexao->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $conexao->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $erro) {
    die("Erro ao conectar: " . $erro->getMessage());
}
?>