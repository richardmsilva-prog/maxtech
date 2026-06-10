<?php

$mensagem = "";
$tipo_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    include "conexao.php";

    $nome     = $mysqli->real_escape_string($_POST["nome"]      ?? "");
    $usuario  = $mysqli->real_escape_string($_POST["usuario"]   ?? "");
    $senha    = $mysqli->real_escape_string($_POST["senha"]     ?? "");
    $email    = $mysqli->real_escape_string($_POST["email"]     ?? "");
    $telefone = $mysqli->real_escape_string($_POST["telefone"]  ?? "");
    $endereco = $mysqli->real_escape_string($_POST["endereco"]  ?? "");

    if (empty($nome) || empty($usuario) || empty($senha) || empty($email)) {
        $mensagem = "Preencha todos os campos obrigatórios (Nome, Usuário, Senha e Email).";
        $tipo_msg = "erro";
    } else {
        $sql = "SELECT id FROM usuarios WHERE usuario = '$usuario' OR email = '$email'";
        $query = $mysqli->query($sql) or die ("Erro ao verificar usuário: " . $mysqli->error);

        if ($query->num_rows > 0) {
            $mensagem = "Já existe um usuário com esse nome de usuário ou e-mail. Tente outro.";
            $tipo_msg = "erro";
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO usuarios (nome, usuario, senha, email, telefone, endereco)
                    VALUES ('$nome', '$usuario', '$senha_hash', '$email', '$telefone', '$endereco')";
            
            if ($mysqli->query($sql)) {
                $mensagem = "Cadastro realizado com sucesso! Você já pode fazer login.";
                $tipo_msg = "sucesso";
            } else {
                $mensagem = "Erro ao cadastrar: " . $mysqli->error;
                $tipo_msg = "erro";
            }   
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
    <link rel="stylesheet" href="style.css">
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
            <div class="campo">
                <label for="nome">Nome Completo *</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="campo">
                <label for="usuario">Nome de Usuário *</label>
                <input type="text" id="usuario" name="usuario" required>
            </div>
            <div class="campo">
                <label for="senha">Senha *</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <div class="campo">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" placeholder="seu@email.com" required>
            </div>
            <div class="campo">
                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone" placeholder="(00) 00000-0000">
            </div>
            <div class="campo">
                <label for="endereco">Endereço</label>
                <input type="text" id="endereco" name="endereco" placeholder="Rua, Número, Cidade, Estado">
            </div>
            <button type="submit" class="btn btn-principal">Cadastrar</button>
        </form>

        <p class="link-rodape">
            Já tem uma conta? <a href="login.php">Faça login</a>
        </p>
    </div>
</body>
</html>