<?php
$pesquisador = "Pesquisador";

$pendentes = 3;
$aprovadas = 8;
$analise = 2;
$devolvidas = 1;
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
                <section class="boas-vindas">
                    <h2>Área do pesquisador</h2>

                    <h2>Bem-vindo!</h2>

                    <p>Acompanhe suas ações e solicitações.</p>
                </section>
                <a class="btn-criar-acao" href="cadastrar_acao.php">Criar nova ação</a>
            </div>

            <section class="acoes">

        <div class="card-pesquisador card-pendente">
            <h3>Ações pendentes</h3>
            <p class="card-quantidade"><strong><?= $pendentes ?></strong> ações</p>
            <a href="">Consultar ações pendentes</a>
        </div>

        <div class="card-pesquisador card-aprovada">
            <h3>Ações aprovadas</h3>
            <p class="card-quantidade"><strong><?= $aprovadas ?></strong> ações</p>
            <a href="">Consultar ações aprovadas</a>
        </div>

        <div class="card-pesquisador card-analise">
            <h3>Em análise</h3>
            <p class="card-quantidade"><strong><?= $analise ?></strong> ações</p>
            <a href="">Consultar ações em análise</a>
        </div>

        <div class="card-pesquisador card-devolvida">
            <h3>Ações devolvidas</h3>
            <p class="card-quantidade"><strong><?= $devolvidas ?></strong> ações</p>
            <a href="">Consultar ações devolvidas</a>
        </div>

            </section>
        </div>

    </main>

    <footer>
        Projeto Banco de Dados
    </footer>

</body>
</html>