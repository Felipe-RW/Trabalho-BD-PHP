<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/criar_acao_log.php';

if (empty($_SESSION['usuario_id'])) {
    header('Location: ../index.html');
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];
$id = filter_input(
    $_SERVER['REQUEST_METHOD'] === 'POST' ? INPUT_POST : INPUT_GET,
    'id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$id) {
    http_response_code(400);
    exit('ID da ação inválido.');
}

$stmt = $conexao->prepare(
    'SELECT id, titulo, descricao, data_de_realizacao, status_acao, categoria_id
     FROM acao
     WHERE id = :id'
);
$stmt->execute([':id' => $id]);
$acao = $stmt->fetch(PDO::FETCH_ASSOC);
$bloqueada = $acao && $acao['status_acao'] === 'Aprovado';
$categorias = $conexao->query('SELECT id, nome FROM categoria ORDER BY nome')
    ->fetchAll(PDO::FETCH_ASSOC);
$erros = [];

if ($acao && !$bloqueada && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $dataStr = trim($_POST['data_de_realizacao'] ?? '');
    $categoriaId = (int) ($_POST['categoria_id'] ?? 0);

    $acao['titulo'] = $titulo;
    $acao['descricao'] = $descricao;
    $acao['data_de_realizacao'] = $dataStr;
    $acao['categoria_id'] = $categoriaId;

    if ($titulo === '' || mb_strlen($titulo) > 100) {
        $erros[] = 'O título é obrigatório e deve ter até 100 caracteres.';
    }
    if ($descricao === '') {
        $erros[] = 'A descrição é obrigatória.';
    }

    $data = DateTime::createFromFormat('Y-m-d', $dataStr);
    if (!$data || $data->format('Y-m-d') !== $dataStr) {
        $erros[] = 'Informe uma data de realização válida.';
    }

    if (!in_array($categoriaId, array_map('intval', array_column($categorias, 'id')), true)) {
        $erros[] = 'Selecione uma categoria válida.';
    }

    if (!$erros) {
        try {
            $conexao->beginTransaction();

            $stmt = $conexao->prepare('SELECT status_acao FROM acao WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $id]);
            if ($stmt->fetchColumn() === 'Aprovado') {
                throw new RuntimeException('Esta ação já foi aprovada e não pode ser editada.');
            }

            $stmt = $conexao->prepare(
                'UPDATE acao
                 SET titulo = :titulo,
                     descricao = :descricao,
                     data_de_realizacao = :data,
                     categoria_id = :categoria
                 WHERE id = :id'
            );
            $stmt->execute([
                ':titulo' => $titulo,
                ':descricao' => $descricao,
                ':data' => $dataStr,
                ':categoria' => $categoriaId,
                ':id' => $id,
            ]);

            criarAcaoLog($conexao, 'UPDATE', 'acao', $id, $usuarioId);
            $conexao->commit();

            header('Location: index.php');
            exit;
        } catch (RuntimeException $ex) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            $erros[] = $ex->getMessage();
        } catch (Throwable $ex) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            error_log($ex->getMessage());
            $erros[] = 'Não foi possível editar a ação. Tente novamente.';
        }
    }
}

if (!$acao) {
    http_response_code(404);
}
?>
<?php require __DIR__ . '/../includes/header.php'; ?>

<div class="topo">
    <h2>Editar ação</h2>
    <a class="btn" href="index.php">Voltar ao painel do Pesquisador</a>
</div>

<?php if (!$acao): ?>
    <p class="aviso">Ação não encontrada.</p>
<?php elseif ($bloqueada): ?>
    <p class="aviso">Esta ação já foi aprovada e não pode ser editada.</p>
<?php else: ?>
    <?php foreach ($erros as $erro): ?>
        <p class="aviso" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>

    <form method="post" action="editar_acao.php?id=<?= (int) $acao['id'] ?>">
        <input type="hidden" name="id" value="<?= (int) $acao['id'] ?>">

        <label for="titulo">Título</label>
        <input type="text" id="titulo" name="titulo" maxlength="100" required
               value="<?= htmlspecialchars($acao['titulo'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao" rows="5" required><?= htmlspecialchars($acao['descricao'], ENT_QUOTES, 'UTF-8') ?></textarea>

        <label for="data_de_realizacao">Data de realização</label>
        <input type="date" id="data_de_realizacao" name="data_de_realizacao" required
               value="<?= htmlspecialchars($acao['data_de_realizacao'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="categoria_id">Categoria</label>
        <select id="categoria_id" name="categoria_id" required>
            <option value="">Selecione...</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?= (int) $categoria['id'] ?>"
                    <?= (int) $categoria['id'] === (int) $acao['categoria_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn">Salvar alterações</button>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
