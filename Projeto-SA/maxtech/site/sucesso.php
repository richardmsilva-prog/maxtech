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
    
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .bg-topbar {
            background-color: #2c3e50;
        }
        .success-icon {
            font-size: 5rem;
            color: #198754;
        }
        @media print {
            body { 
                background-color: #ffffff !important; 
            }
            .no-print, .btn, hr, .topbar, footer { 
                display: none !important; 
            }
            .card { 
                box-shadow: none !important; 
                border: 1px solid #dee2e6 !important; 
            }
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <nav class="navbar navbar-dark bg-topbar topbar py-3 shadow-sm no-print">
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
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-7">
                
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4 p-md-5">
                        
                        <div class="text-center mb-4">
                            <div class="mb-3 no-print">
                                <i class="fa-solid fa-circle-check success-icon"></i>
                            </div>
                            <h2 class="fw-bold text-dark">Compra Confirmada!</h2>
                            <p class="text-muted fs-6">
                                Obrigado, <strong><?= htmlspecialchars($nomeUsuario) ?></strong>! Seu pedido foi processado com sucesso.
                            </p>
                        </div>

                        <div class="bg-white border p-4 rounded-3 mb-4 shadow-sm">
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-secondary text-uppercase fs-6">
                                <i class="fa-solid fa-receipt me-2"></i>Resumo do Pedido
                            </h5>
                            
                            <div class="d-flex justify-content-between mb-2 small text-muted">
                                <span>Número do Pedido: <strong class="text-dark fs-6">#<?= htmlspecialchars($dadosVenda["id_venda"]) ?></strong></span>
                                <span>Data: <strong><?= $dataFormatada ?></strong></span>
                            </div>
                            
                            <hr class="my-3">

                            <div class="table-responsive mb-3">
                                <table class="table table-borderless align-middle mb-0">
                                    <tbody>
                                        <?php foreach ($produtosComprados as $item): ?>
                                            <tr>
                                                <td class="ps-0" style="width: 70%;">
                                                    <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($item["nome"]) ?></span>
                                                    <small class="text-muted">Qtd: <?= $item["quantidade"] ?> x R$ <?= number_format($item["preco_unitario"], 2, ",", ".") ?></small>
                                                </td>
                                                <td class="text-end pe-0 fw-semibold text-dark">
                                                    R$ <?= number_format($item["quantidade"] * $item["preco_unitario"], 2, ",", ".") ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <hr class="my-3 border-secondary border-opacity-25">

                            <div class="d-flex justify-content-between align-items-center pt-2">
                                <span class="fw-bold text-dark fs-5">Valor Total:</span>
                                <span class="fw-bold text-success fs-3">R$ <?= $totalFormatado ?></span>
                            </div>
                        </div>

                        <div class="d-grid gap-3 no-print mt-5">
                            <a href="meus_pedidos.php" class="btn btn-success btn-lg rounded-3 fw-bold shadow-sm">
                                <i class="fa-solid fa-truck-fast me-2"></i>Acompanhar Entrega e Instalação
                            </a>
                            
                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <button onclick="window.print();" class="btn btn-outline-secondary w-100 py-2 rounded-3 fw-semibold">
                                        <i class="fa-solid fa-print me-2"></i>Imprimir Recibo
                                    </button>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <a href="index.php" class="btn btn-outline-primary w-100 py-2 rounded-3 fw-semibold">
                                        <i class="fa-solid fa-basket-shopping me-2"></i>Continuar Comprando
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4 no-print">
                            <small class="text-muted">
                                Precisa de ajuda com este pedido? <a href="contato.php" class="text-primary text-decoration-none fw-semibold">Fale com o nosso suporte</a>.
                            </small>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </main>

    <footer class="rodape mt-auto py-4 bg-white border-top text-center text-secondary no-print">
        <div class="container">
            <p class="mb-0 small">&copy; <?= date("Y") ?> <strong class="text-dark">MaxTech</strong>. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>