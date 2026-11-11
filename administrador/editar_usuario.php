<?php
session_start();
require_once __DIR__ . '/../includes/conexao.php';

$db = $conexao ?? $pdo;

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$adminId = filter_var($_SESSION['usuario_id'] ?? '', FILTER_VALIDATE_INT);
if (!$adminId) {
    header('Location: ../index.html');
    exit;
}

$consulta = $db->prepare(
    'SELECT u.id
     FROM usuario u
     JOIN usuario_cargo uc ON uc.usuario_id = u.id
     JOIN cargo c ON c.id = uc.cargo_id
     WHERE u.id = ? AND u.ativo = 1 AND c.titulo = ?'
);
$consulta->execute([$adminId, 'Administrador']);
if (!$consulta->fetchColumn()) {
    http_response_code(403);
    exit('Acesso permitido somente a administradores ativos.');
}

if (empty($_SESSION['csrf_usuarios'])) {
    $_SESSION['csrf_usuarios'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['csrf_usuarios'];
$erro = '';
$mensagem = $_SESSION['mensagem_usuario'] ?? '';
unset($_SESSION['mensagem_usuario']);
$pagina = ($_GET['pagina'] ?? '') === 'cadastrar' ? 'cadastrar' : 'listar';
$dados = ['nome' => '', 'email' => '', 'matricula' => ''];
$cargoSelecionado = '';
$usuarioEdicao = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $pagina = $acao === 'cadastrar' ? 'cadastrar' : 'listar';

    foreach ($dados as $campo => $valor) {
        $dados[$campo] = is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
    }
    $cargoSelecionado = filter_var($_POST['cargo_id'] ?? '', FILTER_VALIDATE_INT);

    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($csrf, $_POST['csrf'])) {
        $erro = 'Solicitação inválida. Recarregue a página.';
    } elseif (!in_array($acao, ['cadastrar', 'editar', 'desativar', 'ativar'], true)) {
        $erro = 'Operação inválida.';
    } else {
        try {
            if ($acao === 'desativar') {
                if (!$id) {
                    throw new InvalidArgumentException('Usuário inválido.');
                }
                if ((int) $id === (int) $adminId) {
                    throw new InvalidArgumentException('Você não pode desativar o próprio usuário.');
                }

                $consulta = $db->prepare('SELECT id FROM usuario WHERE id = ?');
                $consulta->execute([$id]);
                if (!$consulta->fetchColumn()) {
                    throw new InvalidArgumentException('Usuário não encontrado.');
                }

                $consulta = $db->prepare('UPDATE usuario SET ativo = 0 WHERE id = ? AND ativo = 1');
                $consulta->execute([$id]);
                $mensagem = $consulta->rowCount() ? 'Usuário desativado.' : 'O usuário já estava inativo.';
            } elseif ($acao === 'ativar') {
                if (!$id) {
                    throw new InvalidArgumentException('Usuário inválido.');
                }

                $consulta = $db->prepare('UPDATE usuario SET ativo = 1 WHERE id = ? AND ativo = 0');
                $consulta->execute([$id]);
                $mensagem = $consulta->rowCount() ? 'Usuário ativado.' : 'O usuário já estava ativo ou não foi encontrado.';
            } else {
                if ($acao === 'editar') {
                    if (!$id) {
                        throw new InvalidArgumentException('Usuário inválido.');
                    }
                    $consulta = $db->prepare('SELECT id, nome, email, matricula, ativo FROM usuario WHERE id = ?');
                    $consulta->execute([$id]);
                    $usuarioEdicao = $consulta->fetch(PDO::FETCH_ASSOC);
                    if (!$usuarioEdicao) {
                        throw new InvalidArgumentException('Usuário não encontrado.');
                    }
                    $usuarioEdicao = array_merge($usuarioEdicao, $dados);
                }

                $matricula = filter_var($dados['matricula'], FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1, 'max_range' => 2147483647],
                ]);
                if (
                    $dados['nome'] === '' || strlen($dados['nome']) > 100 ||
                    strlen($dados['email']) > 100 || !filter_var($dados['email'], FILTER_VALIDATE_EMAIL) ||
                    !$matricula
                ) {
                    throw new InvalidArgumentException('Informe nome e e-mail válidos e matrícula inteira positiva.');
                }

                $consulta = $db->prepare(
                    'SELECT id FROM usuario WHERE (email = ? OR matricula = ?) AND id <> ?'
                );
                $consulta->execute([$dados['email'], $matricula, $acao === 'editar' ? $id : 0]);
                if ($consulta->fetchColumn()) {
                    throw new InvalidArgumentException('E-mail ou matrícula já utilizado por outro usuário.');
                }

                if ($acao === 'cadastrar') {
                    $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';
                    $consulta = $db->prepare('SELECT id FROM cargo WHERE id = ?');
                    $consulta->execute([$cargoSelecionado]);
                    if ($senha === '' || strlen($senha) > 50 || !$consulta->fetchColumn()) {
                        throw new InvalidArgumentException('Informe uma senha de até 50 caracteres e selecione um cargo.');
                    }

                    $db->beginTransaction();
                    $consulta = $db->prepare(
                        'INSERT INTO usuario (nome, email, senha, matricula) VALUES (?, ?, ?, ?)'
                    );
                    $consulta->execute([$dados['nome'], $dados['email'], $senha, $matricula]);
                    $novoId = $db->lastInsertId();

                    $consulta = $db->prepare(
                        'INSERT INTO usuario_cargo (usuario_id, cargo_id) VALUES (?, ?)'
                    );
                    $consulta->execute([$novoId, $cargoSelecionado]);
                    $db->commit();
                    $mensagem = 'Usuário cadastrado.';
                } else {
                    $consulta = $db->prepare(
                        'UPDATE usuario SET nome = ?, email = ?, matricula = ? WHERE id = ?'
                    );
                    $consulta->execute([$dados['nome'], $dados['email'], $matricula, $id]);
                    $mensagem = 'Usuário atualizado.';
                }
            }

            $_SESSION['mensagem_usuario'] = $mensagem;
            header('Location: editar_usuario.php');
            exit;
        } catch (InvalidArgumentException $e) {
            $erro = $e->getMessage();
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log($e->getMessage());
            $erro = 'Não foi possível salvar. Verifique os dados e tente novamente.';
        }
    }
} elseif (isset($_GET['editar'])) {
    $id = filter_var($_GET['editar'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id) {
        $consulta = $db->prepare('SELECT id, nome, email, matricula, ativo FROM usuario WHERE id = ?');
        $consulta->execute([$id]);
        $usuarioEdicao = $consulta->fetch(PDO::FETCH_ASSOC);
    }
    if (!$usuarioEdicao) {
        $erro = 'Usuário não encontrado.';
    }
}

$cargos = $db->query('SELECT id, titulo FROM cargo ORDER BY titulo')->fetchAll(PDO::FETCH_ASSOC);
$busca = is_string($_GET['busca'] ?? null) ? trim($_GET['busca']) : '';
$consulta = $db->prepare(
    'SELECT u.id, u.nome, u.email, u.matricula, u.ativo,
            EXISTS (
                SELECT 1
                FROM usuario_cargo uc
                JOIN cargo c ON c.id = uc.cargo_id
                WHERE uc.usuario_id = u.id AND c.titulo = \'Administrador\'
            ) AS is_admin
     FROM usuario u
     WHERE u.nome LIKE ? OR u.email LIKE ? OR CAST(u.matricula AS CHAR) LIKE ?
     ORDER BY u.nome, u.id'
);
$termoBusca = '%' . $busca . '%';
$consulta->execute([$termoBusca, $termoBusca, $termoBusca]);
$usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);
$formulario = $usuarioEdicao ?: $dados;
$classeBody = 'pagina-usuarios';
?>
<?php include '../includes/header.php'; ?>
    <h1>Gerenciar usuários</h1>

    <?php if ($erro): ?>
        <p class="erro" role="alert"><?= escapar($erro) ?></p>
    <?php endif; ?>
    <?php if ($mensagem): ?>
        <p class="sucesso" role="status"><?= escapar($mensagem) ?></p>
    <?php endif; ?>

    <?php if ($pagina === 'cadastrar' || $usuarioEdicao): ?>
        <section>
            <h2><?= $usuarioEdicao ? 'Editar usuário' : 'Cadastrar usuário' ?></h2>
            <form method="post" action="editar_usuario.php" class="campos">
                <input type="hidden" name="csrf" value="<?= escapar($csrf) ?>">
                <input type="hidden" name="acao" value="<?= $usuarioEdicao ? 'editar' : 'cadastrar' ?>">
                <?php if ($usuarioEdicao): ?>
                    <input type="hidden" name="id" value="<?= (int) $usuarioEdicao['id'] ?>">
                <?php endif; ?>

                <label for="nome">Nome</label>
                <input id="nome" name="nome" maxlength="100" required value="<?= escapar($formulario['nome']) ?>">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" maxlength="100" required value="<?= escapar($formulario['email']) ?>">
                <label for="matricula">Matrícula</label>
                <input id="matricula" name="matricula" type="number" min="1" max="2147483647" required value="<?= escapar($formulario['matricula']) ?>">

                <?php if (!$usuarioEdicao): ?>
                    <label for="senha">Senha</label>
                    <input id="senha" name="senha" type="password" maxlength="50" autocomplete="new-password" required>
                    <label for="cargo">Cargo</label>
                    <select id="cargo" name="cargo_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($cargos as $cargo): ?>
                            <option value="<?= (int) $cargo['id'] ?>" <?= (int) $cargoSelecionado === (int) $cargo['id'] ? 'selected' : '' ?>>
                                <?= escapar($cargo['titulo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <div class="acoes">
                    <button type="submit">Salvar usuário</button>
                    <a href="editar_usuario.php">Cancelar</a>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section>
        <h2>Usuários cadastrados</h2>
        <form method="get" class="busca">
            <label for="busca">Buscar por nome, e-mail ou matrícula</label>
            <input id="busca" name="busca" value="<?= escapar($busca) ?>">
            <button type="submit">Buscar</button>
            <a class="btn-limpar-busca" href="editar_usuario.php">Limpar</a>
        </form>

        <div class="tabela">
            <table>
                <caption>Lista de usuários e ações disponíveis</caption>
                <thead>
                    <tr>
                        <th scope="col">Nome</th>
                        <th scope="col">E-mail</th>
                        <th scope="col">Matrícula</th>
                        <th scope="col">Status</th>
                        <th scope="col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= escapar($usuario['nome']) ?></td>
                            <td><?= escapar($usuario['email']) ?></td>
                            <td><?= escapar($usuario['matricula']) ?></td>
                            <td><?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                            <td>
                                <div class="acoes">
                                    <?php if (!(bool) $usuario['is_admin']): ?>
                                        <a class="btn-editar-usuario" href="?editar=<?= (int) $usuario['id'] ?>">
                                            Editar<span class="sr-only"> <?= escapar($usuario['nome']) ?></span>
                                        </a>
                                        <div class="acoes-status">
                                            <form method="post" action="editar_usuario.php" <?= $usuario['ativo'] ? 'onsubmit="return confirm(\'Desativar este usuário? Ele não poderá mais acessar o sistema.\');"' : '' ?>>
                                                <input type="hidden" name="csrf" value="<?= escapar($csrf) ?>">
                                                <input type="hidden" name="acao" value="<?= $usuario['ativo'] ? 'desativar' : 'ativar' ?>">
                                                <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
                                                <button type="submit" class="<?= $usuario['ativo'] ? 'perigo' : 'ativar' ?>">
                                                    <?= $usuario['ativo'] ? 'Desativar' : 'Ativar' ?><span class="sr-only"> <?= escapar($usuario['nome']) ?></span>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$usuarios): ?>
                        <tr><td colspan="5">Nenhum usuário encontrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <div class="acoes voltar-usuarios">
        <a class="btn-voltar-usuarios" href="index.php"><span aria-hidden="true">←</span> Voltar ao painel</a>
    </div>
<?php include '../includes/footer.php'; ?>
