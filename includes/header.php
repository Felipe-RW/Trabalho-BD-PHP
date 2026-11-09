<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$inicioUrl = '/Trabalho-BD-PHP/index.html';
$usuarioId = (int) ($_SESSION['usuario_id'] ?? $_SESSION['id'] ?? 0);

if ($usuarioId > 0) {
    require_once __DIR__ . '/conexao.php';

    $consultaCargo = $conexao->prepare(
        'SELECT c.titulo
         FROM usuario_cargo uc
         JOIN cargo c ON c.id = uc.cargo_id
         WHERE uc.usuario_id = :usuario_id
         ORDER BY c.id
         LIMIT 1'
    );
    $consultaCargo->execute([':usuario_id' => $usuarioId]);
    $cargoAtual = $consultaCargo->fetchColumn();

    $destinosPorCargo = [
        'Pesquisador' => '/Trabalho-BD-PHP/pesquisador/index.php',
        'Validador' => '/Trabalho-BD-PHP/validador/index.php',
        'Comitê' => '/Trabalho-BD-PHP/comite/index.php',
        'Administrador' => '/Trabalho-BD-PHP/administrador/index.php',
    ];

    if (isset($destinosPorCargo[$cargoAtual])) {
        $inicioUrl = $destinosPorCargo[$cargoAtual];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco de Dados Embrapa</title>
    <link rel="stylesheet" href="/Trabalho-BD-PHP/css/estilo.css">
</head>
<body>
    <header>
        <h1>Embrapa</h1>
        <nav>
            <a href="<?= htmlspecialchars($inicioUrl, ENT_QUOTES, 'UTF-8') ?>">Início</a>
            <a class="btn btn-pequeno btn-perigo" href="/Trabalho-BD-PHP/includes/logout.php">Sair</a>
        </nav>
    </header>
    <main>
