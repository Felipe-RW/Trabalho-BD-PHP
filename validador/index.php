<?php
require '../includes/conexao.php';

$stmt = $conexao->query(
	'SELECT a.id, a.titulo, a.descricao, a.data_de_realizacao,
			a.status_acao, c.nome AS categoria
	 FROM acao a
	 JOIN categoria c ON c.id = a.categoria_id
	 WHERE a.status_acao = \'Em Avaliação\'
	 ORDER BY a.id DESC'
);
$acoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Ações para validação</title>
	<link rel="stylesheet" href="../css/estilo.css">
</head>
<body>
	<main>
		<div class="topo">
			<h2>Ações para validação</h2>
		</div>

		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Título</th>
					<th>Descrição</th>
					<th>Data de realização</th>
					<th>Categoria</th>
					<th>Status</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php if (count($acoes) === 0): ?>
					<tr><td colspan="7">Nenhuma ação encontrada para validação.</td></tr>
				<?php endif; ?>
				<?php foreach ($acoes as $acao): ?>
					<tr>
						<td><?= (int) $acao['id'] ?></td>
						<td><?= htmlspecialchars($acao['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
						<td><?= htmlspecialchars($acao['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
						<td><?= htmlspecialchars($acao['data_de_realizacao'], ENT_QUOTES, 'UTF-8') ?></td>
						<td><?= htmlspecialchars($acao['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
						<td><?= htmlspecialchars($acao['status_acao'], ENT_QUOTES, 'UTF-8') ?></td>
						<td>
							<a class="btn btn-pequeno" href="validar_acao.php?id=<?= (int) $acao['id'] ?>">Validar</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</main>
</body>
</html>
