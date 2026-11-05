<?php
require '../includes/conexao.php';

$stmt = $conexao->query(
	'SELECT a.id, a.titulo, a.descricao, a.data_de_realizacao,
			a.status_acao, c.nome AS categoria
	 FROM acao a
	 JOIN categoria c ON c.id = a.categoria_id
	 ORDER BY a.id DESC'
);
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
		<h2>Ações</h2>

		<ul>
			<?php while ($acao = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
				<li>
					<a href="validar_acao_teste.php?id=<?= (int) $acao['id'] ?>">
						<?= htmlspecialchars($acao['titulo'], ENT_QUOTES, 'UTF-8') ?>
					</a>
					— <?= htmlspecialchars($acao['categoria'], ENT_QUOTES, 'UTF-8') ?>
					(<?= htmlspecialchars($acao['status_acao'], ENT_QUOTES, 'UTF-8') ?>)
				</li>
			<?php endwhile; ?>
		</ul>
	</main>
</body>
</html>
