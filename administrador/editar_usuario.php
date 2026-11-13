<?php
session_start();
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/criar_acao_log.php';

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
$dados = ['nome' => '', 'email' => '', 'matricula' => ''];
$usuarioEdicao = null;
$cargos = $db->query(
    'SELECT id, titulo FROM cargo ORDER BY titulo'
)->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    foreach ($dados as $campo => $valor) {
        $dados[$campo] = is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
    }

    $cargosSelecionados = [];

    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($csrf, $_POST['csrf'])) {
        $erro = 'Solicitação inválida. Recarregue a página.';
    } elseif (!in_array($acao, ['editar', 'desativar', 'ativar'], true)) {
        $erro = 'Operação inválida.';
    } else {
        try {
            if (in_array($acao, ['editar', 'desativar', 'ativar'], true) && (int) $id === 1) {
                throw new InvalidArgumentException('O administrador do sistema (ID 1) não pode ser alterado.');
            }

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

                $db->beginTransaction();
                $consulta = $db->prepare('UPDATE usuario SET ativo = 0 WHERE id = ? AND ativo = 1');
                $consulta->execute([$id]);
                if ($consulta->rowCount()) {
                    criarAcaoLog($db, 'Desativar', 'usuario', (int) $id, (int) $adminId);
                    $mensagem = 'Usuário desativado.';
                } else {
                    $mensagem = 'O usuário já estava inativo.';
                }
                $db->commit();
            } elseif ($acao === 'ativar') {
                if (!$id) {
                    throw new InvalidArgumentException('Usuário inválido.');
                }

                $db->beginTransaction();
                $consulta = $db->prepare('UPDATE usuario SET ativo = 1 WHERE id = ? AND ativo = 0');
                $consulta->execute([$id]);
                if ($consulta->rowCount()) {
                    criarAcaoLog($db, 'Ativar', 'usuario', (int) $id, (int) $adminId);
                    $mensagem = 'Usuário ativado.';
                } else {
                    $mensagem = 'O usuário já estava ativo ou não foi encontrado.';
                }
                $db->commit();
            } else {
                if (!$id) {
                    throw new InvalidArgumentException('Usuário inválido.');
                }

                if ((int) $id === (int) $adminId) {
                    $consulta = $db->prepare(
                        'SELECT cargo_id
                        FROM usuario_cargo
                        WHERE usuario_id = ?'
                    );

                    $consulta->execute([$id]);

                    $cargosSelecionados = array_map(
                        'intval',
                        $consulta->fetchAll(PDO::FETCH_COLUMN)
                    );

                } else {
                    $cargosRecebidos = $_POST['cargos'] ?? [];

                    if (!is_array($cargosRecebidos)) {
                        $cargosRecebidos = [];
                    }

                    $cargosSelecionados = array_values(array_unique(
                        array_filter(
                            array_map('intval', $cargosRecebidos),
                            fn($cargoId) => isset($cargos[$cargoId])
                        )
                    ));

                    if (!$cargosSelecionados) {
                        throw new InvalidArgumentException(
                            'Selecione pelo menos um cargo.'
                        );
                    }
                }

                $consulta = $db->prepare('SELECT id, nome, email, matricula, ativo FROM usuario WHERE id = ?');//
                $consulta->execute([$id]);
                $usuarioEdicao = $consulta->fetch(PDO::FETCH_ASSOC);
                if (!$usuarioEdicao) {
                    throw new InvalidArgumentException('Usuário não encontrado.');
                }
                $usuarioEdicao = array_merge($usuarioEdicao, $dados);

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
                $consulta->execute([$dados['email'], $matricula, $id]);
                if ($consulta->fetchColumn()) {
                    throw new InvalidArgumentException('E-mail ou matrícula já utilizado por outro usuário.');
                }

                $db->beginTransaction();
                $consulta = $db->prepare(
                    'UPDATE usuario SET nome = ?, email = ?, matricula = ? WHERE id = ?'
                );
                $consulta->execute([$dados['nome'], $dados['email'], $matricula, $id]);
                if ((int) $id !== (int) $adminId) {
                    $consulta = $db->prepare(
                        'DELETE FROM usuario_cargo WHERE usuario_id = ?'
                    );
                    $consulta->execute([$id]);

                    $consultaCargo = $db->prepare(
                        'INSERT INTO usuario_cargo (usuario_id, cargo_id)
                        VALUES (?, ?)'
                    );

                    foreach ($cargosSelecionados as $cargoId) {
                        $consultaCargo->execute([$id, $cargoId]);
                    }
                }
                criarAcaoLog($db, 'Edição', 'usuario', (int) $id, (int) $adminId);
                $db->commit();
                $mensagem = 'Usuário atualizado.';
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
    $id = filter_var(
        $_GET['editar'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if (!$id) {
        $erro = 'Usuário inválido.';
    } elseif ((int) $id === 1) {
        $erro = 'O administrador do sistema (ID 1) não pode ser alterado.';
    } else {
        $consulta = $db->prepare(
            'SELECT id, nome, email, matricula, ativo
             FROM usuario
             WHERE id = ?'
        );

        $consulta->execute([$id]);
        $usuarioEdicao = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$usuarioEdicao) {
            $erro = 'Usuário não encontrado.';
        } else {
            $consulta = $db->prepare(
                'SELECT cargo_id
                 FROM usuario_cargo
                 WHERE usuario_id = ?'
            );

            $consulta->execute([$id]);

            $cargosSelecionados = array_map(
                'intval',
                $consulta->fetchAll(PDO::FETCH_COLUMN)
            );
        }
    }
}

$busca = is_string($_GET['busca'] ?? null) ? trim($_GET['busca']) : '';
$consulta = $db->prepare(
    'SELECT u.id, u.nome, u.email, u.matricula, u.ativo
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

    <?php if ($usuarioEdicao): ?>
        <section>
            <h2>Editar usuário</h2>
            <form method="post" action="editar_usuario.php" class="campos">
                <input type="hidden" name="csrf" value="<?= escapar($csrf) ?>">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" value="<?= (int) $usuarioEdicao['id'] ?>">

                <label for="nome">Nome</label>
                <input id="nome" name="nome" maxlength="100" required value="<?= escapar($formulario['nome']) ?>">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" maxlength="100" required value="<?= escapar($formulario['email']) ?>">
                <label for="matricula">Matrícula</label>
                <input id="matricula" name="matricula" type="number" min="1" max="2147483647" required value="<?= escapar($formulario['matricula']) ?>">
                <?php if ((int) $usuarioEdicao['id'] !== (int) $adminId): ?>
                    <fieldset>
                        <legend>Cargos</legend>

                        <?php foreach ($cargos as $idCargo => $titulo): ?>
                            <label class="cargo-opcao">
                                <input
                                    type="checkbox"
                                    name="cargos[]"
                                    value="<?= (int) $idCargo ?>"
                                    <?= in_array(
                                        (int) $idCargo,
                                        $cargosSelecionados,
                                        true
                                    ) ? 'checked' : '' ?>
                                >
                                <span><?= escapar($titulo) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                <?php else: ?>
                    <p>Você não pode editar seus próprios cargos.</p>
                <?php endif; ?>

                <div class="acoes">
                    <button type="submit">Salvar alterações</button>
                    <a class="btn-limpar-busca" href="editar_usuario.php">Cancelar</a>
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
                                    <?php if ((int) $usuario['id'] !== 1): ?>
                                        <a class="btn-editar-usuario" href="?editar=<?= (int) $usuario['id'] ?>">
                                            Editar<span class="sr-only"> <?= escapar($usuario['nome']) ?></span>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ((int) $usuario['id'] !== 1 && (int) $usuario['id'] !== (int) $adminId): ?>
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