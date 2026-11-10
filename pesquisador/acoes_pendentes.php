<?php
session_start();

require_once '../includes/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$sql = "SELECT
            a.titulo,
            a.data_de_realizacao,
            a.status_acao,
            c.nome AS categoria
        FROM acao a
        INNER JOIN acao_usuario au
            ON a.id = au.acao_id
        INNER JOIN categoria c
            ON a.categoria_id = c.id
        WHERE au.usuario_id = :usuario_id
        ORDER BY a.data_de_realizacao DESC";

$stmt = $conexao->prepare($sql);
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();

$acoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<body>
    <main class="conteudo">
        <div class="painel-pesquisador">
            <div class="cabecalho-pesquisador">
                <section class="boas-vindas">
                    <h2 class="tabela_gap">Área do pesquisador</h2>
                    <h2 class="tabela_gap">Minhas Ações</h2>
                    <p>Acompanhe a situação das suas ações aqui.</p>
                </section>
            </div>

            <section class="acoes">
                <div class="tabela_pesquisador_acoes">
                    <table>
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Data de realização</th>
                                <th>Categoria</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (count($acoes) > 0): ?>
                                <?php foreach ($acoes as $acao): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($acao['titulo']) ?>
                                        </td>

                                        <td>
                                            <?= date('d/m/Y', strtotime($acao['data_de_realizacao'])) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($acao['categoria']) ?>
                                        </td>

                                        <td>
                                            <span class="status-acao">
                                                <?= htmlspecialchars($acao['status_acao']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="sem-acoes">
                                        Você ainda não enviou nenhuma ação.
                                    </td>
                                </tr>
                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
<?php include '../includes/footer.php'; ?>