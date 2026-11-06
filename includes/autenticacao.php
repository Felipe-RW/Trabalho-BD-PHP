<?php

session_start();

require_once "conexao.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    if (!$email || $senha === '') {
        header("Location: ../index.html");
        exit();
    }

    try {
        $sql = "SELECT id, nome, email, senha, ativo
                FROM usuario
                WHERE email = :email
                LIMIT 1";

        $consulta = $conexao->prepare($sql);

        $consulta->execute([
            'email' => $email
        ]);

        $dados = $consulta->fetch(PDO::FETCH_ASSOC);

        if ($dados && $dados['ativo'] && $senha === $dados['senha']) {

            session_regenerate_id(true);

            $_SESSION['id'] = $dados['id'];
            $_SESSION['usuario'] = $dados['nome'];
            $_SESSION['email'] = $dados['email'];

            header("Location: ../ escolher_cargo.php");
            exit();

        } else {

            echo "E-mail ou senha incorretos!";
            echo "<br><a href='../index.html'>Tentar novamente</a>";

        }

    } catch (PDOException $erro) {

        error_log($erro->getMessage());
        die("Erro ao realizar login.");

    }

} else {

    header("Location: ../index.html");
    exit();

}
?>