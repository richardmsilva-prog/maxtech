<?php
include 'conexao.php';

$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';
$mensagem    = "";
$tipo_msg    = "";

// Buscar Vendas para o dropdown
$vendas_opcoes = $mysqli->query("SELECT v.id_venda, c.nome FROM vendas v JOIN clientes c ON v.id_cliente = c.id_cliente ORDER BY v.id_venda DESC");
// Buscar Produtos para o dropdown
$produtos_opcoes = $mysqli->query("SELECT id_produto, nome_produto, preco FROM produtos ORDER BY nome_produto ASC");

// ── Ação: EXCLUIR ────────────────────────────────────────
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($mysqli->query("DELETE FROM itens_venda WHERE id_item = $id")) {
        $mensagem = "Item excluído com sucesso.";
        $tipo_msg = "sucesso";
    } else {
        $mensagem = "Erro ao excluir: " . $mysqli->error;
        $tipo_msg = "erro";
    }
}

// ── Ação: SALVAR ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_venda = (int) ($_POST['id_venda'] ?? 0);
    $id_produto = (int) ($_POST['id_produto'] ?? 0);
    $quantidade = (int) ($_POST['quantidade'] ?? 1);
    $preco_unitario = number_format((float) str_replace(',', '.', $_POST['preco_unitario'] ?? '0'), 2, '.', '');

    if ($id_venda === 0 || $id_produto === 0 || $quantidade <= 0) {
        $mensagem = "Preencha todos os campos corretamente.";
        $tipo_msg = "erro";
    } else {
        $sql = "INSERT INTO itens_venda (id_venda, id_produto, quantidade, preco_unitario) VALUES ($id_venda, $id_produto, $quantidade, $preco_unitario)";
        if ($mysqli->query($sql)) {
            $mensagem = "Item adicionado à venda com sucesso.";
            $tipo_msg = "sucesso";
        } else {
            $mensagem = "Erro ao adicionar item: " . $mysqli->error;
            $tipo_msg = "erro";
        }
    }
}

// ── LISTAR Itens (JOIN triplo para exibir nomes legíveis) ───────────
$sql_lista = "
    SELECT i.id_item, i.quantidade, i.preco_unitario, v.id_venda, p.nome_produto, c.nome AS nome_cliente 
    FROM itens_venda i
    JOIN vendas v ON i.id_venda = v.id_venda
    JOIN clientes c ON v.id_cliente = c.id_cliente
    JOIN produtos p ON i.id_produto = p.id_produto
    ORDER BY i.id_item DESC
";
$lista = $mysqli->query($sql_lista);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - Itens da Venda</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-painel">

    <header class="topbar">
        <span class="topbar-titulo">&#128722; MaxTech - Itens da Venda</span>
        <div class="topbar-usuario">
            <a href="vendas.php" style="color:white; margin-right: 10px;">Voltar p/ Vendas</a> |
            <a href="entregas.php" style="color:white; margin-right: 10px; margin-left:10px;">Entregas</a> |
            <a href="instalacoes.php" style="color:white; margin-right: 10px; margin-left:10px;">Instalações</a> |
            <a href="logout.php">Sair</a>
        </div>
    </header>

    <main class="container painel-conteudo">

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem <?= htmlspecialchars($tipo_msg) ?>"><?= htmlspecialchars($mensagem) ?></p>
        <?php endif; ?>

        <section class="card-form">
            <h2>Adicionar Item a uma Venda</h2>
            <form action="" method="POST">
                <div class="form-linha">
                    <div class="campo">
                        <label for="id_venda">Pedido / Cliente</label>
                        <select id="id_venda" name="id_venda" required style="width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">-- Selecione o Pedido --</option>
                            <?php while ($v = $vendas_opcoes->fetch_assoc()): ?>
                                <option value="<?= $v['id_venda'] ?>">Pedido #<?= $v['id_venda'] ?> - <?= htmlspecialchars($v['nome']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="id_produto">Produto</label>
                        <select id="id_produto" name="id_produto" required style="width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">-- Selecione o Produto --</option>
                            <?php while ($p = $produtos_opcoes->fetch_assoc()): ?>
                                <option value="<?= $p['id_produto'] ?>"><?= htmlspecialchars($p['nome_produto']) ?> (R$ <?= $p['preco'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-linha">
                    <div class="campo campo-pequeno">
                        <label for="quantidade">Quantidade</label>
                        <input type="number" id="quantidade" name="quantidade" min="1" required value="1">
                    </div>
                    <div class="campo campo-pequeno">
                        <label for="preco_unitario">Preço Unitário (R$)</label>
                        <input type="number" id="preco_unitario" name="preco_unitario" min="0.01" step="0.01" required>
                    </div>
                </div>
                <div class="form-acoes">
                    <button type="submit" class="btn btn-principal">Adicionar Item</button>
                </div>
            </form>
        </section>

        <section class="card-tabela">
            <h2>Itens Registrados</h2>
            <?php if ($lista && $lista->num_rows > 0): ?>
                <table class="tabela-produtos">
                    <thead>
                        <tr>
                            <th>ID Item</th>
                            <th>Pedido / Cliente</th>
                            <th>Produto</th>
                            <th>Qtd</th>
                            <th>Preço Unit.</th>
                            <th>Subtotal</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($i = $lista->fetch_assoc()): 
                            $subtotal = $i['quantidade'] * $i['preco_unitario'];
                        ?>
                            <tr>
                                <td><?= (int) $i['id_item'] ?></td>
                                <td>#<?= (int) $i['id_venda'] ?> - <?= htmlspecialchars($i['nome_cliente']) ?></td>
                                <td><?= htmlspecialchars($i['nome_produto']) ?></td>
                                <td><?= (int) $i['quantidade'] ?></td>
                                <td>R$ <?= number_format((float) $i['preco_unitario'], 2, ',', '.') ?></td>
                                <td><strong>R$ <?= number_format($subtotal, 2, ',', '.') ?></strong></td>
                                <td class="acoes">
                                    <a href="?acao=excluir&id=<?= (int) $i['id_item'] ?>" class="btn-acao btn-excluir" onclick="return confirm('Excluir este item da venda?')">Excluir</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="sem-registros">Nenhum item registrado.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>