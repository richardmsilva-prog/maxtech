<?php

if (!isset($_SESSION)) {
    session_start();
}

if (isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$erro_login = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'conexao.php';

    $usuario = $mysqli->real_escape_string($_POST['usuario'] ?? '');
    $senha   = $mysqli->real_escape_string($_POST['senha'] ?? '');

    $sql   = "SELECT * FROM usuarios WHERE usuario = '$usuario'";
    $query = $mysqli->query($sql) or die("Erro SQL: " . $mysqli->error);

    if ($query->num_rows === 1) {
        $dados = $query->fetch_assoc();
        
        if (password_verify($senha, $dados['senha'])) {

            session_regenerate_id(true);

            $_SESSION['id']   = $dados['id'];
            $_SESSION['nome'] = $dados['nome'];

            header("Location: index.php");
            exit;
        } else {
            $erro_login = "Usuário ou senha inválidos.";
        }
    } else {
        $erro_login = "Usuário ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MaxTech</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-login">
    <div class="container form-container">
        <h1>Login</h1>
        <p class="subtitulo">Portal do Cliente - MaxTech</p>

        <?php if (!empty($erro_login)): ?>
            <p class="mensagem erro"><?= htmlspecialchars($erro_login) ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
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