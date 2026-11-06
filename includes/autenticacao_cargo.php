<?php

session_start();

require_once "conexao.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $senha = $_POST["senha"];

    // Busca o usuário no banco.
    $sql = "SELECT * FROM usuario
            WHERE email = :email
            AND senha = :senha
            AND ativo = 1";

    $comando = $conexao->prepare($sql);

    $comando->bindValue(":email", $email);
    $comando->bindValue(":senha", $senha);

    $comando->execute();

    $usuario = $comando->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {

        // Guarda os dados do usuário na sessão.
        $_SESSION["usuario_id"] = $usuario["id"];
        $_SESSION["usuario_nome"] = $usuario["nome"];

        // Busca os cargos do usuário.
        $sqlCargos = "SELECT c.titulo
                      FROM cargo c
                      INNER JOIN usuario_cargo uc
                      ON c.id = uc.cargo_id
                      WHERE uc.usuario_id = :id";

        $comandoCargos = $conexao->prepare($sqlCargos);

        $comandoCargos->bindValue(":id", $usuario["id"]);

        $comandoCargos->execute();

        $cargos = $comandoCargos->fetchAll(PDO::FETCH_COLUMN);

        $_SESSION["cargos"] = $cargos;

        header("Location: ../escolher_cargo.php");
        exit();

    } else {

        echo "Email ou senha incorretos, ou usuário desativado.";

    }

}

?>