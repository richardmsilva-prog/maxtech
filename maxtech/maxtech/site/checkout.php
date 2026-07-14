<?php
require_once "../systems/core/protecao.php";

require_once "../systems/core/conexao.php";

if (empty($_SESSION["carrinho"])) {
    header("Location: index.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST["csrf_token"]) || $_POST["csrf_token"] !== $_SESSION["csrf_token"]) {
        die("Falha de segurança: Token CSRF inválido. Retorne à loja e tente novamente.");
    }

    try {
        $mysqli->begin_transaction();

        $idsProdutos = array_keys($_SESSION["carrinho"]);
        
        $qtdIds = count($idsProdutos);
        $placeholders = implode(",", array_fill(0, $qtdIds, "?"));
        
        $sql = "SELECT id_produto, nome_produto, preco, estoque FROM produtos WHERE id_produto IN ($placeholders) FOR UPDATE";
        $stmt = $mysqli->prepare($sql);
        
        $tipos = str_repeat("i", $qtdIds);
        $stmt->bind_param($tipos, ...$idsProdutos);
        $stmt->execute();
        $res = $stmt->get_result();

        $produtosValidados = [];
        $totalVenda = 0;

        while ($produto = $res->fetch_assoc()) {
            $id = $produto["id_produto"];
            $qtdPedida = $_SESSION["carrinho"][$id];

            if ($produto["estoque"] < $qtdPedida) {
                $_SESSION["mensagem"] = "A compra não pôde ser concluída pois o stock do produto '" . $produto["nome_produto"] . "' esgotou ou foi alterado recentemente.";
                
                $mysqli->rollback(); 
                header("Location: carrinho.php");
                exit;
            }

            $subtotal = $qtdPedida * $produto["preco"];
            $totalVenda += $subtotal;

            $produtosValidados[] = [
                "id"             => $id,
                "quantidade"     => $qtdPedida,
                "preco_unitario" => $produto["preco"],
                "novo_estoque"   => $produto["estoque"] - $qtdPedida
            ];
        }
        $stmt->close();

        $idUsuario = $_SESSION["id"];
        $sqlVenda = "INSERT INTO vendas (id_usuario, valor_total, data_venda) VALUES (?, ?, NOW())";
        $stmtVenda = $mysqli->prepare($sqlVenda);
        $stmtVenda->bind_param("id", $idUsuario, $totalVenda);
        $stmtVenda->execute();
        
        $idVenda = $mysqli->insert_id; 
        $stmtVenda->close();

        $sqlItem = "INSERT INTO itens_venda (id_venda, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)";
        $stmtItem = $mysqli->prepare($sqlItem);

        $sqlEstoque = "UPDATE produtos SET estoque = ? WHERE id_produto = ?";
        $stmtEstoque = $mysqli->prepare($sqlEstoque);

        foreach ($produtosValidados as $item) {
            $stmtItem->bind_param("iiid", $idVenda, $item["id"], $item["quantidade"], $item["preco_unitario"]);
            $stmtItem->execute();

            $stmtEstoque->bind_param("ii", $item["novo_estoque"], $item["id"]);
            $stmtEstoque->execute();
        }
        
        $stmtItem->close();
        $stmtEstoque->close();

        $sqlEntrega = "INSERT INTO entregas (id_venda, status_entrega) VALUES (?, 'Processando')";
        $stmtEntrega = $mysqli->prepare($sqlEntrega);
        $stmtEntrega->bind_param("i", $idVenda);
        $stmtEntrega->execute();
        $stmtEntrega->close();

        $sqlInstalacao = "INSERT INTO instalacoes (id_venda, status_instalacao) VALUES (?, 'Agendamento Pendente')";
        $stmtInstalacao = $mysqli->prepare($sqlInstalacao);
        $stmtInstalacao->bind_param("i", $idVenda);
        $stmtInstalacao->execute();
        $stmtInstalacao->close();

        $mysqli->commit();

        unset($_SESSION["carrinho"]);
        unset($_SESSION["csrf_token"]); 
        
        header("Location: sucesso.php?pedido=" . $idVenda);
        exit;

    } catch (Exception $e) {
        $mysqli->rollback();
        
        error_log("Erro crítico no checkout: " . $e->getMessage());
        
        $_SESSION["mensagem"] = "Ocorreu um erro inesperado ao processar o seu pedido. Por favor, tente novamente.";
        header("Location: carrinho.php");
        exit;
    }
}

$idsProdutos = array_keys($_SESSION["carrinho"]);

$qtdIds = count($idsProdutos);
$placeholders = implode(",", array_fill(0, $qtdIds, "?"));

$sql = "SELECT id_produto, nome_produto, preco, estoque, imagem FROM produtos WHERE id_produto IN ($placeholders)";
$stmt = $mysqli->prepare($sql);

$tipos = str_repeat("i", $qtdIds);
$stmt->bind_param($tipos, ...$idsProdutos);
$stmt->execute();
$res = $stmt->get_result();

$totalGeral = 0;
$produtosResumo = [];

