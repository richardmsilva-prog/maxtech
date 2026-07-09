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

    <style>
        .card-login {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .input-group-text {
            background-color: transparent;
            border-right: none;
        }
        .form-control.input-login {
            border-left: none;
        }
        .form-control.input-login:focus {
            box-shadow: none;
            border-color: #dee2e6;
        }
    </style>
</head>
<body class="pagina-login d-flex align-items-center min-vh-100" style="background-color: #f4f6f9;">

    <div class="container form-container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6 col-lg-4">
                
                <div class="card card-login">
                    <div class="card-body p-5">
                        
                        <div class="text-center mb-4">
                            <i class="fa-solid fa-circle-user" style="font-size: 4rem; color: #0d6efd; margin-bottom: 15px;"></i>
                            <h1 class="h3 fw-bold mb-1">Login</h1>
                            <p class="text-muted subtitulo">Portal do Cliente - MaxTech</p>
                        </div>

                        <?php if (!empty($erro_login)): ?>
                            <div class="alert alert-danger d-flex align-items-center p-3 mb-4" role="alert">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                <div><?= htmlspecialchars($erro_login) ?></div>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="mb-3 campo">
                                <label for="usuario" class="form-label fw-semibold text-secondary">Usuário</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-user text-muted"></i>
                                    </span>
                                    <input type="text" id="usuario" name="usuario" class="form-control input-login" value="<?= htmlspecialchars($usuario ?? '') ?>" required placeholder="Digite seu usuário">
                                </div>
                            </div>
                            
                            <div class="mb-4 campo">
                                <label for="senha" class="form-label fw-semibold text-secondary">Senha</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" id="senha" name="senha" class="form-control input-login" required placeholder="Digite sua senha">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2 btn-principal" style="border-radius: 8px; font-weight: bold;">
                                <i class="fa-solid fa-right-to-bracket me-2"></i> Entrar
                            </button>
                        </form>

                        <div class="text-center mt-4 pt-3 border-top link-rodape">
                            <span class="text-muted small">Ainda não tem cadastro?</span><br>
                            <a href="cadastro.php" class="text-decoration-none fw-bold" style="color: #0d6efd; letter-spacing: 0.5px;">
                                Cadastre-se aqui <i class="fa-solid fa-arrow-right fa-sm ms-1"></i>
                            </a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>