<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria = trim($_POST['categoria'] ?? '');

    $busca_id = $pdo->prepare(
        'SELECT id FROM categoria WHERE nome = :categoria'
    );
    $busca_id->execute([':categoria' => $categoria]);
    $categoria_id = $busca_id->fetchColumn();

    if ($categoria_id === false) {
        throw new Exception('Categoria não encontrada.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO acao (titulo, descricao, data_de_realizacao, categoria_id)
         VALUES (:titulo, :descricao, :data_de_realizacao, :categoria_id)'
    );
    $stmt->execute([
        ':titulo' => $_POST['titulo'],
        ':descricao' => $_POST['descricao'],
        ':data_de_realizacao' => $_POST['data_de_realizacao'],
        ':categoria_id' => (int) $categoria_id,
    ]);

    header('Location: index.php');
    exit;
}

?>
