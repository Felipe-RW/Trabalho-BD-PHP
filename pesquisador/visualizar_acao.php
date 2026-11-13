<?php
session_start();

require_once '../includes/conexao.php';

if (empty($_SESSION['usuario_id'])) {
    header('Location: ../index.html');
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$acao = false;

if ($id) {
    $stmt = $conexao->prepare(
        "SELECT a.id, a.titulo, a.descricao, a.data_de_realizacao, a.status_acao,
                c.nome AS categoria
         FROM acao a
         INNER JOIN acao_usuario au ON au.acao_id = a.id
         INNER JOIN categoria c ON c.id = a.categoria_id
         WHERE a.id = :id
           AND au.usuario_id = :usuario_id
           AND a.status_acao = 'Aprovado'"
    );
    $stmt->execute([
        ':id' => $id,
        ':usuario_id' => $usuarioId,
    ]);
    $acao = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$acao) {
    http_response_code($id ? 404 : 400);
}
?>
<?php require '../includes/header.php'; ?>

<section class="conteudo pagina-minhas-acoes">
    <div class="painel-pesquisador">
        <div class="cabecalho-pesquisador">
            <section class="boas-vindas">
                <h2 class="tabela_gap">Visualizar ação aprovada</h2>
            </section>
        </div>

        <?php if (!$acao): ?>
            <p class="aviso">Ação aprovada não encontrada.</p>
        <?php else: ?>
            <section class="acoes">
                <h3><?= htmlspecialchars($acao['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><strong>Categoria:</strong> <?= htmlspecialchars($acao['categoria'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Data de realização:</strong> <?= date('d/m/Y', strtotime($acao['data_de_realizacao'])) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($acao['status_acao'], ENT_QUOTES, 'UTF-8') ?></p>
                <div>
                    <strong>Descrição:</strong>
                    <p><?= nl2br(htmlspecialchars($acao['descricao'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            </section>
        <?php endif; ?>

        <div class="voltar-minhas-acoes">
            <a class="btn-voltar-minhas-acoes" href="acoes_pendentes.php">
                <span aria-hidden="true">←</span> Voltar às minhas ações
            </a>
        </div>
    </div>
</section>

<?php require '../includes/footer.php'; ?>