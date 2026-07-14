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
    <meta name="description" content="Compre <?= htmlspecialchars($produto["nome_produto"]) ?> na MaxTech. Garantia de qualidade, entrega e instalação.">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
</head>

<body class="pg-produto d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg main-header">
        <div class="container">
            <a class="header-logo d-flex align-items-center" href="index.php">
                <img src="../assets/img/Logo_MaxTech.jpg" alt="Logo da MaxTech Vendas, Entrega e Instalação" width="255" height="170">
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-label="Abrir menu de navegação">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end header-nav" id="navbarMenu">
                <div class="navbar-nav align-items-center gap-3">
                    
                    <?php if ($logado): ?>
                        <span class="text-claro-custom">
                            Olá, <strong class="text-primaria-custom"><?= htmlspecialchars($nomeUsuario) ?></strong>
                        </span>
                        <a href="logout.php" class="btn-custom-secondary btn-sm px-3 py-1 text-decoration-none">Sair</a>
                    <?php else: ?>
                        <a href="login.php" class="hover-primary">Fazer Login</a>
                        <a href="cadastro.php" class="btn-custom-primary px-4 py-2 text-decoration-none">Cadastrar-se</a>
                    <?php endif; ?>
                    
                    <div class="vr d-none d-lg-block mx-2 bg-borda-custom"></div>
                    
                    <a href="carrinho.php" class="btn-cart position-relative" aria-label="Acessar carrinho de compras">
                        <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                        <?php if ($qtdCarrinho > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.75em;">
                                <?= $qtdCarrinho ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container main-container flex-grow-1 mb-5">
        <?php 
            $caminhoImagem = !empty($produto['imagem']) ? $produto['imagem'] : "../assets/img/sem-imagem.png";
        ?>
        
        <div class="row produto-box bg-white p-4 p-md-5 align-items-center shadow-sm">
            
            <div class="col-12 col-md-6 mb-4 mb-md-0 text-center">
                <div class="img-wrapper p-3">
                    <img src="<?= htmlspecialchars($caminhoImagem) ?>" alt="Imagem detalhada do produto <?= htmlspecialchars($produto['nome_produto']) ?>" class="img-fluid border rounded" fetchpriority="high">
                </div>
            </div>

            <div class="col-12 col-md-6 ps-md-5">
                
                <h1 class="fw-bold mb-3 produto-titulo">
                    <?= htmlspecialchars($produto["nome_produto"]) ?>
                </h1>
                
                <h2 class="price-tag fw-bolder mb-4">
                    R$ <?= number_format($produto["preco"], 2, ",", ".") ?>
                </h2>
                
                <div class="mb-4">
                    <h3 class="h5 fw-bold mb-2 text-claro-custom">Descrição do Produto</h3>
                    <p class="produto-desc">
                        <?= nl2br(htmlspecialchars($produto['descricao'] ?? "Nenhuma descrição disponível para este produto.")) ?>
                    </p>
                </div>
                
                <hr class="my-4 border-borda-custom">
                
                <?php if ($produto['estoque'] <= 0): ?>
                    <div class="alert-custom-danger d-flex flex-column align-items-center text-center p-4 rounded-3 mb-3">
                        <i class="fa-solid fa-box-open fa-2x mb-2 opacity-75" aria-hidden="true"></i>
                        <h3 class="h5 fw-bold mb-1">Produto Esgotado</h3>
                        <p class="mb-0 small">Este item está temporariamente indisponível no momento.</p>
                    </div>
                    <button class="btn-custom-secondary w-100 py-3 fw-bold btn-esgotado-lg" disabled aria-disabled="true">
                        <i class="fa-solid fa-ban me-2" aria-hidden="true"></i> Indisponível
                    </button>

                <?php else: ?>
                    <p class="fw-semibold mb-3 text-vibrante-custom">
                        <i class="fa-solid fa-check-circle me-1" aria-hidden="true"></i> Em estoque (<?= $produto['estoque'] ?> disponíveis)
                    </p>
                    
                    <form action="carrinho.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION["csrf_token"] ?>">
                        <input type="hidden" name="acao" value="adicionar">
                        <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                        
                        <div class="d-flex align-items-center gap-3 mb-3">
                            
                            <div class="d-flex align-items-center">
                                <label for="qtd" class="me-2 fw-semibold text-claro-custom">Qtd:</label>
                                <input type="number" id="qtd" name="quantidade" class="form-control input-qtd fw-bold" value="1" min="1" max="<?= $produto["estoque"] ?>">
                            </div>
                            
                            <button type="submit" class="btn-custom-primary flex-grow-1 py-3 fw-bold fs-5 d-flex justify-content-center align-items-center btn-add-carrinho">
                                <i class="fa-solid fa-cart-plus me-2" aria-hidden="true"></i> Adicionar ao Carrinho
                            </button>
                        </div>
                    </form>
                    
                    <div class="small mt-3 text-claro-custom">
                        <i class="fa-solid fa-shield-halved me-1 text-primaria-custom" aria-hidden="true"></i> Compra segura garantida pela MaxTech
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main> 

    <footer class="footer text-center text-secondary">
        <div class="container">
            <p class="mb-0 text-claro-custom">&copy; <?= date("Y") ?> <strong class="text-secundaria-custom">MaxTech</strong>. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>