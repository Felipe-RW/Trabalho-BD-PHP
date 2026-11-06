<?php
/**
 * Ações em um único arquivo: listar, cadastrar e editar (com log em log_usuario).
 *
 *   gerenciar_acoes.php              -> lista
 *   gerenciar_acoes.php?pagina=novo  -> formulário de cadastro
 *   gerenciar_acoes.php?pagina=editar&id=3 -> formulário de edição
 *
 * Requisitos: PHP 8+ com pdo_mysql, banco "embrapa_bd" criado (XAMPP: senha do root vazia).
 */
session_start();

// ---------------------------------------------------------
// CONFIGURAÇÃO DO BANCO
// ---------------------------------------------------------
const DB_HOST = 'localhost';
const DB_NAME = 'embrapa_bd';
const DB_USER = 'root';
const DB_PASS = '';

const STATUS_PERMITIDOS = ['Em Avaliação', 'Aprovado', 'Negado'];

function conectar(): PDO
{
    return new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function registrarLog(PDO $pdo, string $operacao, string $tabela, int $idRegistrado, int $usuarioId): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO log_usuario (operacao, tabela_afetada, id_registrado, data_hora, matricula_id)
         VALUES (:operacao, :tabela, :id, NOW(), :usuario)'
    );
    $stmt->execute([
        ':operacao' => $operacao,
        ':tabela'   => $tabela,
        ':id'       => $idRegistrado,
        ':usuario'  => $usuarioId,
    ]);
}

function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function redirecionar(string $url, ?string $tipo = null, ?string $mensagem = null): never
{
    if ($tipo !== null) {
        $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $mensagem];
    }
    header('Location: ' . $url);
    exit;
}

// ---------------------------------------------------------
// USUÁRIO LOGADO
// TESTE: enquanto não existe login, usa o usuario.id = 1 (o Administrador criado pelo script do banco).
// Quando tiver login, apague este bloco e grave $_SESSION['usuario_id'] no login.
// ---------------------------------------------------------
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['usuario_id'] = 1;
}
$usuarioLogadoId = (int) $_SESSION['usuario_id'];

// Token contra CSRF
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$pdo = conectar();
$categorias = $pdo->query('SELECT id, nome FROM categoria ORDER BY nome')->fetchAll();

$pagina = $_GET['pagina'] ?? 'listar';
$id     = (int) ($_GET['id'] ?? 0);
$erros  = [];
$dados  = [
    'titulo'             => '',
    'descricao'          => '',
    'data_de_realizacao' => '',
    'status_acao'        => 'Em Avaliação',
    'categoria_id'       => '',
];

