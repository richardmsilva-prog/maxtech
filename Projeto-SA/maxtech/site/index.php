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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .bg-topbar {
            background-color: #2c3e50;
        }
        .produto-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 12px;
        }
        .produto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        }
        .descricao-truncada {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>

<body class="pagina-painel d-flex flex-column min-vh-100" style="background-color: #f4f6f9;">

    <nav class="navbar navbar-expand-lg navbar-dark bg-topbar topbar py-3 shadow-sm" style="border-bottom: 4px solid #0d6efd;">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="index.php">
                <i class="fa-solid fa-microchip text-primary me-2"></i>MaxTech
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarMenu">
                <div class="navbar-nav align-items-center gap-3 topbar-usuario">
                    
                    <?php if ($logado): ?>
                        <span class="text-light">
                            Olá, <strong style="color: #f1c40f;"><?= htmlspecialchars($nomeUsuario) ?></strong>
                        </span>
                        <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Sair</a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link text-white fw-semibold">Fazer Login</a>
                        <a href="cadastro.php" class="btn btn-primary rounded-pill px-4 fw-semibold">Cadastrar-se</a>
                    <?php endif; ?>
                    
                    <div class="vr bg-light d-none d-lg-block mx-2"></div>
                    
                    <a href="carrinho.php" class="btn btn-light rounded-pill position-relative px-4 fw-bold text-dark">
                        <i class="fa-solid fa-cart-shopping meu-carrinho text-primary me-1"></i> Carrinho
                        <?php if ($qtdCarrinho > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.85em;">
                                <?= $qtdCarrinho ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container painel-conteudo flex-grow-1" style="padding-top: 40px; padding-bottom: 50px;">
        
        <div class="text-center mb-5">
            <h1 class="fw-bold" style="color: #2c3e50;">Catálogo de Produtos e Serviços</h1>
            <p class="text-muted fs-5">Confira as melhores soluções em tecnologia da MaxTech.</p>
        </div>

        <div class="row g-4">
            
            <?php if ($query->num_rows > 0): ?>
                <?php while ($produto = $query->fetch_assoc()): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 produto-card shadow-sm">
                            
                            <?php $caminhoImagem = !empty($produto['imagem']) ? $produto['imagem'] : "../assets/img/sem-imagem.png"; ?>
                            <div class="text-center p-3" style="background-color: #fff; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                                <img src="<?= htmlspecialchars($caminhoImagem) ?>" alt="<?= htmlspecialchars($produto['nome_produto']) ?>" style="height: 200px; object-fit: contain; width: 100%;">
                            </div>
                            
                            <div class="card-body d-flex flex-column border-top">
                                <h5 class="card-title fw-bold text-dark mb-1"><?= htmlspecialchars($produto['nome_produto']) ?></h5>
                                <p class="card-text descricao-truncada text-muted small mb-3" style="min-height: 40px;">
                                    <?= htmlspecialchars($produto['descricao']) ?>
                                </p>
                                
                                <h3 class="text-success fw-bold mt-auto mb-3">
                                    R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                                </h3>
                                
                                <div class="d-grid gap-2">
                                    <?php if ($produto['estoque'] <= 0): ?>
                                        <button class="btn btn-secondary fw-bold" disabled style="opacity: 0.7;">
                                            <i class="fa-solid fa-ban me-1"></i> Esgotado
                                        </button>
                                    <?php else: ?>
                                        <form action="carrinho.php" method="POST" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="acao" value="adicionar">
                                            <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                                            <input type="hidden" name="quantidade" value="1">
                                            <button type="submit" class="btn btn-primary btn-principal w-100 fw-bold">
                                                <i class="fa-solid fa-cart-plus me-1"></i> Adicionar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <a href="produto.php?id_produto=<?= $produto['id_produto'] ?>" class="btn btn-outline-dark fw-semibold">
                                        <i class="fa-solid fa-eye me-1"></i> Ver Detalhes
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-secondary text-center p-5 rounded-4 border-0 shadow-sm">
                        <i class="fa-solid fa-box-open fa-3x text-muted mb-3"></i>
                        <h4 class="fw-bold">Nenhum produto cadastrado no momento.</h4>
                        <p class="text-muted">Volte novamente mais tarde para conferir nossas novidades!</p>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <footer class="rodape mt-auto py-4 bg-white border-top text-center text-secondary">
        <div class="container">
            <p class="mb-0">&copy; <?= date("Y") ?> <strong class="text-dark">MaxTech</strong>. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>