<?php
if (!isset($_SESSION)) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once "../systems/core/conexao.php";

$logado      = isset($_SESSION['id']);
$nomeUsuario = $_SESSION['nome'] ?? 'Visitante';

$qtdCarrinho = 0;
if (isset($_SESSION['carrinho'])) {
    $qtdCarrinho = array_sum($_SESSION['carrinho']);
}

try {
    $sql = "SELECT id_produto, nome_produto, descricao, preco, estoque, imagem FROM produtos ORDER BY nome_produto ASC";
    $query = $mysqli->query($sql);
    
    if (!$query) {
        throw new Exception($mysqli->error);
    }
} catch (Exception $e) {
    error_log("Erro no catálogo: " . $e->getMessage());
    die("Ocorreu um problema interno ao carregar os produtos. Por favor, tente novamente mais tarde."); 
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaxTech - Nossos Produtos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

            &nbsp;|&nbsp;
            <a href="carrinho.php" style="color: #fff; text-decoration: none;">
                <i class="fa-solid fa-cart-shopping meu-carrinho"></i> Carrinho
                <?php if ($qtdCarrinho > 0): ?>
                    <span style="background-color: #e74c3c; color: white; border-radius: 50%; padding: 2px 7px; font-size: 0.8em; margin-left: 5px;">
                        <?= $qtdCarrinho ?>
                    </span>
                <?php endif; ?>
            </a>
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
                        <th>Imagem</th> 
                        <th>Produto / Serviço</th>
                        <th>Descrição</th>
                        <th>Preço</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query->num_rows > 0): ?>
                        <?php while ($produto = $query->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <?php 
                                        $caminhoImagem = !empty($produto['imagem']) ? $produto['imagem'] : "../assets/img/sem-imagem.png";
                                    ?>
                                    <img src="<?= htmlspecialchars($caminhoImagem) ?>" alt="Imagem de <?= htmlspecialchars($produto['nome_produto']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                                </td>
                                
                                <td><strong><?= htmlspecialchars($produto['nome_produto']) ?></strong></td>
                                <td><?= htmlspecialchars($produto['descricao']) ?></td>
                                <td>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                                
                                <td style="display: flex; gap: 10px; align-items: center;">
                                    <?php if ($produto['estoque'] <= 0): ?>
                                        <button class="btn btn-secundario" disabled style="cursor: not-allowed; opacity: 0.6; background-color: #95a5a6; color: white; border: none; padding: 10px;">
                                            ESGOTADO
                                        </button>
                                    <?php else: ?>
                                        <form action="carrinho.php" method="POST" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="acao" value="adicionar">
                                            <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                                            <input type="hidden" name="quantidade" value="1">
                                            <button type="submit" class="btn btn-principal">Adicionar ao Carrinho</button>
                                        </form>
                                    <?php endif; ?>

                                    <a href="produto.php?id_produto=<?= $produto['id_produto'] ?>" class="btn btn-principal">Ver Detalhes</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #7f8c8d; padding: 20px;">
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