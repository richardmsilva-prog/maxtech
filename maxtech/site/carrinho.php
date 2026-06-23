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
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Carrinho - MaxTech</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="pagina-carrinho">

    <header class="topbar">
        <span class="topbar-titulo">MaxTech - Installation & Products</span>
        <div class="topbar-usuario">
            <?php if ($logado): ?>
                Olá, <strong><?= htmlspecialchars($nomeUsuario) ?></strong> &nbsp;|&nbsp;
                <a href="logout.php">Sair</a>
            <?php else: ?>
                <a href="login.php" style="color: #fff; font-weight: bold; text-decoration: none;">Fazer Login</a> &nbsp;|&nbsp;
                <a href="cadastro.php" style="color: #fff; text-decoration: none;">Cadastrar-se</a>
            <?php endif; ?>
            &nbsp;|&nbsp;
            <a href="index.php" style="color: #fff; text-decoration: none;">Voltar à Loja</a>
        </div>
    </header>

    <main class="container carrinho-conteudo" style="padding-top: 30px; padding-bottom: 50px;">
        
        <h1 style="color: #2c3e50; text-align: center; margin-bottom: 30px;">
            <i class="fa-solid fa-cart-shopping"></i> Meu Carrinho
        </h1>

        <?php if (isset($_SESSION["mensagem"])): ?>
            <div style="background-color: #f39c12; color: white; padding: 15px; border-radius: 5px; text-align: center; margin-bottom: 20px;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($_SESSION["mensagem"]) ?>
            </div>
            <?php unset($_SESSION["mensagem"]);  ?>
        <?php endif; ?>

        <?php if ($carrinhoVazio || empty($produtosCarrinho)): ?>
            
            <div style="text-align: center; padding: 50px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <i class="fa-solid fa-box-open" style="font-size: 4em; color: #bdc3c7; margin-bottom: 20px;"></i>
                <h2 style="color: #7f8c8d;">Seu carrinho está vazio!</h2>
                <p style="color: #95a5a6; margin-bottom: 30px;">Que tal dar uma olhada em nossos produtos?</p>
                <a href="index.php" class="btn btn-principal" style="padding: 10px 20px; text-decoration: none;">Ir para a Vitrine</a>
            </div>

        <?php else: ?>

            <section class="card-tabela">
                <table class="tabela-produtos" style="width: 100%; text-align: left; background: #fff; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #ecf0f1;">
                            <th>Produto</th>
                            <th>Preço</th>
                            <th style="text-align: center;">Quantidade</th>
                            <th>Subtotal</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtosCarrinho as $item): ?>
                            <tr style="border-bottom: 1px solid #ecf0f1;">
                                
                                <td style="display: flex; align-items: center; gap: 15px; padding: 15px 10px;">
                                    <?php $img = !empty($item['imagem']) ? $item['imagem'] : "../assets/img/sem-imagem.png"; ?>
                                    <img src="<?= htmlspecialchars($img) ?>" alt="Imagem" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <strong><?= htmlspecialchars($item['nome_produto']) ?></strong>
                                </td>
                                
                                <td>R$ <?= number_format($item['preco'], 2, ',', '.') ?></td>
                                
                                <td style="text-align: center;">
                                    <div style="display: flex; justify-content: center; gap: 5px;">
                                        <form action="carrinho.php" method="POST" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="acao" value="diminuir">
                                            <input type="hidden" name="id_produto" value="<?= $item['id_produto'] ?>">
                                            <button type="submit" style="padding: 5px 10px; cursor: pointer;">-</button>
                                        </form>
                                        
                                        <input type="text" value="<?= $item['quantidade'] ?>" readonly style="width: 40px; text-align: center; border: 1px solid #ccc;">
                                        
                                        <form action="carrinho.php" method="POST" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="acao" value="adicionar">
                                            <input type="hidden" name="id_produto" value="<?= $item['id_produto'] ?>">
                                            <?php if ($item['quantidade'] >= $item['estoque']): ?>
                                                <button type="button" disabled style="padding: 5px 10px; cursor: not-allowed; background-color: #bdc3c7; color: #fff; border: none;" title="Estoque máximo atingido">+</button>
                                            <?php else: ?>
                                                <button type="submit" style="padding: 5px 10px; cursor: pointer;">+</button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>

                                <td><strong>R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></strong></td>
                                
                                <td>
                                    <form action="carrinho.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="acao" value="remover">
                                        <input type="hidden" name="id_produto" value="<?= $item['id_produto'] ?>">
                                        <button type="submit" style="background: transparent; color: #e74c3c; border: none; cursor: pointer; font-size: 1.2em;">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <div style="margin-top: 30px; display: flex; justify-content: flex-end;">
                <div style="background: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 300px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 1.2em;">
                        <span>Total:</span>
                        <strong style="color: #27ae60;">R$ <?= number_format($totalCarrinho, 2, ',', '.') ?></strong>
                    </div>
                    
                    <a href="checkout.php" class="btn btn-principal" style="display: block; text-align: center; background-color: #27ae60; padding: 15px; font-size: 1.1em; text-decoration: none; border-radius: 5px;">
                        Finalizar Compra
                    </a>
                </div>
            </div>

        <?php endif; ?>

    </main>
</body>
</html>