while ($produto = $res->fetch_assoc()) {
    $id = $produto["id_produto"];
    
    $quantidade = $_SESSION["carrinho"][$id];
    
    if ($quantidade > $produto["estoque"]) {
        $quantidade = $produto["estoque"];
        $_SESSION["carrinho"][$id] = $quantidade; 
        $_SESSION["mensagem"] = "Atenção: A quantidade de alguns itens foi ajustada ao limite do nosso estoque.";
    }
    
    $subtotal = $quantidade * $produto["preco"];
    $totalGeral += $subtotal;

    $produtosResumo[] = [
        "id"         => $id,
        "nome"       => $produto["nome_produto"],
        "preco"      => $produto["preco"],
        "imagem"     => $produto["imagem"], 
        "quantidade" => $quantidade,
        "subtotal"   => $subtotal   
    ];
}

$stmt->close(); 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Seguro - MaxTech</title>
    <meta name="description" content="Finalize sua compra de forma segura na MaxTech. Confira os itens do seu carrinho e preencha as informações de entrega e pagamento.">
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="pg-checkout d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg main-header border-bottom-secure">
        <div class="container justify-content-between">
            <a class="header-logo d-flex align-items-center" href="index.php">
                <img src="../assets/img/Logo_MaxTech.jpg" alt="MaxTech Vendas, Entrega e Instalação">
            </a>
            <div class="d-flex align-items-center gap-2" style="color: var(--cor-texto-claro);">
                <i class="fa-solid fa-lock text-success fs-5"></i>
                <span class="fw-semibold small d-none d-sm-inline">Ambiente 100% Seguro</span>
            </div>
        </div>
    </nav>

    <main class="container main-container flex-grow-1 mb-5">
        
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9 col-xl-8">
                
                <div class="text-center mb-4">
                    <h1 class="fw-bold mb-2" style="color: var(--cor-secundaria);">Resumo do Pedido</h1>
                    <p style="color: var(--cor-texto-claro);">Confira seus itens com atenção antes de finalizar a compra.</p>
                </div>

                <?php if (isset($_SESSION["mensagem"])): ?>
                    <div class="alert alert-warning alert-dismissible fade show shadow-sm border-0" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        <?= htmlspecialchars($_SESSION["mensagem"]) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION["mensagem"]); ?>
                <?php endif; ?>

                <div class="border rounded-4 bg-white overflow-hidden shadow-sm">
                    
                    <div class="bg-white p-4 border-bottom">
                        <h4 class="mb-0 fw-bold" style="color: var(--cor-secundaria);">
                            <i class="fa-solid fa-box me-2" style="color: var(--cor-primaria);"></i>Itens no Carrinho
                        </h4>
                    </div>

                    <ul class="list-group list-group-flush border-bottom-0">
                        <?php foreach ($produtosResumo as $item): ?>
                            <?php 
                                $caminhoImagem = !empty($item['imagem']) ? $item['imagem'] : "../assets/img/sem-imagem.png";
                            ?>
                            <li class="list-group-item p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3" style="border-bottom-color: var(--cor-clara);">
                                
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= htmlspecialchars($caminhoImagem) ?>" alt="<?= htmlspecialchars($item["nome"]) ?>" class="checkout-img-thumb shadow-sm">
                                    <div>
                                        <h5 class="mb-1 fw-bold text-dark fs-6"><?= htmlspecialchars($item["nome"]) ?></h5>
                                        <p class="mb-0 small" style="color: var(--cor-texto-claro);">Valor un: R$ <?= number_format($item["preco"], 2, ",", ".") ?></p>
                                    </div>
                                </div>
                                
                                <div class="text-md-end mt-2 mt-md-0 px-md-3">
                                    <p class="mb-0 small" style="color: var(--cor-texto-claro);">Quantidade: <strong class="text-dark fs-6"><?= $item["quantidade"] ?></strong></p>
                                    <h5 class="mb-0 fw-bold mt-1 price-text">R$ <?= number_format($item["subtotal"], 2, ",", ".") ?></h5>
                                </div>

                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="p-4 p-md-5" style="background-color: var(--cor-fundo-alt); border-top: 1px solid var(--cor-clara);">
                        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-4">
                            
                            <div class="text-center text-md-start">
                                <span class="text-uppercase fw-semibold" style="color: var(--cor-texto-claro); letter-spacing: 1px; font-size: 0.85rem;">Total a Pagar</span>
                                <h2 class="price-text fw-bolder mb-0 mt-1" style="font-size: 2.2rem;">
                                    R$ <?= number_format($totalGeral, 2, ",", ".") ?>
                                </h2>
                            </div>

                            <form action="checkout.php" method="POST" class="w-100 m-0" style="max-width: 300px;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                                
                                <button type="submit" class="btn-custom-primary btn-lg w-100 fw-bold py-3 px-4 border-0 text-center text-decoration-none d-block shadow">
                                    <i class="fa-solid fa-lock me-2"></i> Confirmar e Pagar
                                </button>
                            </form>

                        </div>
                    </div>

                </div>

                <div class="text-center mt-4">
                    <a href="carrinho.php" class="text-decoration-none fw-semibold hover-primary" style="color: var(--cor-texto-claro); font-size: 1.05rem;">
                        <i class="fa-solid fa-arrow-left me-2"></i>Voltar para o carrinho
                    </a>
                </div>

            </div>
        </div>
    </main>

    <footer class="footer mt-auto py-4 bg-white border-top text-center">
        <div class="container">
            <p class="mb-1" style="color: var(--cor-texto-claro);"><i class="fa-solid fa-shield-halved text-success me-1"></i> Seus dados estão protegidos.</p>
            <p class="mb-0 small" style="color: var(--cor-texto-claro);">&copy; <?= date("Y") ?> <strong style="color: var(--cor-secundaria);">MaxTech</strong>. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>