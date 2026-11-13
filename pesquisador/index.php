<?php
session_start();
require '../includes/conexao.php';
$pesquisador = "Pesquisador";

$pendentes = 0;
$aprovadas = 0;
$recusadas = 0;

$usuario_id = (int) ($_SESSION['usuario_id'] ?? $_SESSION['id'] ?? 0);
if ($usuario_id > 0) {
    $consulta = $conexao->prepare(
        'SELECT a.status_acao, COUNT(*) AS quantidade
         FROM acao a
         JOIN acao_usuario au ON au.acao_id = a.id
         WHERE au.usuario_id = :usuario_id
         GROUP BY a.status_acao'
    );
    $consulta->execute(['usuario_id' => $usuario_id]);
    foreach ($consulta->fetchAll() as $grupo) {
        switch ($grupo['status_acao']) {
            case 'Em Avaliação': $pendentes = (int) $grupo['quantidade']; break;
            case 'Aprovado': $aprovadas = (int) $grupo['quantidade']; break;
            case 'Negado': $recusadas = (int) $grupo['quantidade']; break;
        }
    }
}
$acao_cadastrada = !empty($_SESSION['acao_cadastrada']);
unset($_SESSION['acao_cadastrada']);
$classeBody = 'pagina-pesquisador-inicio';
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

    <section class="pagina-inicial-pesquisador">

        <div class="painel-pesquisador">
            <?php if ($acao_cadastrada): ?>
                <p class="aviso" role="status">Ação cadastrada com sucesso e enviada para avaliação.</p>
            <?php endif; ?>
            <div class="cabecalho-pesquisador">
                <div class="botoes-pesquisador">
                    <a class="btn btn-visualizar-acoes" href="acoes_pendentes.php">Visualizar ações</a>
                    <a class="btn-criar-acao" href="enviar_acao.php">Criar nova ação</a>
                </div>
                <section class="boas-vindas">
                    <p class="etiqueta-pesquisador">Área do pesquisador</p>
                    <h2>Bem-vindo!</h2>
                    <p class="descricao-boas-vindas">Acompanhe suas ações, consulte atualizações e gerencie suas solicitações em um só lugar.</p>
                </section>
            </div>

            <section class="acoes">

                <div class="card-pesquisador card-pendente">
                    <h3>Ações pendentes</h3>
                    <p class="card-quantidade"><strong><?= $pendentes ?></strong> ações</p>
                </div>

                <div class="card-pesquisador card-aprovada">
                    <h3>Ações aprovadas</h3>
                    <p class="card-quantidade"><strong><?= $aprovadas ?></strong> ações</p>
                </div>

                <div class="card-pesquisador card-recusada">
                    <h3>Ações recusadas</h3>
                    <p class="card-quantidade"><strong><?= $recusadas ?></strong> ações</p>
                </div>

            </section>
        </div>

    </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>