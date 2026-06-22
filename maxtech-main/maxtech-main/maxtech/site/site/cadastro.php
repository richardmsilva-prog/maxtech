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
    $senha    =     ($_POST["senha"]     ?? ""); 
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

            $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE usuario = ? OR email = ?");
            $stmt->bind_param("ss", $usuario, $email);
            $stmt->execute();
            $query = $stmt->get_result();

            if ($query->num_rows > 0) {
                $mensagem = "Já existe um usuário com esse nome de usuário ou e-mail. Tente outro.";
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
    <title>Cadastro de Usuário</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="pagina-cadastro">
    <div class="container form-container">
        <h1>Criar Conta</h1>
        <p class="subtitulo">Portal do Cliente - MaxTech</p>

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem <?= htmlspecialchars($tipo_msg) ?>">
                <?= htmlspecialchars($mensagem) ?>
            </p>
        <?php endif; ?>

        <form action="cadastro.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="campo">
                <label for="nome">Nome Completo *</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
            </div>
            <div class="campo">
                <label for="usuario">Nome de Usuário *</label>
                <input type="text" id="usuario" name="usuario" value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" required>
            </div>
            <div class="campo">
                <label for="senha">Senha *</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <div class="campo">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="seu@email.com" required>
            </div>
            <div class="campo">
                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>" placeholder="(00) 00000-0000">
            </div>
            <div class="campo">
                <label for="endereco">Endereço</label>
                <input type="text" id="endereco" name="endereco" value="<?= htmlspecialchars($_POST['endereco'] ?? '') ?>" placeholder="Rua, Número, Cidade, Estado">
            </div>
            <button type="submit" class="btn btn-principal">Cadastrar</button>
        </form>

        <p class="link-rodape">
            Já tem uma conta? <a href="login.php">Faça login</a>
        </p>
    </div>
</body>
</html>