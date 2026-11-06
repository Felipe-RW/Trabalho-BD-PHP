<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pré-visualização — Cadastrar categoria</title>
    <link rel="stylesheet" href="../css/estilo.css">
</head>
<body>
    <header>
        <h1>Embrapa</h1>
        <nav>
            <a href="index.php">Voltar ao painel</a>
        </nav>
    </header>

    <main>
        <div class="topo">
            <h2>Cadastrar categoria</h2>
            <a class="btn" href="cadastrar_categoria.php">Abrir página funcional</a>
        </div>

        <p class="aviso" role="note">
            Modo de pré-visualização: o formulário não se conecta ao banco e nenhuma categoria será salva.
        </p>

        <form id="form-categoria-preview">
            <label for="nome">Nome da categoria:</label>
            <input type="text" id="nome" name="nome" maxlength="100" required>
            <button class="btn" type="submit">Testar cadastro</button>
        </form>

        <p id="resultado-preview" class="aviso" role="status" hidden></p>
    </main>

    <footer>
        <p>Projeto de Banco de Dados — Pré-visualização</p>
    </footer>

    <script>
        document.getElementById('form-categoria-preview').addEventListener('submit', function (event) {
            event.preventDefault();
            const nome = document.getElementById('nome').value.trim();
            const resultado = document.getElementById('resultado-preview');
            resultado.textContent = `Teste visual concluído para a categoria “${nome}”. Nenhum dado foi salvo.`;
            resultado.hidden = false;
        });
    </script>
</body>
</html>
