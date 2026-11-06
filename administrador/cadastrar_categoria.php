<?php
session_start();

require '../includes/conexao.php';
require '../includes/criar_acao_log.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conexao->prepare('INSERT INTO categoria (nome) VALUES (:nome)');
    $stmt->execute([
        ':nome' => $_POST['nome'],
    ]);

    $categoriaId = (int) $conexao->lastInsertId();
    $usuarioId = (int) $_SESSION['usuario_id'];

    criarAcaoLog($conexao, 'Cadastro', 'categoria', $categoriaId, $usuarioId);

    header('Location: index.php');
    exit;
}

include '../includes/header.php';
?>

<section class="categoria-pagina">
    <div class="categoria-titulo">
        <h2>Cadastrar categoria</h2>
    </div>

    <form method="post" action="cadastrar_categoria.php">
        <label for="nome">Nome da categoria:</label>
        <input type="text" id="nome" name="nome" maxlength="100" required>

        <div class="categoria-acoes">
            <button class="btn" type="submit">Cadastrar</button>
            <a class="btn" href="index.php">Voltar ao painel do Administrador</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>
