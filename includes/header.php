<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$inicioUrl = '/Trabalho-BD-PHP/escolher_cargo.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco de Dados Embrapa</title>
    <link rel="stylesheet" href="/Trabalho-BD-PHP/css/estilo.css">
</head>
<body<?= isset($classeBody) && $classeBody !== '' ? ' class="' . htmlspecialchars($classeBody, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
    <header>
        <h1>Embrapa</h1>
        <nav>
            <a href="<?= htmlspecialchars($inicioUrl, ENT_QUOTES, 'UTF-8') ?>">Início</a>
            <a class="btn btn-pequeno btn-perigo" href="/Trabalho-BD-PHP/includes/logout.php">Sair</a>
        </nav>
    </header>
    <main>
