<?php
// index.php - login (back-end + front-end)

if (!isset($_SESSION)) {
    session_start();
}

$erro_login = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'conexao.php';

    $usuario = $mysqli->real_escape_string($_POST['usuario'] ?? '');
    $senha   = $mysqli->real_escape_string($_POST['senha'] ?? '');

    $sql   = "SELECT * FROM usuarios WHERE usuario = '$usuario' AND senha = '$senha'";
    $query = $mysqli->query($sql) or die("Erro SQL: " . $mysqli->error);

    if ($query->num_rows === 1) {
        $dados = $query->fetch_assoc();
        $_SESSION['id']   = $dados['id'];
        $_SESSION['nome'] = $dados['nome'];

        header("Location: painel.php");
        exit;
    } else {
        $erro_login = "Usuário ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistema de Estoque</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-login">
    <div class="container form-container">
        <h1>Login</h1>
        <p class="subtitulo">Sistema de Estoque</p>

        <?php if (!empty($erro_login)): ?>
            <p class="mensagem erro"><?= htmlspecialchars($erro_login) ?></p>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="campo">
                <label for="usuario">Usuário</label>
                <input type="text" id="usuario" name="usuario" required>
            </div>
            <div class="campo">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <button type="submit" class="btn btn-principal">Entrar</button>
        </form>

        <p class="link-rodape">
            Ainda não tem cadastro?
            <a href="cadastro.php">Cadastre-se aqui</a>
        </p>
    </div>
</body>
</html>