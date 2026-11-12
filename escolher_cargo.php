<?php

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.html");
    exit();
}

$cargos = $_SESSION["cargos"];
$classeBody = "pagina-escolher-cargo";

function temCargo($cargo, $cargos) {
    return in_array($cargo, $cargos);
}

?>

<?php include __DIR__ . '/includes/header.php'; ?>

        <h2 class="titulo-cargos">Escolha seu perfil de acesso</h2>

        <p class="boas-vindas-cargos">
            Bem-vindo, <?php echo htmlspecialchars($_SESSION["usuario_nome"]); ?>!
        </p>

        <div class="container_cargos">

            <?php if (temCargo("Administrador", $cargos)): ?>

                <a href="administrador/index.php" class="card_cargo">
                    <h3>Administrador</h3>
                    <p>Acessar área administrativa</p>
                </a>

            <?php else: ?>

                <div class="card_cargo bloqueado">
                    <h3> Administrador</h3>
                    <p>Você não possui acesso a este cargo.</p>
                </div>

            <?php endif; ?>


            <?php if (temCargo("Comitê", $cargos)): ?>

                <a href="comite/index.php" class="card_cargo">
                    <h3>Comitê</h3>
                    <p>Acessar área do comitê</p>
                </a>

            <?php else: ?>

                <div class="card_cargo bloqueado">
                    <h3> Comitê</h3>
                    <p>Você não possui acesso a este cargo.</p>
                </div>

            <?php endif; ?>


            <?php if (temCargo("Pesquisador", $cargos)): ?>

                <a href="pesquisador/index.php" class="card_cargo">
                    <h3>Pesquisador</h3>
                    <p>Acessar área do pesquisador</p>
                </a>

            <?php else: ?>

                <div class="card_cargo bloqueado">
                    <h3> Pesquisador</h3>
                    <p>Você não possui acesso a este cargo.</p>
                </div>

            <?php endif; ?>


            <?php if (temCargo("Validador", $cargos)): ?>

                <a href="validador/index.php" class="card_cargo">
                    <h3>Validador</h3>
                    <p>Acessar área do validador</p>
                </a>

            <?php else: ?>

                <div class="card_cargo bloqueado">
                    <h3> Validador</h3>
                    <p>Você não possui acesso a este cargo.</p>
                </div>

            <?php endif; ?>

        </div>

<?php include __DIR__ . '/includes/footer.php'; ?>
