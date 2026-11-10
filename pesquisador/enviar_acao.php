<?php
session_start();

require_once '../includes/conexao.php';

$titulo = '';
$categoria_id = '';
$data_de_realizacao = '';
$descricao = '';
$erro = '';

$usuario_id = (int) ($_SESSION['usuario_id'] ?? $_SESSION['id'] ?? 0);

// Verifica se o usuário está logado
if ($usuario_id <= 0) {
    header('Location: ../login.php');
    exit;
}

// Busca as categorias
$sql = "SELECT id, nome FROM categoria ORDER BY nome";
$stmt = $conexao->prepare($sql);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processa o formulário
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

    } elseif (!ctype_digit($categoria_id) || (int)$categoria_id <= 0) {
        $erro = 'Selecione uma categoria válida.';

    } else {

        try {

            // Inicia uma transação
            $conexao->beginTransaction();

            // 1. Cadastra a ação
            $sql = "INSERT INTO acao
                    (titulo, categoria_id, data_de_realizacao, descricao)
                    VALUES
                    (:titulo, :categoria_id, :data_de_realizacao, :descricao)";

            $stmt = $conexao->prepare($sql);

            $stmt->execute([
                ':titulo' => $titulo,
                ':categoria_id' => (int)$categoria_id,
                ':data_de_realizacao' => $data_de_realizacao,
                ':descricao' => $descricao
            ]);

            // Recupera o ID da ação cadastrada
            $acao_id = $conexao->lastInsertId();

            // 2. Vincula a ação ao pesquisador
            $sql = "INSERT INTO acao_usuario
                    (usuario_id, acao_id)
                    VALUES
                    (:usuario_id, :acao_id)";

            $stmt = $conexao->prepare($sql);

            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':acao_id' => $acao_id
            ]);

            // Confirma as duas operações
            $conexao->commit();

            // Informa ao painel que o cadastro foi realizado
            $_SESSION['acao_cadastrada'] = true;

            header('Location: index.php');
            exit;

        } catch (PDOException $e) {

            // Desfaz as operações em caso de erro
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            $erro = 'Erro ao cadastrar ação. Verifique os dados informados.';
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<main>
    <div class="enviar_acao_painel">

        <h1>Enviar Ação</h1>

        <?php if (!empty($erro)): ?>
            <p style="color: red;" role="alert">
                <?= htmlspecialchars($erro) ?>
            </p>
        <?php endif; ?>

        <br>

        <form method="POST" class="enviar_acao_painel">

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

            <textarea name="descricao" required><?= htmlspecialchars($descricao) ?></textarea>

            <br>

            <div class="acoes-formulario">

                <button type="submit" class="btn">
                    Salvar
                </button>

                <a href="index.php" class="btn">
                    Cancelar
                </a>

            </div>

        </form>

    </div>
</main>

<?php include '../includes/footer.php'; ?>