// ---------------------------------------------------------
// PROCESSAMENTO DO FORMULÁRIO (cadastrar ou editar)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pagina = ($_POST['pagina'] ?? '') === 'editar' ? 'editar' : 'novo';
    $id     = (int) ($_POST['id'] ?? 0);

    foreach ($dados as $campo => $_) {
        $dados[$campo] = trim($_POST[$campo] ?? '');
    }

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $erros[] = 'Sessão inválida. Recarregue a página e tente de novo.';
    }
    if ($dados['titulo'] === '' || mb_strlen($dados['titulo']) > 100) {
        $erros[] = 'O título é obrigatório e deve ter até 100 caracteres.';
    }
    if ($dados['descricao'] === '' || strlen($dados['descricao']) > 65535) {
        $erros[] = 'A descrição é obrigatória.';
    }

    $data = DateTime::createFromFormat('Y-m-d', $dados['data_de_realizacao']);
    if (!$data || $data->format('Y-m-d') !== $dados['data_de_realizacao']) {
        $erros[] = 'Informe uma data de realização válida.';
    }
    if (!in_array($dados['status_acao'], STATUS_PERMITIDOS, true)) {
        $erros[] = 'Status inválido.';
    }
    $idsCategoria = array_map('intval', array_column($categorias, 'id'));
    if (!in_array((int) $dados['categoria_id'], $idsCategoria, true)) {
        $erros[] = 'Selecione uma categoria válida.';
    }
    if ($pagina === 'editar' && $id <= 0) {
        $erros[] = 'Ação não informada.';
    }

    if (!$erros) {
        try {
            $pdo->beginTransaction();

            $parametros = [
                ':titulo'    => $dados['titulo'],
                ':descricao' => $dados['descricao'],
                ':data'      => $dados['data_de_realizacao'],
                ':status'    => $dados['status_acao'],
                ':categoria' => (int) $dados['categoria_id'],
            ];

            if ($pagina === 'editar') {
                $stmt = $pdo->prepare(
                    'UPDATE acao
                     SET titulo = :titulo, descricao = :descricao, data_de_realizacao = :data,
                         status_acao = :status, categoria_id = :categoria
                     WHERE id = :id'
                );
                $stmt->execute($parametros + [':id' => $id]);

                // Confere se a ação existe (rowCount pode ser 0 quando nada mudou)
                $existe = $pdo->prepare('SELECT COUNT(*) FROM acao WHERE id = :id');
                $existe->execute([':id' => $id]);
                if ((int) $existe->fetchColumn() === 0) {
                    throw new RuntimeException('Ação não encontrada.');
                }

                registrarLog($pdo, 'UPDATE', 'acao', $id, $usuarioLogadoId);
                $pdo->commit();
                redirecionar('gerenciar_acoes.php', 'ok', "Ação $id atualizada com sucesso.");
            }

            $stmt = $pdo->prepare(
                'INSERT INTO acao (titulo, descricao, data_de_realizacao, status_acao, categoria_id)
                 VALUES (:titulo, :descricao, :data, :status, :categoria)'
            );
            $stmt->execute($parametros);

            $novoId = (int) $pdo->lastInsertId();
            registrarLog($pdo, 'INSERT', 'acao', $novoId, $usuarioLogadoId);
            $pdo->commit();
            redirecionar('gerenciar_acoes.php', 'ok', "Ação cadastrada com sucesso (código $novoId).");
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log($ex->getMessage());
            $erros[] = $ex instanceof RuntimeException
                ? $ex->getMessage()
                : 'Não foi possível salvar a ação. Tente novamente.';
        }
    }
}

