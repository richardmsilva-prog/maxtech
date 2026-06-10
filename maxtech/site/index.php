<?php

if (!isset($_SESSION)) {
    session_start();
}

include 'conexao.php';

$logado      = isset($_SESSION['id']);
$nomeUsuario = $_SESSION['nome'] ?? 'Visitante';

$mysqli->query("CREATE TABLE IF NOT EXISTS produtos (
    id_produto INT AUTO_INCREMENT PRIMARY KEY,
    nome_produto VARCHAR(150) NOT NULL,
    preco DECIMAL(10,2) NOT NULL
)");

$sql = "SELECT * FROM produtos ORDER BY nome_produto ASC";
$query = $mysqli->query($sql) or die("Erro ao buscar produtos: " . $mysqli->error);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaxTech - Nossos Produtos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-painel">

    <header class="topbar">
        <span class="topbar-titulo">MaxTech - Installation & Products</span>
        <div class="topbar-usuario">
            <?php if ($logado): ?>
                Olá, <strong><?= htmlspecialchars($nomeUsuario) ?></strong> &nbsp;|&nbsp; 
                <a href="logout.php">Sair</a>
            <?php else: ?>
                <a href="login.php" style="color: #fff; font-weight: bold; text-decoration: none;">Fazer Login</a> &nbsp;|&nbsp;
                <a href="cadastro.php" style="color: #fff; text-decoration: none;">Cadastrar-se</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="container painel-conteudo" style="padding-top: 30px;">
        
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 style="color: #2c3e50;">Catálogo de Produtos e Serviços</h1>
            <p style="color: #7f8c8d;">Confira as melhores soluções em tecnologia da MaxTech.</p>
        </div>

        <section class="card-tabela">
            <table class="tabela-produtos">
                <thead>
                    <tr>
                        <th>Produto / Serviço</th>
                        <th>Descrição</th>
                        <th>Preço</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($query->num_rows > 0): 
                        while ($produto = $query->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($produto['nome']) ?></strong></td>
                            <td><?= htmlspecialchars($produto['descricao']) ?></td>
                            <td>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                            <td><button class="btn btn-principal">Comprar</button></td>
                        </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #7f8c8d; padding: 20px;">
                                Nenhum produto cadastrado no momento. 
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </main>
</body>
</html>