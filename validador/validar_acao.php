<?php
session_start();

require '../includes/conexao.php';
require '../includes/criar_acao_log.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit('Faça login para validar uma ação.');
}

$usuarioId = (int) $_SESSION['usuario_id'];
$metodo = $_SERVER['REQUEST_METHOD'];
$acaoId = filter_input(
    $metodo === 'POST' ? INPUT_POST : INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$acaoId) {
    http_response_code(400);
    exit('ID da ação inválido.');
}

$erro = null;

if ($metodo === 'POST') {
    $status = $_POST['decisao'] ?? '';
    if (!in_array($status, ['Aprovado', 'Negado'], true)) {
        http_response_code(400);
        exit('Decisão inválida.');
    }

    $motivo = trim($_POST['motivo_rejeicao'] ?? '');
    $motivo = $status === 'Negado' && $motivo !== '' ? $motivo : null;

    try {
        $conexao->beginTransaction();

        $atualizaAcao = $conexao->prepare(
            'UPDATE acao SET status_acao = :status WHERE id = :id'
        );
        $atualizaAcao->execute([
            ':status' => $status,
            ':id' => $acaoId,
        ]);

        if ($atualizaAcao->rowCount() === 0) {
            $verificaAcao = $conexao->prepare('SELECT id FROM acao WHERE id = :id');
            $verificaAcao->execute([':id' => $acaoId]);
            if (!$verificaAcao->fetchColumn()) {
                $conexao->rollBack();
                http_response_code(404);
                exit('Ação não encontrada.');
            }
        }

        $salvaValidacao = $conexao->prepare(
            'INSERT INTO validacao (usuario_id, acao_id, motivo_rejeicao)
             VALUES (:usuario_id, :acao_id, :motivo_rejeicao)
             ON DUPLICATE KEY UPDATE motivo_rejeicao = VALUES(motivo_rejeicao)'
        );
        $salvaValidacao->execute([
            ':usuario_id' => $usuarioId,
            ':acao_id' => $acaoId,
            ':motivo_rejeicao' => $motivo,
        ]);

        criarAcaoLog($conexao, $status, 'validacao', $acaoId, $usuarioId);

        $conexao->commit();
        header('Location: validar_acao_com_login.php?id=' . $acaoId . '&salvo=1');
        exit;
    } catch (Throwable $e) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
        $erro = 'Não foi possível salvar a validação. Verifique a conexão e tente novamente.';
    }
}

$consulta = $conexao->prepare(
    'SELECT a.id, a.titulo, a.descricao, a.data_de_realizacao,
            a.status_acao, c.nome AS categoria
     FROM acao a
     JOIN categoria c ON c.id = a.categoria_id
     WHERE a.id = :id'
);
$consulta->execute([':id' => $acaoId]);
$acao = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$acao) {
    http_response_code(404);
    exit('Ação não encontrada.');
}

$validacaoAtual = $conexao->prepare(
    'SELECT motivo_rejeicao FROM validacao
     WHERE usuario_id = :usuario_id AND acao_id = :acao_id'
);
$validacaoAtual->execute([
    ':usuario_id' => $usuarioId,
    ':acao_id' => $acaoId,
]);
$motivoAtual = $validacaoAtual->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar ação</title>
</head>
<body>
    <h1><?= htmlspecialchars($acao['titulo'], ENT_QUOTES, 'UTF-8') ?></h1>

    <?php if (isset($_GET['salvo'])): ?>
        <p>Validação salva com sucesso.</p>
    <?php endif; ?>

    <?php if ($erro !== null): ?>
        <p role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <p><strong>Categoria:</strong> <?= htmlspecialchars($acao['categoria'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Descrição:</strong><br><?= nl2br(htmlspecialchars($acao['descricao'], ENT_QUOTES, 'UTF-8')) ?></p>
    <p><strong>Data de realização:</strong> <?= htmlspecialchars($acao['data_de_realizacao'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Status atual:</strong> <?= htmlspecialchars($acao['status_acao'], ENT_QUOTES, 'UTF-8') ?></p>

    <form method="post" action="validar_acao_com_login.php">
        <input type="hidden" name="id" value="<?= (int) $acao['id'] ?>">

        <label for="motivo_rejeicao">Motivo (opcional):</label><br>
        <textarea id="motivo_rejeicao" name="motivo_rejeicao" rows="4" cols="50"><?= htmlspecialchars((string) $motivoAtual, ENT_QUOTES, 'UTF-8') ?></textarea>
        <br>

        <button type="submit" name="decisao" value="Aprovado">Aprovar</button>
        <button type="submit" name="decisao" value="Negado">Negar</button>
    </form>

    <p><a href="index.php">Voltar à lista</a></p>
</body>
</html>
