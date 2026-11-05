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

    echo "Conexão realizada com sucesso!";

} catch (PDOException $erro) {
    die("Erro ao conectar: " . $erro->getMessage());
}
?>