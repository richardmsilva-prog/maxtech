<?php

require_once "../systems/core/protecao.php";
require_once "../systems/core/conexao.php"; 

if (empty($_GET["pedido"])) {
    header("Location: index.php");
    exit;
}

$idPedido = $_GET["pedido"];
$idUsuario = $_SESSION["id"];

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
    <title>Pedido Confirmado #<?= htmlspecialchars($dadosVenda["id_venda"]) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        @media print {
            body { 
                background-color: #ffffff !important; 
            }
            .no-print, .btn, hr { 
                display: none !important; 
            }
            .card { 
                box-shadow: none !important; 
                border: none !important; 
            }
        }
    </style>
</head>
<body class="bg-light">

    <div class="container mt-5 pt-3 mb-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-7">
                
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4 p-md-5">
                        
                        <div class="text-center mb-4">
                            <div class="mb-3 text-success no-print">
                                <svg xmlns="http://www.w3.org/2000/svg" width="75" height="75" fill="currentColor" class="bi bi-check-circle-fill mx-auto" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                                </svg>
                            </div>
                            <h2 class="fw-bold text-dark">Compra Confirmada!</h2>
                            <p class="text-muted">
                                Obrigado, <strong><?= htmlspecialchars($_SESSION["nome"]) ?></strong>! Enviamos os detalhes e o comprovante para o seu e-mail cadastrado.
                            </p>
                        </div>

                        <div class="bg-white border p-4 rounded-3 mb-4">
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-secondary text-uppercase">Resumo do Pedido</h5>
                            
                            <div class="d-flex justify-content-between mb-2 small text-muted">
                                <span>Pedido: <strong class="text-dark">#<?= htmlspecialchars($dadosVenda["id_venda"]) ?></strong></span>
                                <span>Data: <?= $dataFormatada ?></span>
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

                            <hr class="my-3">

                            <div class="d-flex justify-content-between align-items-center pt-2">
                                <span class="fw-bold text-dark fs-5">Valor Total:</span>
                                <span class="fw-bold text-primary fs-4">R$ <?= $totalFormatado ?></span>
                            </div>
                        </div>

                        <div class="d-grid gap-2 no-print">
                            <a href="meus_pedidos.php" class="btn btn-primary btn-lg rounded-3 fw-semibold">Acompanhar Entrega e Instalação</a>
                            
                            <div class="row g-2">
                                <div class="col-6">
                                    <button onclick="window.print();" class="btn btn-outline-secondary w-100 py-2 rounded-3">
                                        Imprimir Recibo
                                    </button>
                                </div>
                                <div class="col-6">
                                    <a href="index.php" class="btn btn-outline-secondary w-100 py-2 rounded-3">
                                        Continuar Comprando
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4 no-print">
                            <small class="text-muted">
                                Precisa de ajuda com este pedido? <a href="contato.php" class="text-decoration-none">Fale com o nosso suporte</a>.
                            </small>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>