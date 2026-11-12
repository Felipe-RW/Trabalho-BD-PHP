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
$classeBody = 'pagina-criar-acao';
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="criar-acao-painel">
    <div class="cabecalho-criar-acao">
        <div>
            <p class="rotulo-criar-acao">Área do pesquisador</p>
            <h1>Criar nova ação</h1>
            <p class="descricao-criar-acao">Preencha as informações para enviar uma ação para avaliação.</p>
        </div>
        <a href="index.php" class="btn btn-cancelar-criar-acao">Voltar</a>
    </div>

    <?php if (!empty($erro)): ?>
        <p class="erro-criar-acao" role="alert">
            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST" class="formulario-criar-acao">
        <label for="titulo">Título da ação</label>
        <input
            type="text"
            id="titulo"
            name="titulo"
            maxlength="100"
            value="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>"
            required
        >

        <label for="categoria_id">Categoria</label>
        <select id="categoria_id" name="categoria_id" required>
            <option value="">Selecione uma categoria</option>
            <?php foreach ($categorias as $categoria): ?>
                <option
                    value="<?= (int) $categoria['id'] ?>"
                    <?= ($categoria_id == $categoria['id']) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="data_de_realizacao">Data de realização</label>
        <input
            type="date"
            id="data_de_realizacao"
            name="data_de_realizacao"
            value="<?= htmlspecialchars($data_de_realizacao, ENT_QUOTES, 'UTF-8') ?>"
            required
        >

        <label for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao" required><?= htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') ?></textarea>

        <div class="acoes-formulario">
            <button type="submit" class="btn">Enviar para avaliação</button>
        </div>
    </form>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>