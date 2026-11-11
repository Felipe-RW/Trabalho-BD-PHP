<?php
$bodyClass = 'pagina-administrador';
include __DIR__ . '/../includes/header.php';
?>

<section class="menu-administrador">
    <h2>Menu do Administrador</h2>

    <div class="card-menu-administrador">
        <a class="btn btn-adm" href="cadastrar_usuario.php">Cadastrar usuário</a>
        <a class="btn btn-adm" href="editar_usuario.php">Editar usuário</a>
        <a class="btn btn-adm" href="cadastrar_categoria.php">Cadastrar categoria</a>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>