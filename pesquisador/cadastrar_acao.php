<?php
session_start();

require '../includes/conexao.php';
require '../includes/criar_acao_log.php';

$titulo = '';
$categoria_id = '';
$descricao = '';
$data_de_realizacao = '';
$erro = '';

$categorias = $conexao
    ->query('SELECT id, nome FROM categoria ORDER BY nome')
    ->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $categoria_id = trim($_POST['categoria_id'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $data_de_realizacao = trim($_POST['data_de_realizacao'] ?? '');

    if ($titulo === '' || $categoria_id === '' || $descricao === '' || $data_de_realizacao === '') {
        $erro = 'Preencha todos os campos.';
    } elseif (!isset($_SESSION['usuario_id'])) {
        $erro = 'Faça login novamente para cadastrar uma ação.';
    } else {
        try {
            $conexao->beginTransaction();

            $stmt = $conexao->prepare(
                'INSERT INTO acao (titulo, descricao, data_de_realizacao, categoria_id)
                 VALUES (:titulo, :descricao, :data_de_realizacao, :categoria_id)'
            );
            $stmt->execute([
                ':titulo' => $titulo,
                ':descricao' => $descricao,
                ':data_de_realizacao' => $data_de_realizacao,
                ':categoria_id' => (int) $categoria_id,
            ]);

            $acao_id = (int) $conexao->lastInsertId();
            $usuario_id = (int) $_SESSION['usuario_id'];

            $vinculo = $conexao->prepare(
                'INSERT INTO acao_usuario (usuario_id, acao_id)
                 VALUES (:usuario_id, :acao_id)'
            );
            $vinculo->execute([
                ':usuario_id' => $usuario_id,
                ':acao_id' => $acao_id,
            ]);

            criarAcaoLog($conexao, 'Cadastro', 'acao', $acao_id, $usuario_id);
            $conexao->commit();

            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            error_log($e->getMessage());
            $erro = 'Não foi possível cadastrar a ação. Confira os dados e tente novamente.';
        }
    }
}

include '../includes/header.php';
?>

<section class="cadastro-acao-pagina">
    <h2>Cadastro de ação</h2>

    <?php if ($erro !== ''): ?>
        <p class="aviso" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="cadastrar_acao.php">
        <label for="titulo">Título da ação:</label>
        <input
            type="text"
            id="titulo"
            name="titulo"
            maxlength="100"
            value="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>"
            required
        >

        <label for="categoria_id">Categoria:</label>
        <select id="categoria_id" name="categoria_id" required>
            <option value="">Selecione uma categoria</option>
            <?php foreach ($categorias as $categoria): ?>
                <option
                    value="<?= (int) $categoria['id'] ?>"
                    <?= $categoria_id === (string) $categoria['id'] ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="data_de_realizacao">Data de realização:</label>
        <input
            type="date"
            id="data_de_realizacao"
            name="data_de_realizacao"
            value="<?= htmlspecialchars($data_de_realizacao, ENT_QUOTES, 'UTF-8') ?>"
            required
        >

        <label for="descricao">Descrição:</label>
        <textarea id="descricao" name="descricao" required><?= htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') ?></textarea>

        <div class="categoria-acoes">
            <button class="btn" type="submit">Salvar ação</button>
            <a class="btn" href="index.php">Cancelar</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>
