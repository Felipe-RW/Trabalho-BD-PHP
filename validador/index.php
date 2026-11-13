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
$classeBody = 'pagina-validador';
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
	<section class="painel-validador">
		<div class="topo-validador">
			<div>
				<h2>Ações para validação</h2>
				<p>Revise as ações enviadas e registre sua decisão.</p>
			</div>
		</div>

		<div class="tabela-validador">
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
						<td class="descricao-acao"><?= htmlspecialchars($acao['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
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
		</div>
	</section>
<?php include '../includes/footer.php'; ?>
