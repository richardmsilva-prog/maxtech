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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .produto-imagem-wrapper {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            background-color: #fff;
        }
        .produto-imagem-wrapper img {
            width: 100%;
            height: auto;
            max-height: 450px;
            object-fit: contain; 
            transition: transform 0.3s ease;
        }
        .produto-imagem-wrapper:hover img {
            transform: scale(1.05); 
        }
        .bg-topbar {
            background-color: #2c3e50;
        }
    </style>
</head>

<body class="pagina-produto d-flex flex-column min-vh-100" style="background-color: #f8f9fa;">

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

    <main class="container produto-conteudo flex-grow-1 py-5">
        <?php 
            $caminhoImagem = !empty($produto['imagem']) ? $produto['imagem'] : "../assets/img/sem-imagem.png";
        ?>
        
        <div class="row bg-white p-4 p-md-5 rounded-4 shadow-sm align-items-center">
            
            <div class="col-12 col-md-6 mb-4 mb-md-0 text-center">
                <div class="produto-imagem-wrapper p-3 border">
                    <img src="<?= htmlspecialchars($caminhoImagem) ?>" alt="Imagem de <?= htmlspecialchars($produto['nome_produto']) ?>" class="img-fluid">
                </div>
            </div>

            <div class="col-12 col-md-6 ps-md-5 produto-info">
                
                <h1 class="fw-bold mb-3" style="color: #212529; font-size: 2.2rem;">
                    <?= htmlspecialchars($produto["nome_produto"]) ?>
                </h1>
                
                <h2 class="text-success fw-bolder mb-4" style="font-size: 2.5rem; letter-spacing: -1px;">
                    R$ <?= number_format($produto["preco"], 2, ",", ".") ?>
                </h2>
                
                <div class="mb-4">
                    <h5 class="fw-bold text-secondary mb-2">Descrição do Produto</h5>
                    <p class="descricao text-muted" style="line-height: 1.7; font-size: 1.05rem;">
                        <?= nl2br(htmlspecialchars($produto['descricao'] ?? "Nenhuma descrição disponível para este produto.")) ?>
                    </p>
                </div>
                
                <hr class="my-4">
                
                <?php if ($produto['estoque'] <= 0): ?>
                    <div class="alert alert-secondary border-0 text-center p-4 rounded-3" style="background-color: #f8d7da; color: #842029;">
                        <i class="fa-solid fa-box-open fa-2x mb-2 opacity-50"></i>
                        <h5 class="fw-bold mb-1">Produto Esgotado</h5>
                        <p class="mb-0 small">Este item está temporariamente indisponível no momento.</p>
                    </div>
                    <button class="btn btn-secondary w-100 py-3 fw-bold rounded-3" disabled style="cursor: not-allowed; opacity: 0.6;">
                        <i class="fa-solid fa-ban me-2"></i> Indisponível
                    </button>

                <?php else: ?>
                    <p class="text-success fw-semibold mb-3">
                        <i class="fa-solid fa-check-circle me-1"></i> Em estoque (<?= $produto['estoque'] ?> disponíveis)
                    </p>
                    
                    <form action="carrinho.php" method="POST" class="form-comprar">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION["csrf_token"] ?>">
                        <input type="hidden" name="acao" value="adicionar">
                        <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                        
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="input-group" style="width: 140px;">
                                <span class="input-group-text bg-light text-secondary border-end-0">Qtd</span>
                                <input type="number" id="qtd" name="quantidade" class="form-control border-start-0 ps-0 fw-bold text-center" value="1" min="1" max="<?= $produto["estoque"] ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-principal flex-grow-1 py-3 fw-bold rounded-3 fs-5 shadow-sm transition-all" style="letter-spacing: 0.5px;">
                                <i class="fa-solid fa-cart-plus me-2"></i> Adicionar ao Carrinho
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-muted small mt-3">
                        <i class="fa-solid fa-shield-halved text-primary me-1"></i> Compra segura garantida pela MaxTech
                    </div>
                <?php endif; ?>

            </div>
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