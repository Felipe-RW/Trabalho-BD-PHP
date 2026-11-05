<?php
session_start();
require_once __DIR__ . '/../includes/conexao.php';

// O projeto ainda não possui autenticação. Restrinja esta página ao administrador
// quando o login for implementado.
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function escapar($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$erro = '';
$mensagem = $_SESSION['mensagem_usuario'] ?? '';
unset($_SESSION['mensagem_usuario']);
$usuarioEdicao = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $acao = $_POST['acao'] ?? '';
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $erro = 'Solicitação inválida. Recarregue a página e tente novamente.';
    } elseif (!$id || !in_array($acao, ['editar', 'desativar'], true)) {
        $erro = 'Usuário ou operação inválida.';
    } else {
        $consulta = $pdo->prepare('SELECT id, nome, email, matricula, ativo FROM usuario WHERE id = ?');
        $consulta->execute([$id]);
        $usuarioEdicao = $consulta->fetch();
        if (!$usuarioEdicao) {
            $erro = 'Usuário não encontrado.';
        } else {
            switch ($acao) {
            case 'desativar':
            $comando = $pdo->prepare('UPDATE usuario SET ativo = 0 WHERE id = ?');
            $comando->execute([$id]);
            $_SESSION['mensagem_usuario'] = 'Usuário desativado com sucesso.';
            header('Location: index.php');
            exit;

            case 'editar':
            $nome = is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : '';
            $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
            $matricula = filter_var($_POST['matricula'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
            $usuarioEdicao['nome'] = $nome;
            $usuarioEdicao['email'] = $email;
            $usuarioEdicao['matricula'] = is_string($_POST['matricula'] ?? null) ? $_POST['matricula'] : '';
            if ($nome === '' || strlen($nome) > 100 || strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$matricula) {
                $erro = 'Informe nome e e-mail válidos (até 100 bytes) e uma matrícula inteira positiva.';
            } else {
                try {
                    // O ID e a senha não são alterados por este formulário.
                    $comando = $pdo->prepare('UPDATE usuario SET nome = ?, email = ?, matricula = ? WHERE id = ?');
                    $comando->execute([$nome, $email, $matricula, $id]);
                    $_SESSION['mensagem_usuario'] = 'Usuário atualizado com sucesso.';
                    header('Location: index.php');
                    exit;
                } catch (PDOException $e) {
                    if (($e->errorInfo[1] ?? null) === 1062) {
                        $erro = 'Este e-mail já está sendo utilizado por outro usuário.';
                    } else {
                        error_log($e->getMessage());
                        $erro = 'Não foi possível atualizar o usuário. Tente novamente.';
                    }
                }
            }
            break;

            default:
                $erro = 'Operação inválida.';
                break;
            }
        }
    }
} elseif (isset($_GET['editar'])) {
    $id = filter_var($_GET['editar'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id) {
        $consulta = $pdo->prepare('SELECT id, nome, email, matricula, ativo FROM usuario WHERE id = ?');
        $consulta->execute([$id]);
        $usuarioEdicao = $consulta->fetch();
    }
    if (!$usuarioEdicao) {
        $erro = 'Usuário não encontrado.';
    }
}
$usuarios = $pdo->query('SELECT id, nome, email, matricula, ativo FROM usuario ORDER BY nome, id')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar usuários</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f2f4f8; color: #222; font-family: Arial, sans-serif; }
        header { padding: 24px; background: #1f3a5f; color: white; }
        header h1 { margin: 0; font-size: 24px; }
        main { max-width: 1100px; margin: 32px auto; padding: 0 16px; }
        .aviso, .sucesso, .erro { padding: 14px; border-radius: 6px; }
        .aviso { background: #fff3cd; }
        .sucesso { background: #d1e7dd; }
        .erro { background: #f8d7da; }
        .edicao { max-width: 520px; background: white; padding: 24px; border-radius: 8px; margin-bottom: 28px; }
        label { display: block; margin: 16px 0 6px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #aaa; border-radius: 4px; }
        button, .botao { display: inline-block; border: 0; border-radius: 4px; padding: 10px 14px; background: #1f3a5f; color: white; font: inherit; text-decoration: none; cursor: pointer; }
        .desativar { background: #a3212c; }
        .acoes { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .edicao .acoes { margin-top: 20px; }
        .tabela { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #e6edf7; }
    </style>
</head>
<body>
<header><h1>Gerenciar usuários</h1></header>
<main>
    <p>Edite os dados ou desative um usuário existente. Desativar não exclui o registro.</p>
    <p class="aviso">Ambiente de desenvolvimento: esta página ainda não possui controle de acesso. Não a disponibilize publicamente antes de integrar o login de administrador.</p>
    <?php if ($mensagem): ?><p class="sucesso" role="status"><?= escapar($mensagem) ?></p><?php endif; ?>
    <?php if ($erro): ?><p class="erro" role="alert"><?= escapar($erro) ?></p><?php endif; ?>

    <?php if ($usuarioEdicao): ?>
    <section class="edicao">
        <h2>Editar usuário</h2>
        <form method="post" action="index.php">
            <input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" name="id" value="<?= escapar($usuarioEdicao['id']) ?>">
            <label for="nome">Nome</label>
            <input id="nome" name="nome" maxlength="100" value="<?= escapar($usuarioEdicao['nome']) ?>" required>
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" maxlength="100" value="<?= escapar($usuarioEdicao['email']) ?>" required>
            <label for="matricula">Matrícula</label>
            <input id="matricula" name="matricula" type="number" min="1" max="2147483647" step="1" value="<?= escapar($usuarioEdicao['matricula']) ?>" required>
            <div class="acoes"><button type="submit">Salvar alterações</button><a href="index.php">Cancelar</a></div>
        </form>
    </section>
    <?php endif; ?>

    <h2>Usuários</h2>
    <div class="tabela">
    <table>
        <thead><tr><th>Nome</th><th>E-mail</th><th>Matrícula</th><th>Situação</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($usuarios as $usuario): ?>
            <tr>
                <td><?= escapar($usuario['nome']) ?></td>
                <td><?= escapar($usuario['email']) ?></td>
                <td><?= escapar($usuario['matricula']) ?></td>
                <td><?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                <td><div class="acoes">
                    <a class="botao" href="?editar=<?= escapar($usuario['id']) ?>">Editar</a>
                    <?php if ($usuario['ativo']): ?>
                    <form method="post" action="index.php" onsubmit="return confirm('Deseja realmente desativar este usuário?');">
                        <input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
                        <input type="hidden" name="acao" value="desativar">
                        <input type="hidden" name="id" value="<?= escapar($usuario['id']) ?>">
                        <button class="desativar" type="submit">Desativar</button>
                    </form>
                    <?php endif; ?>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$usuarios): ?><tr><td colspan="5">Nenhum usuário cadastrado.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</main>
</body>
</html>
