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
        die("Erro de segurança: Token CSRF inválido.");
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

        $erro_login = "Ocorreu um erro interno. Por favor, tente novamente mais tarde.";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="pg-login d-flex align-items-center min-vh-100">

    <div class="container main-container">
        <div class="row align-items-center justify-content-center gap-4 gap-md-0">
            
            <div class="col-12 col-md-6 text-center brand-section">
                <img src="../assets/img/Logo_MaxTech.jpg" alt="Logo da empresa MaxTech" class="brand-logo-large">
            </div>

            <div class="col-12 col-md-6 col-lg-5">
                <div class="login-box p-4 p-sm-5">
                    
                    <div class="mb-4">
                        <h2 class="h3 fw-bold mb-1">Acesse sua conta</h2>
                        <p class="text-muted small">Informe seus dados para entrar no sistema.</p>
                    </div>

                    <?php if (!empty($erro_login)): ?>
                        <div class="alert-custom-danger d-flex align-items-center p-3 mb-4" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            <div><?= htmlspecialchars($erro_login) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="mb-3">
                            <label for="usuario" class="form-label fw-semibold" style="color: var(--cor-texto-claro);">Usuário</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-user input-icon"></i>
                                <input type="text" id="usuario" name="usuario" class="form-control" value="<?= htmlspecialchars($usuario ?? '') ?>" required placeholder="Digite seu usuário">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="senha" class="form-label fw-semibold" style="color: var(--cor-texto-claro);">Senha</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-lock input-icon"></i>
                                <input type="password" id="senha" name="senha" class="form-control" required placeholder="Digite sua senha">
                                <i class="fa-solid fa-eye toggle-password" id="btnToggleSenha"></i>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="remember-me">
                                <input type="checkbox" id="lembrar" name="lembrar">
                                <label for="lembrar">Lembrar de mim</label>
                            </div>
                            </div>
                        
                        <button type="submit" class="btn-custom-primary w-100 py-3 fw-bold fs-6">
                            ENTRAR
                        </button>
                    </form>

                    <div class="separator my-4">
                        <span class="px-3 text-muted small">ou</span>
                    </div>

                    <button type="button" class="btn-custom-secondary w-100 py-3 fw-bold fs-6 d-flex justify-content-center align-items-center" onclick="window.location.href='cadastro.php'">
                        <i class="fa-solid fa-user-plus me-2"></i> CRIAR CONTA
                    </button>

                </div> 
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const btnToggleSenha = document.querySelector('#btnToggleSenha');
        const inputSenha = document.querySelector('#senha');

        btnToggleSenha.addEventListener('click', function () {
            const isPassword = inputSenha.getAttribute('type') === 'password';
            inputSenha.setAttribute('type', isPassword ? 'text' : 'password');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>