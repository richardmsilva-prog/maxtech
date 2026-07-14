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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Falha de segurança: Token CSRF inválido. Retorne à loja e tente novamente.");
    }

    $acao = $_POST["acao"] ?? "adicionar"; 
    $idProduto = (int) ($_POST["id_produto"] ?? 0);
    $quantidade = (int) ($_POST["quantidade"] ?? 1);

    if ($idProduto > 0) {
        if ($acao === "adicionar") {
            try {
                $stmt = $mysqli->prepare("SELECT estoque FROM produtos WHERE id_produto = ?");
                $stmt->bind_param("i", $idProduto);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if ($row = $res->fetch_assoc()) {
                    $estoqueAtual = (int) $row['estoque'];

                    if (!isset($_SESSION["carrinho"][$idProduto])) {
                        if ($quantidade > $estoqueAtual) {
                            $_SESSION["carrinho"][$idProduto] = $estoqueAtual;
                            $_SESSION["mensagem"] = "Atenção: Apenas $estoqueAtual unidade(s) disponível(is) em estoque.";
                        } else {
                            $_SESSION["carrinho"][$idProduto] = $quantidade;
                        }
                    } else {
                        $novaQuantidade = $_SESSION["carrinho"][$idProduto] + $quantidade;
                        
                        if ($novaQuantidade > $estoqueAtual) {
                            $_SESSION["carrinho"][$idProduto] = $estoqueAtual;
                            $_SESSION["mensagem"] = "Atenção: Limite máximo de $estoqueAtual unidade(s) atingido.";
                        } else {
                            $_SESSION["carrinho"][$idProduto] += $quantidade;
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Erro ao checar estoque: " . $e->getMessage());
                $_SESSION["mensagem"] = "Ocorreu um erro interno. Tente novamente.";
            } finally {
                if (isset($stmt)) $stmt->close();
            }

        } elseif ($acao === "diminuir") {
            $_SESSION["carrinho"][$idProduto] -= $quantidade;
            if ($_SESSION["carrinho"][$idProduto] <= 0) {
                unset($_SESSION["carrinho"][$idProduto]);
            }
        } elseif ($acao === "remover") {
            unset($_SESSION["carrinho"][$idProduto]);
        }
    }

    header("Location: carrinho.php");
    exit;
}

$carrinhoVazio = empty($_SESSION["carrinho"]);
$produtosCarrinho = []; 
$totalCarrinho = 0;
$qtdCarrinho = 0;

if (!$carrinhoVazio) {
    $qtdCarrinho = array_sum($_SESSION['carrinho']);
    $idsProdutos = array_keys($_SESSION["carrinho"]);
    $idsEncontradosNoBanco = []; 
 
    try {
        $placeholders = implode(",", array_fill(0, count($idsProdutos), "?"));
        
        $sql = "SELECT id_produto, nome_produto, preco, estoque, imagem FROM produtos WHERE id_produto IN ($placeholders)";
        $stmt = $mysqli->prepare($sql);
        
        $tipos = str_repeat("i", count($idsProdutos)); 
        $stmt->bind_param($tipos, ...$idsProdutos);
        $stmt->execute();
        
        $res = $stmt->get_result();
        
        while ($produto = $res->fetch_assoc()) {
            $id = $produto['id_produto'];
            $idsEncontradosNoBanco[] = $id; 
            $quantidade = $_SESSION["carrinho"][$id];
            
            if ($quantidade > $produto['estoque']) {
                $quantidade = $produto['estoque'];
                $_SESSION["carrinho"][$id] = $quantidade;
                $_SESSION["mensagem"] = "O estoque de " . $produto['nome_produto'] . " foi atualizado.";
            }
            
            $subtotal = $produto['preco'] * $quantidade;
            
            $produto['quantidade'] = $quantidade;
            $produto['subtotal'] = $subtotal;
            
            $produtosCarrinho[] = $produto;
            $totalCarrinho += $subtotal;
        }
        
        foreach ($idsProdutos as $idSessao) {
            if (!in_array($idSessao, $idsEncontradosNoBanco)) {
                unset($_SESSION["carrinho"][$idSessao]);
            }
        }

    } catch (Exception $e) {
        error_log("Erro ao carregar o carrinho: " . $e->getMessage());
        $_SESSION["mensagem"] = "Ocorreu um erro ao carregar alguns produtos.";
    } finally {
        if (isset($stmt)) $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Carrinho - MaxTech</title>
    <meta name="description" content="Gerencie os itens do seu carrinho de compras na MaxTech. Adicione, remova ou ajuste quantidades antes de finalizar sua compra.">
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="pg-carrinho d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg main-header">
        <div class="container">
            <a class="header-logo d-flex align-items-center" href="index.php">
                <img src="../assets/img/Logo_MaxTech.jpg" alt="MaxTech Vendas, Entrega e Instalação">
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" alt="Menu de Navegação" aria-controls="navbarMenu" aria-expanded="false" aria-label="Alternar navegação">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end header-nav" id="navbarMenu">
                <div class="navbar-nav align-items-center gap-3">
                    
                    <?php if ($logado): ?>
                        <span style="color: var(--cor-texto-claro);">
                            Olá, <strong style="color: var(--cor-primaria);"><?= htmlspecialchars($nomeUsuario) ?></strong>
                        </span>
                        <a href="logout.php" class="btn-custom-secondary btn-sm px-3 py-1 text-decoration-none">Sair</a>
                    <?php else: ?>
                        <a href="login.php" class="hover-primary">Fazer Login</a>
                        <a href="cadastro.php" class="btn-custom-primary px-4 py-2 text-decoration-none">Cadastrar-se</a>
                    <?php endif; ?>
                    
                    <div class="vr d-none d-lg-block mx-2" style="background-color: var(--cor-borda);"></div>
                    
                    <a href="index.php" class="btn-custom-secondary px-3 py-2 text-decoration-none d-flex align-items-center">
                        <i class="fa-solid fa-store me-1"></i> Voltar à Loja
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container main-container flex-grow-1 mb-5">
        
        <div class="text-center mb-5">
            <h1 class="fw-bold" style="color: var(--cor-secundaria);">
                <i class="fa-solid fa-cart-shopping me-2" style="color: var(--cor-primaria);"></i>Meu Carrinho
            </h1>
            <p style="color: var(--cor-texto-claro);">Gerencie os itens selecionados antes de fechar o seu pedido.</p>
        </div>

        <?php if (isset($_SESSION["mensagem"])): ?>
            <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                <?= htmlspecialchars($_SESSION["mensagem"]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION["mensagem"]); ?>
        <?php endif; ?>

        <?php if ($carrinhoVazio || empty($produtosCarrinho)): ?>
            
            <div class="border rounded-4 p-5 text-center bg-white shadow-sm">
                <div class="py-4">
                    <i class="fa-solid fa-box-open mb-4 p-4 bg-light rounded-circle text-muted" style="font-size: 4rem;"></i>
                    <h2 class="fw-bold mb-2" style="color: var(--cor-secundaria);">Seu carrinho está vazio!</h2>
                    <p class="mb-4 fs-5" style="color: var(--cor-texto-claro);">Que tal dar uma olhada em nossos ótimos produtos e serviços?</p>
                    <a href="index.php" class="btn-custom-primary btn-lg px-4 py-3 fw-bold text-decoration-none d-inline-block">
                        <i class="fa-solid fa-store me-2"></i>Ir para a Vitrine
                    </a>
                </div>
            </div>

        <?php else: ?>

            <div class="row g-4">
                
                <div class="col-12 col-xl-8">
                    <div class="border rounded-4 bg-white overflow-hidden shadow-sm">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="py-3 ps-4">Produto</th>
                                        <th class="py-3">Preço</th>
                                        <th class="py-3 text-center" style="width: 140px;">Qtd</th>
                                        <th class="py-3">Subtotal</th>
                                        <th class="py-3 text-center pe-4" style="width: 80px;">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($produtosCarrinho as $item): ?>
                                        <tr>
                                            <td class="py-3 ps-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <?php $img = !empty($item['imagem']) ? $item['imagem'] : "../assets/img/sem-imagem.png"; ?>
                                                    <img src="<?= htmlspecialchars($img) ?>" alt="Imagem do Produto" class="cart-img-thumb shadow-sm">
                                                    <div>
                                                        <span class="d-block fw-bold fs-6" style="color: var(--cor-secundaria);"><?= htmlspecialchars($item['nome_produto']) ?></span>
                                                        <span class="badge bg-white text-muted border small mt-1">Cód: #<?= $item['id_produto'] ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <td class="py-3 price-text fw-semibold">
                                                R$ <?= number_format($item['preco'], 2, ',', '.') ?>
                                            </td>
                                            
                                            <td class="py-3">
                                                <div class="d-flex justify-content-center align-items-center gap-1 cart-qty-controls">
                                                    <form action="carrinho.php" method="POST" class="m-0">
                                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                        <input type="hidden" name="acao" value="diminuir">
                                                        <input type="hidden" name="id_produto" value="<?= $item['id_produto'] ?>">
                                                        <button type="submit" class="qty-btn rounded-2 fw-bold">-</button>
                                                    </form>
                                                    
                                                    <input type="text" value="<?= $item['quantidade'] ?>" readonly class="form-control form-control-sm text-center fw-bold bg-light border-0 rounded-2 text-dark" style="width: 45px;">
                                                    
                                                    <form action="carrinho.php" method="POST" class="m-0">
                                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                        <input type="hidden" name="acao" value="adicionar">
                                                        <input type="hidden" name="id_produto" value="<?= $item['id_produto'] ?>">
                                                        
                                                        <?php if ($item['quantidade'] >= $item['estoque']): ?>
                                                            <button type="button" disabled class="qty-btn rounded-2 text-muted" style="opacity: 0.5;" title="Estoque máximo atingido">
                                                                <i class="fa-solid fa-ban" style="font-size: 0.7rem;"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="submit" class="qty-btn rounded-2 fw-bold">+</button>
                                                        <?php endif; ?>
                                                    </form>
                                                </div>
                                            </td>

                                            <td class="py-3 price-text fw-bold fs-6">
                                                R$ <?= number_format($item['subtotal'], 2, ',', '.') ?>
                                            </td>
                                            
                                            <td class="py-3 text-center pe-4">
                                                <form action="carrinho.php" method="POST" class="m-0">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="acao" value="remover">
                                                    <input type="hidden" name="id_produto" value="<?= $item['id_produto'] ?>">
                                                    <button type="submit" class="btn btn-link delete-btn p-0 border-0 fs-5 lh-1 text-decoration-none shadow-none" title="Remover item">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="border rounded-4 bg-white p-4 shadow-sm">
                        <h4 class="fw-bold mb-3 pb-2 border-bottom" style="color: var(--cor-secundaria);">Resumo da Compra</h4>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span style="color: var(--cor-texto-claro);">Total de Itens:</span>
                            <span class="fw-semibold fs-5" style="color: var(--cor-secundaria);"><?= $qtdCarrinho ?> un.</span>
                        </div>
                        
                        <hr class="my-3" style="border-color: var(--cor-clara);">
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-bold fs-7" style="color: var(--cor-secundaria); text-transform: uppercase; letter-spacing: 0.5px;">Valor Total:</span>
                            <strong class="price-text fs-3 fw-bolder">
                                R$ <?= number_format($totalCarrinho, 2, ',', '.') ?>
                            </strong>
                        </div>
                        
                        <a href="checkout.php" class="btn-custom-primary btn-lg w-100 fw-bold py-3 text-center text-decoration-none d-block">
                            <i class="fa-solid fa-credit-card me-2"></i>Ir para o Pagamento
                        </a>
                    </div>
                </div>

            </div>

        <?php endif; ?>

    </main>

    <footer class="footer text-center text-secondary">
        <div class="container">
            <p class="mb-0" style="color: var(--cor-texto-claro);">&copy; <?= date("Y") ?> <strong style="color: var(--cor-secundaria);">MaxTech</strong>. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>