// ---------------------------------------------------------
// Abrir a edição: carrega a ação do banco
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $pagina === 'editar') {
    $stmt = $pdo->prepare(
        'SELECT titulo, descricao, data_de_realizacao, status_acao, categoria_id
         FROM acao WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
    $acao = $stmt->fetch();

    if (!$acao) {
        redirecionar('gerenciar_acoes.php', 'erro', 'Ação não encontrada.');
    }
    $dados = array_map('strval', $acao);
}

// ---------------------------------------------------------
// Lista de ações
// ---------------------------------------------------------
$acoes = [];
if ($pagina === 'listar') {
    $acoes = $pdo->query(
        'SELECT a.id, a.titulo, a.data_de_realizacao, a.status_acao, c.nome AS categoria
         FROM acao a
         JOIN categoria c ON c.id = a.categoria_id
         ORDER BY a.id DESC'
    )->fetchAll();
}

// Mensagem de sucesso/erro guardada pelo redirecionar()
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// =========================================================
// SAÍDA: a página inteira é gerada pelo PHP com echo
// =========================================================
$css = <<<CSS
body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 2rem; }
.caixa { max-width: 760px; margin: auto; background: #fff; padding: 1.5rem; border-radius: 8px; }
.caixa.estreita { max-width: 480px; }
h1 { margin-top: 0; font-size: 1.4rem; }
label { display: block; margin-top: 1rem; font-weight: bold; }
input, select, textarea { width: 100%; padding: .5rem; margin-top: .3rem; box-sizing: border-box; font-family: inherit; }
.botoes { margin-top: 1.5rem; display: flex; gap: .8rem; align-items: center; }
button { padding: .7rem 1.2rem; cursor: pointer; }
table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
th, td { text-align: left; padding: .6rem; border-bottom: 1px solid #ddd; }
th { background: #fafafa; }
a { color: #1a4fa0; }
.msg { padding: .7rem; border-radius: 4px; margin-bottom: 1rem; }
.erro { background: #fde2e2; color: #8a1f1f; }
.ok { background: #dff5df; color: #1d5e1d; }
CSS;

/** Monta <option> de um select. $selecionado compara com o valor como texto. */
function opcao(string $valor, string $texto, string $selecionado): string
{
    $marcado = $valor === $selecionado ? ' selected' : '';
    return '<option value="' . e($valor) . '"' . $marcado . '>' . e($texto) . '</option>';
}

echo '<!DOCTYPE html><html lang="pt-br"><head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
echo '<title>Ações</title>';
echo '<style>' . $css . '</style>';
echo '</head><body>';

if ($pagina === 'listar') {
    // ---------------- LISTA ----------------
    echo '<div class="caixa">';
    echo '<h1>Ações</h1>';
    echo '<p><a href="?pagina=novo">+ Cadastrar nova ação</a></p>';

    if ($flash) {
        echo '<div class="msg ' . e($flash['tipo']) . '">' . e($flash['texto']) . '</div>';
    }

    echo '<table><thead><tr>';
    foreach (['Código', 'Título', 'Data', 'Categoria', 'Status', ''] as $cabecalho) {
        echo '<th>' . $cabecalho . '</th>';
    }
    echo '</tr></thead><tbody>';

    if (!$acoes) {
        echo '<tr><td colspan="6">Nenhuma ação cadastrada.</td></tr>';
    }
    foreach ($acoes as $a) {
        echo '<tr>';
        echo '<td>' . (int) $a['id'] . '</td>';
        echo '<td>' . e($a['titulo']) . '</td>';
        echo '<td>' . e(date('d/m/Y', strtotime($a['data_de_realizacao']))) . '</td>';
        echo '<td>' . e($a['categoria']) . '</td>';
        echo '<td>' . e($a['status_acao']) . '</td>';
        echo '<td><a href="?pagina=editar&id=' . (int) $a['id'] . '">Editar</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
} else {
    // ---------------- FORMULÁRIO (cadastrar / editar) ----------------
    $editando = $pagina === 'editar';

    echo '<form class="caixa estreita" method="post" action="gerenciar_acoes.php">';
    echo '<h1>' . ($editando ? 'Editar ação' : 'Cadastrar ação') . '</h1>';

    if ($erros) {
        echo '<div class="msg erro">';
        foreach ($erros as $erro) {
            echo '<div>' . e($erro) . '</div>';
        }
        echo '</div>';
    }

    echo '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
    echo '<input type="hidden" name="pagina" value="' . ($editando ? 'editar' : 'novo') . '">';
    echo '<input type="hidden" name="id" value="' . (int) $id . '">';

    echo '<label for="titulo">Título</label>';
    echo '<input type="text" id="titulo" name="titulo" maxlength="100" required value="' . e($dados['titulo']) . '">';

    echo '<label for="descricao">Descrição</label>';
    echo '<textarea id="descricao" name="descricao" rows="5" required>' . e($dados['descricao']) . '</textarea>';

    echo '<label for="data_de_realizacao">Data de realização</label>';
    echo '<input type="date" id="data_de_realizacao" name="data_de_realizacao" required value="' . e($dados['data_de_realizacao']) . '">';

    echo '<label for="categoria_id">Categoria</label>';
    echo '<select id="categoria_id" name="categoria_id" required>';
    echo '<option value="">Selecione...</option>';
    foreach ($categorias as $cat) {
        echo opcao((string) $cat['id'], $cat['nome'], $dados['categoria_id']);
    }
    echo '</select>';

    echo '<label for="status_acao">Status</label>';
    echo '<select id="status_acao" name="status_acao">';
    foreach (STATUS_PERMITIDOS as $status) {
        echo opcao($status, $status, $dados['status_acao']);
    }
    echo '</select>';

    echo '<div class="botoes">';
    echo '<button type="submit">' . ($editando ? 'Salvar alterações' : 'Cadastrar') . '</button>';
    echo '<a href="gerenciar_acoes.php">Cancelar</a>';
    echo '</div>';

    echo '</form>';
}

echo '</body></html>';
