<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../index.html");
    exit();
}

if (!isset($cargoNecessario) ||
    !in_array($cargoNecessario, $_SESSION["cargos"])) {

    header("Location: ../escolher_cargo.php");
    exit();

}

?>