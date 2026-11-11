<?php include '../includes/header.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco de Dados Embrapa</title>
    <link rel="stylesheet" href="../css/estilo.css">
</head>
<body>
    <main>
        <div class="frame_login">
            <div class="menu_administrador">
                <h1>Menu do Administrador:</h1>

                <form class="painel_administrador" action="" method="POST">

                    <div class="btn_administrador">
                        
                        <a class="btn btn_adm" href="cadastrar_usuario.php">Cadastrar usuário</a>
                        <a class="btn btn_adm" href="editar_usuario.php">Editar usuário</a>
                        <a class="btn btn_adm" href="cadastrar_categoria.php">Cadastrar categoria</a>

                    </div>

                </form>

            </div>
        </div>
    </main>
</body>
</html>
<?php include '../includes/footer.php'; ?>