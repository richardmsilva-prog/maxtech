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
        $sqlVenda = "INSERT INTO vendas (id_usuario, total_venda, data_venda) VALUES (?, ?, NOW())";
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
    <title>Checkout - Resumo do Pedido</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">

    <div class="max-w-4xl mx-auto mt-10 p-6">
        
        <header class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Resumo do Pedido</h1>
            <p class="text-gray-500 mt-2">Confira os itens antes de finalizar a compra.</p>
        </header>

        <?php if (isset($_SESSION["mensagem"])): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded shadow-sm" role="alert">
                <p><?= htmlspecialchars($_SESSION["mensagem"]) ?></p>
            </div>
            <?php unset($_SESSION["mensagem"]); ?>
        <?php endif; ?>

        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            
            <div class="p-6">
                <h2 class="text-xl font-semibold mb-4 border-b pb-2">Itens no Carrinho</h2>
                
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($produtosResumo as $item): ?>
                        <li class="py-4 flex items-center justify-between">
                            <div class="flex items-center">
                                <img src="<?= htmlspecialchars($item["imagem"]) ?>" alt="<?= htmlspecialchars($item["nome"]) ?>" class="w-16 h-16 object-cover rounded border">
                                
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($item["nome"]) ?></h3>
                                    <p class="text-sm text-gray-500">Valor un: R$ <?= number_format($item["preco"], 2, ",", ".") ?></p>
                                </div>
                            </div>
                            
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Qtd: <span class="font-semibold"><?= $item["quantidade"] ?></span></p>
                                <p class="text-lg font-bold text-gray-900">R$ <?= number_format($item["subtotal"], 2, ",", ".") ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="bg-gray-100 p-6 flex flex-col md:flex-row items-center justify-between">
                
                <div class="mb-4 md:mb-0">
                    <p class="text-sm text-gray-600 uppercase tracking-wide">Total a Pagar</p>
                    <p class="text-3xl font-extrabold text-blue-600">R$ <?= number_format($totalGeral, 2, ",", ".") ?></p>
                </div>

                <form action="checkout.php" method="POST" class="w-full md:w-auto">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                    
                    <button type="submit" class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded shadow-lg transition duration-200 ease-in-out transform hover:-translate-y-1">
                        Confirmar e Pagar
                    </button>
                </form>

            </div>
        </div>
        
        <div class="mt-6 text-center">
            <a href="carrinho.php" class="text-blue-500 hover:text-blue-700 font-medium">← Voltar para o carrinho</a>
        </div>

    </div>

</body>
</html>