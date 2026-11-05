
<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/criar_acao_log.php';

$erros = [];
$nome = $email = $matricula = '';
$funcoes = [];

$opcoes = [
    'Pesquisador',
    'Validador',
    'Comitê',
    'Administrador'
];

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');

    $funcoes = array_values(
        array_intersect(
            $opcoes,
            (array) ($_POST['funcoes'] ?? [])
        )
    );

    if (mb_strlen($nome) < 3) {
        $erros[] = 'Informe o nome do empregado.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }

    if ($matricula === '') {
        $erros[] = 'Informe a matrícula.';
    }

    if (!$funcoes) {
        $erros[] = 'Selecione pelo menos um tipo de função.';
    }

    if (!$erros) {
        try {

            $stmt = $pdo->prepare(
                'INSERT INTO empregados (nome, email, matricula, funcoes)
                 VALUES (:nome, :email, :matricula, :funcoes)'
            );

            $stmt->execute([
                ':nome' => $nome,
                ':email' => strtolower($email),
                ':matricula' => $matricula,
                ':funcoes' => implode(',', $funcoes)
            ]);

            $empregado_id = (int) $pdo->lastInsertId();

            $usuario_id = (int) $_SESSION['usuario_id'];

            criarAcaoLog(
                $pdo,
                'Cadastro',
                'empregados',
                $empregado_id,
                $usuario_id
            );

            header('Location: index.php');
            exit;

        } catch (PDOException $ex) {

            $erros[] = $ex->getCode() === '23000'
                ? 'E-mail ou matrícula já cadastrados.'
                : 'Erro ao salvar o cadastro.';
        }
    }
}
