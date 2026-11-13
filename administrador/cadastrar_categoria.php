<?php
session_start();

require '../includes/conexao.php';
require '../includes/criar_acao_log.php';

$nome = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');

    if ($nome === '' || mb_strlen($nome) > 100) {
        $erro = 'Informe um nome de categoria com até 100 caracteres.';
    } else {
        try {
            $stmt = $conexao->prepare('SELECT id FROM categoria WHERE nome = :nome LIMIT 1');
            $stmt->execute([':nome' => $nome]);

            if ($stmt->fetchColumn()) {
                $erro = 'Já existe uma categoria com esse nome.';
            } else {
                $conexao->beginTransaction();

                $stmt = $conexao->prepare('INSERT INTO categoria (nome) VALUES (:nome)');
                $stmt->execute([':nome' => $nome]);

                $categoriaId = (int) $conexao->lastInsertId();
                $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);
                criarAcaoLog($conexao, 'Cadastro', 'categoria', $categoriaId, $usuarioId);
                $conexao->commit();

                header('Location: cadastrar_categoria.php?sucesso=1');
                exit;
            }
        } catch (PDOException $ex) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            if ($ex->getCode() === '23000') {
                $erro = 'Já existe uma categoria com esse nome.';
            } else {
                error_log($ex->getMessage());
                $erro = 'Não foi possível cadastrar a categoria. Tente novamente.';
            }
        } catch (Throwable $ex) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            error_log($ex->getMessage());
            $erro = 'Não foi possível cadastrar a categoria. Tente novamente.';
        }
    }
}

$categorias = $conexao->query('SELECT id, nome FROM categoria ORDER BY nome, id')
    ->fetchAll(PDO::FETCH_ASSOC);

$classeBody = 'pagina-categoria';
include __DIR__ . '/../includes/header.php';
?>

<section class="categoria-pagina">
    <div class="categoria-titulo">
        <h2>Cadastrar categoria</h2>
        <p>Adicione categorias para organizar as ações do sistema.</p>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
        <p class="aviso" role="status">Categoria cadastrada com sucesso.</p>
    <?php endif; ?>

    <?php if ($erro !== ''): ?>
        <p class="aviso" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="cadastrar_categoria.php">
        <label for="nome">Nome da categoria:</label>
        <input type="text" id="nome" name="nome" maxlength="100" required
               value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>">

        <div class="categoria-acoes">
            <button class="btn" type="submit">Cadastrar</button>
            <a class="btn" href="index.php">Voltar ao painel</a>
        </div>
    </form>

    <div class="categoria-lista">
        <h3>Categorias cadastradas</h3>
        <?php if ($categorias): ?>
            <table>
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Nome</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $categoria): ?>
                        <tr>
                            <td><?= (int) $categoria['id'] ?></td>
                            <td><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhuma categoria cadastrada.</p>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
