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
    
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .bg-topbar {
            background-color: #2c3e50;
        }
        .cart-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
        }
        .item-img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 8px;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }
        .qty-input {
            width: 45px;
            text-align: center;
            font-weight: 600;
        }
        .btn-action-qty {
            padding: 0.25rem 0.6rem;
        }
        .btn-checkout {
            transition: all 0.3s ease;
        }
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }
    </style>
</head>
<body class="pagina-carrinho d-flex flex-column min-vh-100" style="background-color: #f8f9fa;">

    <nav class="navbar navbar-dark bg-topbar topbar py-3 shadow-sm">
        <div class="container flex-column flex-sm-row justify-content-between gap-2">
            <a class="navbar-brand fw-bold fs-4 m-0" href="index.php">
                <i class="fa-solid fa-microchip text-primary me-2"></i>MaxTech
            </a>
            <div class="topbar-usuario text-white small">
                <?php if ($logado): ?>
                    <i class="fa-solid fa-user text-muted me-1"></i> Olá, <strong class="text-light"><?= htmlspecialchars($nomeUsuario) ?></strong>
                    <span class="mx-2 text-muted">|</span>
                    <a href="logout.php" class="text-danger text-decoration-none fw-semibold"><i class="fa-solid fa-right-from-bracket me-1"></i>Sair</a>
                <?php else: ?>
                    <a href="login.php" class="text-white text-decoration-none fw-bold me-2"><i class="fa-solid fa-right-to-bracket me-1"></i>Login</a>
                    <span class="text-muted">|</span>
                    <a href="cadastro.php" class="text-white text-decoration-none ms-2">Cadastrar-se</a>
                <?php endif; ?>
                <span class="mx-2 text-muted">|</span>
                <a href="index.php" class="text-info text-decoration-none fw-semibold"><i class="fa-solid fa-store me-1"></i>Voltar à Loja</a>
            </div>
        </div>
    </nav>

    <main class="container py-5 flex-grow-1">
        
        <div class="text-center mb-5">
            <h1 class="fw-bold text-dark display-6">
                <i class="fa-solid fa-cart-shopping text-primary me-2"></i>Meu Carrinho
            </h1>
            <p class="text-muted">Gerencie os itens selecionados antes de fechar o seu pedido.</p>
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
            
            <div class="card cart-card p-5 text-center bg-white">
                <div class="py-4">
                    <i class="fa-solid fa-box-open text-muted mb-4 shadow-sm p-4 bg-light rounded-circle" style="font-size: 4rem;"></i>
                    <h2 class="fw-bold text-secondary mb-2">Seu carrinho está vazio!</h2>
                    <p class="text-muted mb-4 fs-5">Que tal dar uma olhada em nossos ótimos produtos e serviços?</p>
                    <a href="index.php" class="btn btn-primary btn-lg px-4 py-3 fw-bold rounded-3 shadow">
                        <i class="fa-solid fa-store me-2"></i>Ir para a Vitrine
                    </a>
                </div>
            </div>

        <?php else: ?>

            <div class="row g-4">
                
                <div class="col-12 col-xl-8">
                    <div class="card cart-card bg-white overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase small tracking-wider" style="font-size: 0.8rem;">
                                    <tr>
                                        <th class="py-3 ps-4">Produto</th>
                                        <th class="py-3">Preço</th>
                                        <th class="py-3 text-center" style="width: 140px;">Qtd</th>
                                        <th class="py-3">Subtotal</th>
                                        <th class="py-3 text-center pe-4" style="width: 80px;">Ação</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    <?php foreach ($produtosCarrinho as $item): ?>
                                        <tr>
                                            <td class="py-3 ps-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <?php $img = !empty($item['imagem']) ? $item['imagem'] : "../assets/img/sem-imagem.png"; ?>
                                                    <img src="<?= htmlspecialchars($img) ?>" alt="Imagem do Produto" class="item-img p-1 shadow-sm">
                                                    <div>
                                                        <span class="d-block fw-bold text-dark fs-6"><?= htmlspecialchars($item['nome_produto']) ?></span>
                                                        <span class="badge bg-light text-muted border small">Cód: #<?= $item['id_produto'] ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <td class="py-3 text-secondary fw-semibold">
                                                R$ <?= number_format($item['preco'], 2, ',', '.') ?>
                                            </td>
                                            
                                            <td class="py-3">
                                                <div class="d-flex justify-content-center align-items-center gap-1">
                                                    <form action="carrinho.php" method="POST" class="m-0">
                                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                        <input type="hidden" name="acao" value="diminuir">
                                                        <input type="hidden" name="id_produto" value="<?= $item['id_produto'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary btn-action-qty fw-bold rounded-2">-</button>
                                                    </form>
                                                    
                                                    <input type="text" value="<?= $item['whitespace-normalized' === '' ? '' : $item['quantidade']] ?>" readonly class="form-control form-control-sm qty-input bg-light border-0 rounded-2 text-dark">
                                                    
                                                    <form action="carrinho.php" method="POST" class="m-0">
                                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                        <input type="hidden" name="acao" value="adicionar">
                                                        <input type="hidden" name="id_produto" value="<?= $item['id_produto'] ?>">
                                                        
                                                        <?php if ($item['quantidade'] >= $item['estoque']): ?>
                                                            <button type="button" disabled class="btn btn-sm btn-light btn-action-qty rounded-2 border text-muted" title="Estoque máximo atingido">
                                                                <i class="fa-solid fa-ban" style="font-size: 0.75rem;"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary btn-action-qty fw-bold rounded-2">+</button>
                                                        <?php endif; ?>
                                                    </form>
                                                </div>
                                            </td>

                                            <td class="py-3 fw-bold text-dark fs-6">
                                                R$ <?= number_format($item['subtotal'], 2, ',', '.') ?>
                                            </td>
                                            
                                            <td class="py-3 text-center pe-4">
                                                <form action="carrinho.php" method="POST" class="m-0">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="acao" value="remover">
                                                    <input type="hidden" name="id_produto" value="<?= $item['id_produto'] ?>">
                                                    <button type="submit" class="btn btn-link text-danger p-0 border-0 fs-5 lh-1 transition-all" title="Remover item">
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
                    <div class="card cart-card bg-white p-4">
                        <h4 class="fw-bold text-dark border-b pb-2 mb-3">Resumo da Compra</h4>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Total de Itens:</span>
                            <span class="fw-semibold text-dark fs-5"><?= $qtdCarrinho ?> un.</span>
                        </div>
                        
                        <hr class="text-muted my-3">
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="text-dark fw-bold uppercase fs-7" style="letter-spacing: 0.5px;">Valor Total:</span>
                            <strong class="text-success fs-3 fw-bolder">
                                R$ <?= number_format($totalCarrinho, 2, ',', '.') ?>
                            </strong>
                        </div>
                        
                        <a href="checkout.php" class="btn btn-success btn-checkout btn-lg w-100 fw-bold py-3 rounded-3 shadow">
                            <i class="fa-solid fa-credit-card me-2"></i>Ir para o Pagamento
                        </a>
                    </div>
                </div>

            </div>

        <?php endif; ?>

    </main>

    <footer class="rodape mt-auto py-4 bg-white border-top text-center text-secondary">
        <div class="container">
            <p class="mb-0 small">&copy; <?= date("Y") ?> <strong class="text-dark">MaxTech</strong>. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>