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
    <meta name="description" content="Catálogo de produtos e serviços MaxTech. Confira as melhores soluções em tecnologia, entrega e instalação.">
    <title>MaxTech - Nossos Produtos</title>
    
    <link rel="preload" as="image" href="../assets/img/Logo_MaxTech.jpg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
</head>

<body class="pg-painel d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg main-header">
        <div class="container">
            <a class="header-logo d-flex align-items-center" href="index.php">
                <img src="../assets/img/Logo_MaxTech.jpg" alt="Logo da MaxTech Vendas, Entrega e Instalação" width="255" height="170" fetchpriority="high">
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
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger badge-cart">
                                <?= $qtdCarrinho ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container main-container flex-grow-1 mb-5">
        
        <div class="text-center mb-5">
            <h1 class="fw-bold text-secundaria-custom">Catálogo de Produtos e Serviços</h1>
            <p class="fs-5 text-claro-custom">Confira as melhores soluções em tecnologia da MaxTech.</p>
        </div>

        <div class="row g-4">
            
            <?php if ($query->num_rows > 0): ?>
                <?php while ($produto = $query->fetch_assoc()): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 border rounded-4 bg-white shadow-sm overflow-hidden">
                            
                            <?php $caminhoImagem = !empty($produto['imagem']) ? $produto['imagem'] : "../assets/img/sem-imagem.png"; ?>
                            <div class="text-center p-3 bg-white">
                                <img src="<?= htmlspecialchars($caminhoImagem) ?>" alt="<?= htmlspecialchars($produto['nome_produto']) ?>" loading="lazy" width="200" height="200" class="img-produto-card">
                            </div>
                            
                            <div class="card-body d-flex flex-column border-top border-clara-custom">
                                <h2 class="card-title h5 fw-bold mb-1 text-secundaria-custom"><?= htmlspecialchars($produto['nome_produto']) ?></h2>
                                <p class="card-text text-muted small mb-3 text-claro-custom desc-produto-card">
                                    <?= htmlspecialchars($produto['descricao']) ?>
                                </p>
                                
                                <p class="price-text fs-3 fw-bold mt-auto mb-3">
                                    R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                                </p>
                                
                                <div class="d-grid gap-2">
                                    <?php if ($produto['estoque'] <= 0): ?>
                                        <button class="btn-custom-secondary w-100 fw-bold btn-esgotado" disabled>
                                            <i class="fa-solid fa-ban me-1" aria-hidden="true"></i> Esgotado
                                        </button>
                                    <?php else: ?>
                                        <form action="carrinho.php" method="POST" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="acao" value="adicionar">
                                            <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                                            <input type="hidden" name="quantidade" value="1">
                                            <button type="submit" class="btn-custom-primary w-100 fw-bold d-flex justify-content-center align-items-center">
                                                <i class="fa-solid fa-cart-plus me-1" aria-hidden="true"></i> Adicionar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <a href="produto.php?id_produto=<?= $produto['id_produto'] ?>" class="btn-custom-secondary w-100 fw-semibold text-center text-decoration-none d-flex justify-content-center align-items-center">
                                        <i class="fa-solid fa-eye me-1" aria-hidden="true"></i> Ver Detalhes
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert-custom-danger text-center p-5 rounded-4 d-flex flex-column align-items-center">
                        <i class="fa-solid fa-box-open fa-3x mb-3 opacity-75" aria-hidden="true"></i>
                        <h2 class="h4 fw-bold">Nenhum produto cadastrado no momento.</h2>
                        <p class="mb-0 small">Volte novamente mais tarde para conferir nossas novidades!</p>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>