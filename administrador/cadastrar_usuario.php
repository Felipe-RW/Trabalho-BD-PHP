<?php
session_start();

require '../includes/conexao.php';
require '../includes/criar_acao_log.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

$erros = [];
$nome = $email = $matricula = '';
$cargosSelecionados = [];

// [id => titulo]
$cargos = $conexao->query('SELECT id, titulo FROM cargo ORDER BY titulo')
                  ->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $cargosSelecionados = array_values(array_filter(
        array_map('intval', (array) ($_POST['cargos'] ?? [])),
        fn($id) => isset($cargos[$id])
    ));

    if (mb_strlen($nome) < 3) {
        $erros[] = 'Informe o nome do usuário.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }

    if (!ctype_digit($matricula)) {
        $erros[] = 'A matrícula deve conter apenas números.';
    }

    if ($senha === '') {
        $erros[] = 'Informe a senha.';
    }

    if (!$cargosSelecionados) {
        $erros[] = 'Selecione pelo menos um cargo.';
    }

    if (!$erros) {
        try {
            $conexao->beginTransaction();

            $stmt = $conexao->prepare(
                'INSERT INTO usuario (nome, email, senha, matricula)
                 VALUES (:nome, :email, :senha, :matricula)'
            );
            $stmt->execute([
                ':nome' => $nome,
                ':email' => strtolower($email),
                ':senha' => $senha,
                ':matricula' => (int) $matricula
            ]);

            $novo_usuario_id = (int) $conexao->lastInsertId();

            $stmtCargo = $conexao->prepare(
                'INSERT INTO usuario_cargo (usuario_id, cargo_id)
                 VALUES (:usuario_id, :cargo_id)'
            );
            foreach ($cargosSelecionados as $cargo_id) {
                $stmtCargo->execute([
                    ':usuario_id' => $novo_usuario_id,
                    ':cargo_id' => $cargo_id
                ]);
            }

            criarAcaoLog(
                $conexao,
                'Cadastro',
                'usuario',
                $novo_usuario_id,
                (int) $_SESSION['usuario_id']
            );

            $conexao->commit();

            header('Location: index.php');
            exit;

        } catch (PDOException $ex) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            $erros[] = $ex->getCode() === '23000'
                ? 'E-mail já cadastrado.'
                : 'Erro ao salvar o cadastro.';
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
    <h1>Cadastrar Usuário</h1>

    <?php if ($erros): ?>
        <ul>
            <?php foreach ($erros as $erro): ?>
                <li><?= htmlspecialchars($erro) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post">
        <label>Nome:
            <input type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" required>
        </label><br>

        <label>E-mail:
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </label><br>

        <label>Matrícula:
            <input type="text" name="matricula" value="<?= htmlspecialchars($matricula) ?>" required>
        </label><br>

        <label>Senha:
            <input type="password" name="senha" required>
        </label><br>

        <fieldset>
            <legend>Cargos</legend>
            <?php foreach ($cargos as $id => $titulo): ?>
                <label>
                    <input type="checkbox" name="cargos[]" value="<?= $id ?>"
                        <?= in_array($id, $cargosSelecionados, true) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($titulo) ?>
                </label><br>
            <?php endforeach; ?>
        </fieldset>

        <button type="submit">Cadastrar</button>
        <a href="index.php">Cancelar</a>
    </form>
<?php include '../includes/footer.php'; ?>
