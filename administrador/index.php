<?php
$bodyClass = 'pagina-administrador';
include __DIR__ . '/../includes/header.php';
?>

<section class="menu-administrador">
    <h2>Menu do Administrador</h2>

    <div class="opcoes-administrador">
        <article class="card-opcao-administrador">
            <h3>Cadastrar usuário</h3>
            <p>Adicione um novo usuário ao sistema.</p>
            <a class="btn btn-adm" href="cadastrar_usuario.php">Acessar</a>
        </article>

        <article class="card-opcao-administrador">
            <h3>Editar usuário</h3>
            <p>Atualize as informações de um usuário.</p>
            <a class="btn btn-adm" href="editar_usuario.php">Acessar</a>
        </article>

        <article class="card-opcao-administrador">
            <h3>Cadastrar categoria</h3>
            <p>Inclua uma categoria no sistema.</p>
            <a class="btn btn-adm" href="cadastrar_categoria.php">Acessar</a>
        </article>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>