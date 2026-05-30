<?php
// cadastro.php - cadastro de usuários (back-end + front-end)

$mensagem = "";
$tipo_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'conexao.php';

    $nome    = $mysqli->real_escape_string($_POST['nome']    ?? '');
    $usuario = $mysqli->real_escape_string($_POST['usuario'] ?? '');
    $senha   = $mysqli->real_escape_string($_POST['senha']   ?? '');

    if (empty($nome) || empty($usuario) || empty($senha)) {
        $mensagem = "Preencha todos os campos para realizar o cadastro.";
        $tipo_msg = "erro";
    } else {
        $sql_verifica = "SELECT id FROM usuarios WHERE usuario = '$usuario'";
        $query_verifica = $mysqli->query($sql_verifica) or die("Erro SQL: " . $mysqli->error);

        if ($query_verifica->num_rows > 0) {
            $mensagem = "Já existe um usuário com esse login. Escolha outro.";
            $tipo_msg = "erro";
        } else {
            $sql_insert = "INSERT INTO usuarios (nome, usuario, senha)
                           VALUES ('$nome', '$usuario', '$senha')";

            if ($mysqli->query($sql_insert)) {
                $mensagem = "Usuário cadastrado com sucesso! Você já pode fazer login.";
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
    <title>Cadastro - Sistema de Estoque</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-cadastro">
    <div class="container form-container">
        <h1>Cadastro de Usuário</h1>
        <p class="subtitulo">Sistema de Estoque</p>

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem <?= htmlspecialchars($tipo_msg) ?>">
                <?= htmlspecialchars($mensagem) ?>
            </p>
        <?php endif; ?>

        <form action="cadastro.php" method="POST">
            <div class="campo">
                <label for="nome">Nome completo</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="campo">
                <label for="usuario">Usuário (login)</label>
                <input type="text" id="usuario" name="usuario" required>
            </div>
            <div class="campo">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <button type="submit" class="btn btn-principal">Cadastrar</button>
        </form>

        <p class="link-rodape">
            Já tem cadastro?
            <a href="index.php">Voltar para o login</a>
        </p>
    </div>
</body>
</html>