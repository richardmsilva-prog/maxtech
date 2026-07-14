<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION)) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem = "";
$tipo_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Erro de segurança: Token CSRF inválido.");
    }

    $nome     = trim($_POST["nome"]      ?? "");
    $usuario  = trim($_POST["usuario"]   ?? "");
    $senha    = $_POST["senha"]          ?? ""; 
    $email    = trim($_POST["email"]     ?? "");
    $telefone = trim($_POST["telefone"]  ?? "");
    $endereco = trim($_POST["endereco"]  ?? "");

    if (empty($nome) || empty($usuario) || empty($senha) || empty($email)) {
        $mensagem = "Preencha todos os campos obrigatórios (Nome, Usuário, Senha e Email).";
        $tipo_msg = "erro";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "O formato do e-mail informado é inválido.";
        $tipo_msg = "erro";
    } else if (strlen($senha) < 8) {
        $mensagem = "A senha deve ter pelo menos 8 caracteres.";
        $tipo_msg = "erro";
    } else {
        try {
            require_once "../systems/core/conexao.php";

            $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $query = $stmt->get_result();

            if ($query->num_rows > 0) {
                $mensagem = "Já existe um usuário com esse nome de usuário. Tente outro.";
                $tipo_msg = "erro";
                $stmt->close(); 
            } else {
                $stmt->close(); 

                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
                $stmt = $mysqli->prepare("INSERT INTO usuarios (nome, usuario, senha, email, telefone, endereco) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $nome, $usuario, $senha_hash, $email, $telefone, $endereco);
        
                if ($stmt->execute()) {
                    $mensagem = "Cadastro realizado com sucesso! Você já pode fazer login.";
                    $tipo_msg = "sucesso";
                    
                    $_POST = []; 
                }
                $stmt->close(); 
            }

        } catch (Exception $e) {
            error_log("Erro no cadastro: " . $e->getMessage());
            $mensagem = "Ocorreu um erro interno. Por favor, tente novamente mais tarde.";
            $tipo_msg = "erro";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário - MaxTech</title>
    <meta name="description" content="Crie sua conta na MaxTech, loja de eletrodomésticos. Preencha o formulário de cadastro com seus dados para acessar o portal do cliente.">
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="pg-cadastro d-flex align-items-center min-vh-100">

    <div class="container main-container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 d-flex justify-content-center">
                
                <div class="cadastro-box p-4 p-md-5">
                    
                    <div class="text-center mb-4">
                        <i class="fa-solid fa-user-plus" style="font-size: 3.5rem; color: var(--cor-primaria); margin-bottom: 15px;"></i>
                        <h2 class="h3 fw-bold mb-1">Criar Conta</h2>
                        <p class="text-muted">Portal do Cliente - MaxTech</p>
                    </div>

                    <?php if (!empty($mensagem)): ?>
                        <?php 
                            $alertClass = ($tipo_msg === 'sucesso') ? 'alert-custom-success' : 'alert-custom-danger';
                            $iconClass  = ($tipo_msg === 'sucesso') ? 'fa-circle-check' : 'fa-triangle-exclamation';
                        ?>
                        <div class="<?= $alertClass ?> d-flex align-items-center p-3 mb-4" role="alert">
                            <i class="fa-solid <?= $iconClass ?> me-2"></i>
                            <div><?= htmlspecialchars($mensagem) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="cadastro.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label fw-semibold" style="color: var(--cor-texto-claro);">Nome Completo *</label>
                                <div class="input-wrapper">
                                    <i class="fa-regular fa-id-card input-icon"></i>
                                    <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required placeholder="Seu nome">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="usuario" class="form-label fw-semibold" style="color: var(--cor-texto-claro);">Nome de Usuário *</label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-user input-icon"></i>
                                    <input type="text" id="usuario" name="usuario" class="form-control" value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" required placeholder="Ex: joaosilva">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label fw-semibold" style="color: var(--cor-texto-claro);">Email *</label>
                                <div class="input-wrapper">
                                    <i class="fa-regular fa-envelope input-icon"></i>
                                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required placeholder="seu@email.com">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="telefone" class="form-label fw-semibold" style="color: var(--cor-texto-claro);">Telefone</label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-phone input-icon"></i>
                                    <input type="tel" id="telefone" name="telefone" class="form-control" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>" placeholder="(00) 00000-0000">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="endereco" class="form-label fw-semibold" style="color: var(--cor-texto-claro);">Endereço</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-map-location-dot input-icon"></i>
                                <input type="text" id="endereco" name="endereco" class="form-control" value="<?= htmlspecialchars($_POST['endereco'] ?? '') ?>" placeholder="Rua, Número, Cidade, Estado">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="senha" class="form-label fw-semibold" style="color: var(--cor-texto-claro);">Senha *</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-lock input-icon"></i>
                                <input type="password" id="senha" name="senha" class="form-control" required placeholder="Mínimo de 8 caracteres">
                            </div>
                        </div>

                        <button type="submit" class="btn-custom-primary w-100 py-2 fw-bold d-flex justify-content-center align-items-center">
                            <i class="fa-solid fa-check me-2"></i> Cadastrar
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <span class="text-muted small">Já tem uma conta?</span><br>
                        <a href="login.php" class="text-decoration-none fw-bold small hover-primary" style="color: var(--cor-primaria); letter-spacing: 0.5px;">
                            <i class="fa-solid fa-arrow-left fa-sm me-1"></i> Faça login aqui
                        </a>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>