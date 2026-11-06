<?php

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.html");
    exit();
}

$cargos = $_SESSION["cargos"];

function temCargo($cargo, $cargos) {
    return in_array($cargo, $cargos);
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Escolher Cargo - Embrapa</title>

    <link rel="stylesheet" href="css/estilo.css">

</head>

<body>

    <header>
        <h1>Embrapa</h1>
    </header>

    <main>

        <h2>Escolha seu cargo de acesso</h2>

        <p>
            Bem-vindo, <?php echo htmlspecialchars($_SESSION["usuario_nome"]); ?>!
        </p>

        <div class="container_cargos">

            <!-- Administrador -->
            <?php if (temCargo("Administrador", $cargos)): ?>

                <a href="administrador/index.php" class="card_cargo">
                    <h3>Administrador</h3>
                    <p>Acessar área administrativa</p>
                </a>

            <?php else: ?>

                <div class="card_cargo bloqueado">
                    <h3>🔒 Administrador</h3>
                    <p>Você não possui acesso a este cargo.</p>
                </div>

            <?php endif; ?>


            <!-- Comitê -->
            <?php if (temCargo("Comitê", $cargos)): ?>

                <a href="comite/index.php" class="card_cargo">
                    <h3>Comitê</h3>
                    <p>Acessar área do comitê</p>
                </a>

            <?php else: ?>

                <div class="card_cargo bloqueado">
                    <h3>🔒 Comitê</h3>
                    <p>Você não possui acesso a este cargo.</p>
                </div>

            <?php endif; ?>


            <!-- Pesquisador -->
            <?php if (temCargo("Pesquisador", $cargos)): ?>

                <a href="pesquisador/index.php" class="card_cargo">
                    <h3>Pesquisador</h3>
                    <p>Acessar área do pesquisador</p>
                </a>

            <?php else: ?>

                <div class="card_cargo bloqueado">
                    <h3>🔒 Pesquisador</h3>
                    <p>Você não possui acesso a este cargo.</p>
                </div>

            <?php endif; ?>


            <!-- Validador -->
            <?php if (temCargo("Validador", $cargos)): ?>

                <a href="validador/index.php" class="card_cargo">
                    <h3>Validador</h3>
                    <p>Acessar área do validador</p>
                </a>

            <?php else: ?>

                <div class="card_cargo bloqueado">
                    <h3>🔒 Validador</h3>
                    <p>Você não possui acesso a este cargo.</p>
                </div>

            <?php endif; ?>

        </div>

    </main>

    <footer>
        <p>Projeto de Banco de Dados</p>
    </footer>

</body>

</html>
