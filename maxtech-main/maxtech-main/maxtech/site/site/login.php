<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION)) {
    session_start();
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$erro_login = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if(!isset($_POST["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])) {
        die("Erro de segurança: Token CSRF inválido.")
    }

    try {
        require_once "../systems/core/conexao.php";

        $usuario = trim($_POST['usuario'] ?? '');
        $senha   = $_POST['senha'] ?? '';

        $stmt = $mysqli->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $query = $stmt->get_result();

        if ($query->num_rows === 1) {
            $dados = $query->fetch_assoc();
        
            if (password_verify($senha, $dados['senha'])) {

                session_regenerate_id(true);

                $_SESSION['id']   = $dados['id'];
                $_SESSION['nome'] = $dados['nome'];

                $stmt->close(); 
                header("Location: index.php");
                exit;

            } else {
                $erro_login = "Usuário ou senha inválidos.";
            }
        } else {
            $erro_login = "Usuário ou senha inválidos.";
        }
    
        $stmt->close();

    } catch (Exception $e) {
        error_log("Erro no login: " . $e->getMessage());

        $erro_login = "Ocorreu um erro interno. Por favor, tente novamente mais tarde."
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MaxTech</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="pagina-login">
    <div class="container form-container">
        <h1>Login</h1>
        <p class="subtitulo">Portal do Cliente - MaxTech</p>

        <?php if (!empty($erro_login)): ?>
            <p class="mensagem erro"><?= htmlspecialchars($erro_login) ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="campo">
                <label for="usuario">Usuário</label>
                <input type="text" id="usuario" name="usuario" value="<?= htmlspecialchars($usuario ?? '') ?>" required>
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