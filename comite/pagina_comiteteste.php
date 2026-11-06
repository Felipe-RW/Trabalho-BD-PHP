
<?php include '../includes/header.php';
require'../includes/conexao.php';

$resultado = $pdo->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu do Comitê</title>
    <link rel="stylesheet" href="../css/estilo.css">
</head>

<body>

    <main>

        <div class="frame_login">

            <div class="menu_comite">

                <h1>Menu do Comitê:</h1>

                <form class="painel_comite" action="" method="POST">

                    <div class="btn_comite">

                        <a class="btn btn_comite_item" href="index.php">
                            Membros do Comitê
                        </a>

                        <a class="btn btn_comite_item" href="../validador/validar_acao.php">
                            Avaliar ações
                        </a>

                        <a class="btn btn_comite_item" href="relatorios.php">
                            Relatórios
                        </a>

                    </div>

                </form>

                <h2>Membros cadastrados</h2>

                <table>

                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Matrícula</th>
                            <th>Cargo</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($resultado->rowCount() > 0): ?>

                            <?php while ($membro = $resultado->fetch(PDO::FETCH_ASSOC)): ?>

                                <tr>
                                    <td>
                                        <?= htmlspecialchars($membro['nome']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($membro['email']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($membro['matricula']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($membro['cargo']) ?>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="4">
                                    Nenhum membro do Comitê cadastrado.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</body>

</html>

<?php include '../includes/footer.php'; ?>
