<!-- <?php
$host = 'localhost';
$banco = 'locadora';
$usuario = 'root';
$senha = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8mb4", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erro na conexão: ' . $e->getMessage());
}
//1. Copie a pasta locadora para C:\xampp\htdocs\.
//2. Inicie o Apache e o MySQL no painel do XAMPP.
//3. Acesse http://localhost/locadora/. -->