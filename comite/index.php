<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

if (!isset($_SESSION['usuario_id'])) {
	header('Location: ../index.html');
	exit;
}
$cargosUsuario = $_SESSION['cargos'] ?? [];
if (!is_array($cargosUsuario) || !in_array('Comitê', $cargosUsuario, true)) {
	header('Location: ../escolher_cargo.php');
	exit;
}

require_once __DIR__ . '/../includes/conexao.php';

$escapar = static function ($valor): string {
	return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$lerFiltro = static function (string $nome): string {
	$valor = $_GET[$nome] ?? '';
	return is_string($valor) ? trim($valor) : '';
};

$validarData = static function (string $valor): ?string {
	if ($valor === '') {
		return null;
	}

	$data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
	return $data && $data->format('Y-m-d') === $valor ? $valor : null;
};

$operacao = $lerFiltro('operacao');

$tabela = $lerFiltro('tabela');

$buscaUsuario = $lerFiltro('usuario');

$dataInicioInformada = $lerFiltro('data_inicio');

$dataFimInformada = $lerFiltro('data_fim');

$dataInicio = $validarData($dataInicioInformada);

$dataFim = $validarData($dataFimInformada);

$erroFiltro = null;

$erroConsulta = null;

$operacoes = [];

$tabelas = [];

$logs = [];

$total = 0;

$pagina = 1;

$totalPaginas = 1;

$porPagina = 25;


if ($dataInicioInformada !== '' && $dataInicio === null) {
	$erroFiltro = 'A data inicial informada é inválida.';
}

if ($dataFimInformada !== '' && $dataFim === null) {
	$erroFiltro = 'A data final informada é inválida.';
}

if ($dataInicio !== null && $dataFim !== null && $dataInicio > $dataFim) {
	$erroFiltro = 'A data inicial não pode ser posterior à data final.';
	$dataInicio = null;
	$dataFim = null;
}

$paginaRecebida = $_GET['pagina'] ?? 1;

$paginaValidada = is_scalar($paginaRecebida)
	? filter_var($paginaRecebida, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
	: false;

$pagina = $paginaValidada === false ? 1 : $paginaValidada;

try {
	$operacoes = $conexao->query(
		'SELECT DISTINCT operacao FROM log_usuario ORDER BY operacao'
	)->fetchAll(PDO::FETCH_COLUMN);
	$tabelas = $conexao->query(
		'SELECT DISTINCT tabela_afetada FROM log_usuario ORDER BY tabela_afetada'
	)->fetchAll(PDO::FETCH_COLUMN);

	$condicoes = [];
	$parametros = [];

	if ($operacao !== '') {
		$condicoes[] = 'l.operacao = :operacao';
		$parametros[':operacao'] = $operacao;
	}

	if ($tabela !== '') {
		$condicoes[] = 'l.tabela_afetada = :tabela';
		$parametros[':tabela'] = $tabela;
	}

	if ($buscaUsuario !== '') {
		$condicoes[] = '(u.nome LIKE :usuario_nome OR u.email LIKE :usuario_email OR CAST(l.matricula_id AS CHAR) = :usuario_id)';
		$parametros[':usuario_nome'] = '%' . $buscaUsuario . '%';
		$parametros[':usuario_email'] = '%' . $buscaUsuario . '%';
		$parametros[':usuario_id'] = $buscaUsuario;
	}

	if ($dataInicio !== null) {
		$condicoes[] = 'l.data_hora >= :data_inicio';
		$parametros[':data_inicio'] = $dataInicio . ' 00:00:00';
	}

	if ($dataFim !== null) {
		$condicoes[] = 'l.data_hora < DATE_ADD(:data_fim, INTERVAL 1 DAY)';
		$parametros[':data_fim'] = $dataFim;
	}

	$whereSql = $condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '';
	$consultaTotal = $conexao->prepare(
		'SELECT COUNT(*)
		 FROM log_usuario l
		 LEFT JOIN usuario u ON u.id = l.matricula_id' . $whereSql
	);

	$consultaTotal->execute($parametros);

	$total = (int) $consultaTotal->fetchColumn();

	$totalPaginas = max(1, (int) ceil($total / $porPagina));

	$pagina = min($pagina, $totalPaginas);

	$consultaLogs = $conexao->prepare(
		'SELECT l.id, l.operacao, l.tabela_afetada, l.id_registrado,
				l.data_hora, l.matricula_id, u.nome AS usuario_nome
		 FROM log_usuario l
		 LEFT JOIN usuario u ON u.id = l.matricula_id' . $whereSql . '
		 ORDER BY l.data_hora DESC, l.id DESC
		 LIMIT :limite OFFSET :deslocamento'
	);

	foreach ($parametros as $nome => $valor) {
		$consultaLogs->bindValue($nome, $valor, PDO::PARAM_STR);
	}

	$consultaLogs->bindValue(':limite', $porPagina, PDO::PARAM_INT);

	$consultaLogs->bindValue(':deslocamento', ($pagina - 1) * $porPagina, PDO::PARAM_INT);

	$consultaLogs->execute();

	$logs = $consultaLogs->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $erro) {
	error_log($erro->getMessage());
	$erroConsulta = 'Não foi possível carregar os registros de log.';
}

$filtrosAtivos = array_filter([
	'operacao' => $operacao,
	'tabela' => $tabela,
	'usuario' => $buscaUsuario,
	'data_inicio' => $dataInicioInformada,
	'data_fim' => $dataFimInformada,
], static fn($valor) => $valor !== '');

$urlPagina = static function (int $numero) use ($filtrosAtivos): string {
	return '?' . http_build_query($filtrosAtivos + ['pagina' => $numero]);
};
?>

<?php $classeBody = 'pagina-log-comite'; ?>
<?php include __DIR__ . '/../includes/header.php'; ?>
    <section class="pagina-log">

        <div class="topo">

            <div>
                <h2>Registro do LOG</h2>
                <p class="log-descricao">Histórico de operações registradas no sistema.</p>
            </div>

        </div>

        <?php if ($erroFiltro !== null): ?>
            <p class="aviso" role="alert"><?= $escapar($erroFiltro) ?></p>
        <?php endif; ?>

        <?php if ($erroConsulta !== null): ?>
            <p class="aviso" role="alert"><?= $escapar($erroConsulta) ?></p>
        <?php else: ?>

            <form class="log-filtros" method="get" action="index.php">

                <div class="log-campo">

                    <label for="operacao">Operação</label>

                    <select id="operacao" name="operacao">

                        <option value="">Todas</option>
                        <?php foreach ($operacoes as $opcao): ?>
                            <option value="<?= $escapar($opcao) ?>" <?= $operacao === $opcao ? 'selected' : '' ?>><?= $escapar($opcao) ?></option>
                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="log-campo">

                    <label for="tabela">Tabela afetada</label>

                    <select id="tabela" name="tabela">

                        <option value="">Todas</option>
                        <?php foreach ($tabelas as $opcao): ?>
                            <option value="<?= $escapar($opcao) ?>" <?= $tabela === $opcao ? 'selected' : '' ?>><?= $escapar($opcao) ?></option>
                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="log-campo">

                    <label for="usuario">Usuário</label>

                    <input type="search" id="usuario" name="usuario" value="<?= $escapar($buscaUsuario) ?>" placeholder="Nome, e-mail ou ID">

                </div>

                <div class="log-campo">

                    <label for="data_inicio">Data inicial</label>

                    <input type="date" id="data_inicio" name="data_inicio" value="<?= $escapar($dataInicioInformada) ?>">

                </div>

                <div class="log-campo">

                    <label for="data_fim">Data final</label>

                    <input type="date" id="data_fim" name="data_fim" value="<?= $escapar($dataFimInformada) ?>">

                </div>

                <div class="log-acoes-filtro">

                    <button class="btn" type="submit">Filtrar</button>

                    <a class="btn btn-secundario" href="index.php">Limpar</a>

                </div>

            </form>

            <div class="log-resumo" aria-live="polite">

                <span><?= number_format($total, 0, ',', '.') ?> registro(s) encontrado(s)</span>

                <?php if ($total > 0): ?><span>Página <?= (int) $pagina ?> de <?= (int) $totalPaginas ?></span><?php endif; ?>

            </div>

            <div class="log-tabela-scroll">

                <table class="tabela-log">

                    <thead>
                        <tr>

                            <th scope="col">ID do log</th>
                            <th scope="col">Data e hora</th>
                            <th scope="col">Usuário</th>
                            <th scope="col">Operação</th>
                            <th scope="col">Tabela afetada</th>
                            <th scope="col">ID do registro</th>

                        </tr>
                    </thead>

                    <tbody>

                        <?php if (!$logs): ?>
                            <tr><td class="log-vazio" colspan="6">Nenhum registro de log encontrado.</td></tr>
                        <?php else: ?>

                            <?php foreach ($logs as $log): ?>
                                <?php

                                $timestamp = strtotime($log['data_hora']);

                                $dataFormatada = $timestamp === false ? $log['data_hora'] : date('d/m/Y H:i:s', $timestamp);

                                $nomeUsuario = $log['usuario_nome'] ?: 'Usuário #' . $log['matricula_id'];
                                ?>
                                
                                <tr>

                                    <td>#<?= (int) $log['id'] ?></td>
                                    <td><?= $escapar($dataFormatada) ?></td>
                                    <td><?= $escapar($nomeUsuario) ?></td>
                                    <td><span class="log-operacao"><?= $escapar($log['operacao']) ?></span></td>
                                    <td><?= $escapar($log['tabela_afetada']) ?></td>
                                    <td>#<?= (int) $log['id_registrado'] ?></td>

                                </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <?php if ($totalPaginas > 1): ?>

                <nav class="log-paginacao" aria-label="Paginação dos registros">
                    <?php if ($pagina > 1): ?><a class="btn btn-pequeno" href="<?= $escapar($urlPagina($pagina - 1)) ?>">Anterior</a><?php endif; ?>
                    <?php if ($pagina < $totalPaginas): ?><a class="btn btn-pequeno" href="<?= $escapar($urlPagina($pagina + 1)) ?>">Próxima</a><?php endif; ?>
                </nav>

            <?php endif; ?>

        <?php endif; ?>

    </section>
    
<?php include __DIR__ . '/../includes/footer.php'; ?>