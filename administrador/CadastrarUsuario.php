<?php


header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Use o método POST.');
}


try {
    $pdo = new PDO('mysql:host=localhost;dbname=sistema;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados.');
}


$opcoes    = ['Pesquisador', 'Validador', 'Comitê', 'Administrador'];
$nome      = trim($_POST['nome'] ?? '');
$email     = trim($_POST['email'] ?? '');
$matricula = trim($_POST['matricula'] ?? '');
$funcoes   = array_values(array_intersect($opcoes, (array) ($_POST['funcoes'] ?? [])));


$erros = [];
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
if ($erros) {
    http_response_code(422);
    exit(implode("\n", $erros));
}


try {
    $stmt = $pdo->prepare(
        'INSERT INTO empregados (nome, email, matricula, funcoes) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$nome, strtolower($email), $matricula, implode(',', $funcoes)]);
    $id = $pdo->lastInsertId();
    echo "Empregado cadastrado com sucesso! ID: $id";
} catch (PDOException $ex) {
    if ($ex->getCode() === '23000') {
        http_response_code(409);
        exit('E-mail ou matrícula já cadastrados.');
    }
    http_response_code(500);
    exit('Erro ao salvar o cadastro.');
}