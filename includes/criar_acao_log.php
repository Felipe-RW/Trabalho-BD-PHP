<?php

function criarAcaoLog(
	PDO $conexao,
	string $operacao,
	string $tabelaAfetada,
	int $idRegistrado,
	int $usuarioId
): void {
	$stmt = $conexao->prepare(
		'INSERT INTO log_usuario
			(operacao, tabela_afetada, id_registrado, data_hora, matricula_id)
		 VALUES
			(:operacao, :tabela_afetada, :id_registrado, NOW(), :matricula_id)'
	);

	$stmt->execute([
		':operacao' => $operacao,
		':tabela_afetada' => $tabelaAfetada,
		':id_registrado' => $idRegistrado,
		':matricula_id' => $usuarioId,
	]);
}
