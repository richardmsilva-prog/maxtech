<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once "../systems/core/protecao.php";
require_once "../systems/core/conexao.php"; 

if (empty($_GET["pedido"])) {
    header("Location: index.php");
    exit;
}

$idPedido = $_GET["pedido"];
$idUsuario = $_SESSION["id"];

// Variáveis para a Navbar
$logado      = isset($_SESSION['id']);
$nomeUsuario = $_SESSION['nome'] ?? 'Cliente';

$produtosComprados = [];
$dadosVenda = null;

try {
    $sql = "SELECT v.id_venda, v.total_venda, v.data_venda, 
                   iv.quantidade, iv.preco_unitario, 
                   p.nome_produto
            FROM vendas v
            INNER JOIN itens_venda iv ON v.id_venda = iv.id_venda
            INNER JOIN produtos p ON iv.id_produto = p.id_produto
            WHERE v.id_venda = ? AND v.id_usuario = ?";
            
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $idPedido, $idUsuario);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $stmt->close();
        header("Location: index.php");
        exit;
    }

    while ($linha = $res->fetch_assoc()) {
        if (!$dadosVenda) {
            $dadosVenda = [
                "id_venda"    => $linha["id_venda"],
                "total_venda" => $linha["total_venda"],
                "data_venda"  => $linha["data_venda"]
            ];
        }
        
        $produtosComprados[] = [
            "nome"           => $linha["nome_produto"],
            "quantidade"     => $linha["quantidade"],
            "preco_unitario" => $linha["preco_unitario"]
        ];
    }
    $stmt->close();

    $dataFormatada = date("d/m/Y H:i", strtotime($dadosVenda["data_venda"]));
    $totalFormatado = number_format($dadosVenda["total_venda"], 2, ",", ".");

} catch (Exception $e) {
    error_log("Erro ao carregar página de sucesso: " . $e->getMessage());
    $_SESSION["mensagem"] = "Não foi possível carregar o resumo do pedido, mas sua compra foi processada com sucesso.";
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado #<?= htmlspecialchars($dadosVenda["id_venda"]) ?> - MaxTech</title>
    
    <link class="no-print" rel="stylesheet" href="../assets/css/style.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="pg-sucesso d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg main-header no-print">
        <div class="container">
            <a class="header-logo d-flex align-items-center" href="index.php">
                <img src="../assets/img/Logo_MaxTech.jpg" alt="MaxTech Vendas, Entrega e Instalação">
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
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

    <main class="container main-container flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-7">
                
                <div class="card shadow-sm border rounded-4 bg-white">
                    <div class="card-body p-4 p-md-5">
                        
                        <div class="text-center mb-4">
                            <div class="mb-3 no-print">
                                <i class="fa-solid fa-circle-check" style="font-size: 5rem; color: #27ae60;"></i>
                            </div>
                            <h2 class="fw-bold" style="color: var(--cor-secundaria);">Compra Confirmada!</h2>
                            <p style="color: var(--cor-texto-claro);" class="fs-6">
                                Obrigado, <strong><?= htmlspecialchars($nomeUsuario) ?></strong>! Seu pedido foi processado com sucesso.
                            </p>
                        </div>

                        <div class="bg-white border p-4 rounded-3 mb-4 shadow-sm" style="border-color: var(--cor-clara)!important;">
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-uppercase fs-6" style="color: var(--cor-secundaria);">
                                <i class="fa-solid fa-receipt me-2" style="color: var(--cor-primaria);"></i>Resumo do Pedido
                            </h5>
                            
                            <div class="d-flex justify-content-between mb-2 small" style="color: var(--cor-texto-claro);">
                                <span>Número do Pedido: <strong class="text-dark fs-6">#<?= htmlspecialchars($dadosVenda["id_venda"]) ?></strong></span>
                                <span>Data: <strong><?= $dataFormatada ?></strong></span>
                            </div>
                            
                            <hr class="my-3" style="border-color: var(--cor-clara);">

                            <div class="table-responsive mb-3">
                                <table class="table table-borderless align-middle mb-0">
                                    <tbody>
                                        <?php foreach ($produtosComprados as $item): ?>
                                            <tr>
                                                <td class="ps-0" style="width: 70%;">
                                                    <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($item["nome"]) ?></span>
                                                    <small style="color: var(--cor-texto-claro);">Qtd: <?= $item["quantidade"] ?> x R$ <?= number_format($item["preco_unitario"], 2, ",", ".") ?></small>
                                                </td>
                                                <td class="text-end pe-0 fw-semibold text-dark">
                                                    R$ <?= number_format($item["quantidade"] * $item["preco_unitario"], 2, ",", ".") ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <hr class="my-3" style="border-color: var(--cor-clara);">

                            <div class="d-flex justify-content-between align-items-center pt-2">
                                <span class="fw-bold fs-5" style="color: var(--cor-secundaria);">Valor Total:</span>
                                <span class="fw-bold price-text fs-3">R$ <?= $totalFormatado ?></span>
                            </div>
                        </div>

                        <div class="d-grid gap-3 no-print mt-5">
                            <a href="meus_pedidos.php" class="btn-custom-primary btn-lg rounded-3 fw-bold text-center text-decoration-none d-block shadow-sm">
                                <i class="fa-solid fa-truck-fast me-2"></i>Acompanhar Entrega e Instalação
                            </a>
                            
                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <button onclick="window.print();" class="btn-custom-secondary w-100 py-2 rounded-3 fw-semibold bg-transparent">
                                        <i class="fa-solid fa-print me-2"></i>Imprimir Recibo
                                    </button>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <a href="index.php" class="btn-custom-secondary w-100 py-2 rounded-3 fw-semibold text-center text-decoration-none d-block hover-primary-btn">
                                        <i class="fa-solid fa-basket-shopping me-2"></i>Continuar Comprando
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4 no-print">
                            <small style="color: var(--cor-texto-claro);">
                                Precisa de ajuda com este pedido? <a href="contato.php" class="text-decoration-none fw-semibold" style="color: var(--cor-primaria);">Fale com o nosso suporte</a>.
                            </small>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </main>

    <footer class="footer mt-auto py-4 bg-white border-top text-center no-print">
        <div class="container">
            <p class="mb-0 small" style="color: var(--cor-texto-claro);">&copy; <?= date("Y") ?> <strong style="color: var(--cor-secundaria);">MaxTech</strong>. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>