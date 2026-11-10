<?php
$pesquisador = "Pesquisador";

$pendentes = 0;
$aprovadas = 0;
$recusadas = 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Area do Pesquisador</title>

    <link rel="stylesheet" href="../css/estilo.css">
</head>

<body>

    <header>
        <h1>Embrapa</h1>
    </header>

    <main class="conteudo">

        <div class="painel-pesquisador">
            <div class="cabecalho-pesquisador">
                <div class="botoes-pesquisador">
                    <button type="button" class="btn btn-visualizar-acoes">Visualizar ações</button>
                    <a class="btn-criar-acao" href="cadastrar_acao.php">Criar nova ação</a>
                </div>
                <section class="boas-vindas">
                    <h2>Área do pesquisador</h2>

                    <h2>Bem-vindo!</h2>

                    <p>Acompanhe suas ações e solicitações.</p>
                </section>
            </div>

            <section class="acoes">

        <div class="card-pesquisador card-pendente">
            <h3>Ações pendentes</h3>
            <p class="card-quantidade"><strong><?= $pendentes ?></strong> ações</p>
        </div>

        <div class="card-pesquisador card-aprovada">
            <h3>Ações aprovadas</h3>
            <p class="card-quantidade"><strong><?= $aprovadas ?></strong> ações</p>
        </div>

        <div class="card-pesquisador card-recusada">
            <h3>Ações recusadas</h3>
            <p class="card-quantidade"><strong><?= $recusadas ?></strong> ações</p>
        </div>

            </section>
        </div>

    </main>

    <footer>
        Projeto Banco de Dados
    </footer>

</body>
</html>
