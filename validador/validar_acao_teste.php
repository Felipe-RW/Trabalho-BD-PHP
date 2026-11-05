<?php
session_start();

require '../includes/conexao.php';
require '../includes/criar_acao_log.php';

$usuarioId = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
$acaoId = filter_input(
	$_SERVER['REQUEST_METHOD'] === 'POST' ? INPUT_POST : INPUT_GET,
	'id',
	FILTER_VALIDATE_INT
);

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if ($usuarioId === null) {
		http_response_code(401);
		$erro = 'Modo de teste: o formulário está disponível, mas é necessário um usuario_id válido para salvar a validação.';
	} else {

		$status = $_POST['decisao'];
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
			header('Location: validar_acao_teste.php?id=' . $acaoId . '&salvo=1');
			exit;
		} catch (Throwable $e) {
			if ($conexao->inTransaction()) {
				$conexao->rollBack();
			}
			$erro = 'Não foi possível salvar a validação. Verifique a conexão e tente novamente.';
		}
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

$motivoAtual = null;
if ($usuarioId !== null) {
	$validacaoAtual = $conexao->prepare(
		'SELECT motivo_rejeicao FROM validacao
		 WHERE usuario_id = :usuario_id AND acao_id = :acao_id'
	);
	$validacaoAtual->execute([
		':usuario_id' => $usuarioId,
		':acao_id' => $acaoId,
	]);
	$motivoAtual = $validacaoAtual->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Validar ação</title>
	<link rel="stylesheet" href="../css/estilo.css">
</head>
<body>
	<main>
	<h1><?= htmlspecialchars($acao['titulo'], ENT_QUOTES, 'UTF-8') ?></h1>

	<?php if (isset($_GET['salvo'])): ?>
		<p class="aviso">Validação salva com sucesso.</p>
	<?php endif; ?>

	<?php if ($erro !== null): ?>
		<p class="aviso" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
	<?php endif; ?>

	<p><strong>Categoria:</strong> <?= htmlspecialchars($acao['categoria'], ENT_QUOTES, 'UTF-8') ?></p>
	<p><strong>Descrição:</strong><br><?= nl2br(htmlspecialchars($acao['descricao'], ENT_QUOTES, 'UTF-8')) ?></p>
	<p><strong>Data de realização:</strong> <?= htmlspecialchars($acao['data_de_realizacao'], ENT_QUOTES, 'UTF-8') ?></p>
	<p><strong>Status atual:</strong> <?= htmlspecialchars($acao['status_acao'], ENT_QUOTES, 'UTF-8') ?></p>

	<?php if ($usuarioId === null): ?>
		<p class="aviso">Modo de teste: você pode preencher o formulário, mas salvar exige um usuario_id válido.</p>
	<?php endif; ?>

	<form method="post" action="validar_acao_teste.php">
		<input type="hidden" name="id" value="<?= (int) $acao['id'] ?>">

		<label for="motivo_rejeicao">Motivo (opcional):</label><br>
		<textarea id="motivo_rejeicao" name="motivo_rejeicao" rows="4" cols="50"><?= htmlspecialchars((string) $motivoAtual, ENT_QUOTES, 'UTF-8') ?></textarea>
		<br>

		<button class="btn" type="submit" name="decisao" value="Aprovado">Aprovar</button>
		<button class="btn btn-perigo" type="submit" name="decisao" value="Negado">Negar</button>
	</form>

	<p><a href="index.php">Voltar à lista</a></p>
	</main>
</body>
</html>
