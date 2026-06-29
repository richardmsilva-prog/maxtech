<?php 
if (!isset($_SESSION)) {
    session_start();
}

require_once "../systems/core/conexao.php";

$logado      = isset($_SESSION['id']);
$nomeUsuario = $_SESSION['nome'] ?? 'Visitante';

$qtdCarrinho = 0;
if (isset($_SESSION['carrinho'])) {
    $qtdCarrinho = array_sum($_SESSION['carrinho']);
}

if (!isset($_GET["id_produto"])) {
    header("Location: index.php");
    exit;
}

$idProduto = (int) $_GET["id_produto"];

try {
    $stmt = $mysqli->prepare("SELECT id_produto, nome_produto, descricao, preco, estoque, imagem FROM produtos WHERE id_produto = ?");
    $stmt->bind_param("i", $idProduto);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        header("Location: index.php");
        exit;
    }
    $produto = $res->fetch_assoc();
} catch (Exception $e) {
    error_log("Erro ao buscar produto: " . $e->getMessage());
    header("Location: index.php?erro=falha_servidor");
    exit;
} finally {
    if (isset($stmt)) $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($produto["nome_produto"]) ?> - MaxTech</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="pagina-produto">
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

    <main class="container produto-conteudo" style="padding-top: 40px; padding-bottom: 40px;">
        <?php 
            $caminhoImagem = !empty($produto['imagem']) ? $produto['imagem'] : "../assets/img/sem-imagem.png";
        ?>
        
        <div class="produto-detalhes" style="display: flex; gap: 30px; align-items: flex-start; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            
            <div class="produto-imagem">
                <img src="<?= htmlspecialchars($caminhoImagem) ?>" alt="Imagem de <?= htmlspecialchars($produto['nome_produto']) ?>" style="width: 300px; height: 300px; object-fit: cover; border-radius: 8px; border: 1px solid #eee;">
            </div>

            <div class="produto-info">
                <h1 style="color: #2c3e50; margin-top: 0;"><?= htmlspecialchars($produto["nome_produto"]) ?></h1>
                <p class="preco" style="font-size: 1.5em; color: #27ae60; font-weight: bold;">
                    R$ <?= number_format($produto["preco"], 2, ",", ".") ?>
                </p>
                <p class="descricao" style="color: #7f8c8d; line-height: 1.6; margin-bottom: 20px;">
                    <?= htmlspecialchars($produto['descricao'] ?? "Nenhuma descrição disponível para este produto.") ?>
                </p>
                
                <?php if ($produto['estoque'] <= 0): ?>
                    <button class="btn btn-secundario" disabled style="cursor: not-allowed; opacity: 0.6; background-color: #95a5a6; color: white; border: none; padding: 12px 20px; font-size: 1.1em; border-radius: 5px;">
                        <i class="fa-solid fa-ban"></i> ESGOTADO
                    </button>
                    <p style="color: #e74c3c; font-size: 0.9em; margin-top: 10px;">Este item está temporariamente indisponível.</p>
                <?php else: ?>
                    <form action="carrinho.php" method="POST" class="form-comprar">
                        <label for="qtd" style="font-size: 0.9em; color: #7f8c8d;">Quantidade:</label>
                        <input type="number" id="qtd" name="quantidade" value="1" min="1" max="<?= $produto["estoque"] ?>" style"= width: 60px; padding: 5px; margin-right: 10px; margin-bottom: 15px;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION["csrf_token"] ?>">
                        <input type="hidden" name="acao" value="adicionar">
                        <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                        
                        <button type="submit" class="btn btn-principal" style="padding: 12px 20px; font-size: 1.1em; border-radius: 5px; cursor: pointer;">
                            <i class="fa-solid fa-cart-plus"></i> Comprar Agora
                        </button>
                    </form>
                    <p style="color: #27ae60; font-size: 0.9em; margin-top: 10px;"><i class="fa-solid fa-check-circle"></i> Em estoque (<?= $produto['estoque'] ?> disponíveis)</p>
                <?php endif; ?>
            </div>
        </div>
    </main> 

    <footer class="rodape" style="text-align: center; padding: 20px; background-color: #f8f9fa; color: #7f8c8d; margin-top: auto;">
        <p>&copy; <?= date("Y") ?> MaxTech. Todos os direitos reservados.</p>
    </footer>
</body>
</html>