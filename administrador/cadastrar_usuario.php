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
<section class="cadastro-usuario-pagina">
    <div class="cadastro-usuario-titulo">
        <h2>Cadastrar usuário</h2>
    </div>

    <?php if ($erros): ?>
        <ul class="aviso" role="alert">
            <?php foreach ($erros as $erro): ?>
                <li><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="cadastrar_usuario.php">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>" autocomplete="name" required>

        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required>

        <label for="matricula">Matrícula</label>
        <input type="text" id="matricula" name="matricula" value="<?= htmlspecialchars($matricula, ENT_QUOTES, 'UTF-8') ?>" inputmode="numeric" required>

        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" autocomplete="new-password" required>

        <fieldset>
            <legend>Cargos</legend>
            <?php foreach ($cargos as $id => $titulo): ?>
                <label class="cargo-opcao">
                    <input type="checkbox" name="cargos[]" value="<?= $id ?>"
                        <?= in_array($id, $cargosSelecionados, true) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <div class="cadastro-usuario-acoes">
            <button class="btn" type="submit">Cadastrar usuário</button>
            <a class="btn" href="index.php">Voltar ao painel</a>
        </div>
    </form>
</section>
<?php include '../includes/footer.php'; ?>
