<?php
require_once '../includes/conexao.php';
include '../includes/header.php';

$titulo = '';
$categoria_id = '';
$data_de_realizacao = '';
$descricao = '';
$erro = '';

$sql = "SELECT id, nome FROM categoria ORDER BY nome";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo = trim($_POST['titulo'] ?? '');
    $categoria_id = trim($_POST['categoria_id'] ?? '');
    $data_de_realizacao = trim($_POST['data_de_realizacao'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if (
        empty($titulo) ||
        empty($categoria_id) ||
        empty($data_de_realizacao) ||
        empty($descricao)
    ) {
        $erro = 'Preencha todos os campos!';
    } else {

        try {

            $sql = "INSERT INTO acao
                    (titulo, categoria_id, data_de_realizacao, descricao)
                    VALUES
                    (:titulo, :categoria_id, :data_de_realizacao, :descricao)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':titulo' => $titulo,
                ':categoria_id' => $categoria_id,
                ':data_de_realizacao' => $data_de_realizacao,
                ':descricao' => $descricao
            ]);

            header('Location: index.php?sucesso=1');
            exit;

        } catch (PDOException $e) {
            $erro = 'Erro ao cadastrar ação. Verifique os dados informados.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Banco de Dados Embrapa</title>

    <link rel="stylesheet" href="../css/estilo.css">
</head>
<body>
<main>
    <div class="frame_login">

        <div class="menu_administrador">

            <h1>Enviar Ação</h1>

            <?php if (!empty($erro)): ?>
                <p style="color: red;">
                    <?= htmlspecialchars($erro) ?>
                </p>
            <?php endif; ?>

            <form method="POST">

                <label>Título da Ação:</label>

                <input
                    type="text"
                    name="titulo"
                    maxlength="100"
                    value="<?= htmlspecialchars($titulo) ?>"
                    required
                >

                <br>

                <label>Categoria:</label>

                <select name="categoria_id" required>

                    <option value="">Selecione uma categoria</option>

                    <?php foreach ($categorias as $categoria): ?>

                        <option
                            value="<?= $categoria['id'] ?>"
                            <?= ($categoria_id == $categoria['id']) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($categoria['nome']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <br>

                <label>Data de realização:</label>

                <input
                    type="date"
                    name="data_de_realizacao"
                    value="<?= htmlspecialchars($data_de_realizacao) ?>"
                    required
                >

                <br>

                <label>Descrição:</label>

                <textarea
                    name="descricao"
                    required
                ><?= htmlspecialchars($descricao) ?></textarea>

                <br>

                <button type="submit">Salvar</button>

                <a href="index.php">Cancelar</a>

            </form>
        </div>
    </div>
</main>
</body>
</html>
<?php include '../includes/footer.php'; ?>