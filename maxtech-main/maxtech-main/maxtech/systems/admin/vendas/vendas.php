<?php

include 'conexao.php';

$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';
$mensagem    = "";
$tipo_msg    = "";

// Buscar clientes para o dropdown (Lista de Opções)
$clientes_opcoes = $mysqli->query("SELECT id_cliente, nome FROM clientes ORDER BY nome ASC");

// ── Ação: EXCLUIR ────────────────────────────────────────
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($mysqli->query("DELETE FROM vendas WHERE id_venda = $id")) {
        $mensagem = "Venda excluída com sucesso.";
        $tipo_msg = "sucesso";
    } else {
        $mensagem = "Erro ao excluir: " . $mysqli->error;
        $tipo_msg = "erro";
    }
}

// ── Ação: SALVAR ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = (int) ($_POST['id_cliente'] ?? 0);
    $valor_total = number_format((float) str_replace(',', '.', $_POST['valor_total'] ?? '0'), 2, '.', '');
    $status = $mysqli->real_escape_string($_POST['status_pagamento'] ?? 'Pendente');

    if ($id_cliente === 0 || $valor_total <= 0) {
        $mensagem = "Selecione um cliente e informe um valor válido.";
        $tipo_msg = "erro";
    } else {
        $sql = "INSERT INTO vendas (id_cliente, valor_total, status_pagamento) VALUES ($id_cliente, $valor_total, '$status')";
        if ($mysqli->query($sql)) {
            $mensagem = "Venda registrada com sucesso.";
            $tipo_msg = "sucesso";
        } else {
            $mensagem = "Erro ao registrar venda: " . $mysqli->error;
            $tipo_msg = "erro";
        }
    }
}

// ── LISTAR Vendas com o Nome do Cliente (JOIN) ───────────
// Usamos INNER JOIN para buscar o nome do cliente usando o id_cliente da venda
$sql_lista = "
    SELECT v.id_venda, v.data_venda, v.valor_total, v.status_pagamento, c.nome AS nome_cliente 
    FROM vendas v
    INNER JOIN clientes c ON v.id_cliente = c.id_cliente
    ORDER BY v.data_venda DESC
";
$lista = $mysqli->query($sql_lista);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - Vendas</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-painel">

    <header class="topbar">
        <span class="topbar-titulo">&#128181; MaxTech - Registro de Vendas</span>
        <div class="topbar-usuario">
            <a href="clientes.php" style="color:white; margin-right: 10px;">Ir para Clientes</a> |
            <a href="produtos.php" style="color:white; margin-right: 10px; margin-left:10px;">Ir para Produtos</a> |
            <a href="logout.php">Sair</a>
        </div>
    </header>

    <main class="container painel-conteudo">

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem <?= htmlspecialchars($tipo_msg) ?>"><?= htmlspecialchars($mensagem) ?></p>
        <?php endif; ?>

        <section class="card-form">
            <h2>Nova Venda Rápida</h2>
            <form action="" method="POST">
                <div class="form-linha">
                    <div class="campo">
                        <label for="id_cliente">Selecione o Cliente</label>
                        <select id="id_cliente" name="id_cliente" required style="width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">-- Escolha um cliente --</option>
                            <?php while ($cli = $clientes_opcoes->fetch_assoc()): ?>
                                <option value="<?= $cli['id_cliente'] ?>"><?= htmlspecialchars($cli['nome']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="campo campo-pequeno">
                        <label for="valor_total">Valor Total (R$)</label>
                        <input type="number" id="valor_total" name="valor_total" min="0.01" step="0.01" required>
                    </div>
                    <div class="campo campo-pequeno">
                        <label for="status_pagamento">Status do Pagamento</label>
                        <select id="status_pagamento" name="status_pagamento" style="width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="Pendente">Pendente</option>
                            <option value="Aprovado">Aprovado</option>
                            <option value="Recusado">Recusado</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                    </div>
                </div>
                <div class="form-acoes">
                    <button type="submit" class="btn btn-principal">Registrar Venda</button>
                </div>
            </form>
        </section>

        <section class="card-tabela">
            <h2>Histórico de Vendas</h2>
            <?php if ($lista && $lista->num_rows > 0): ?>
                <table class="tabela-produtos">
                    <thead>
                        <tr>
                            <th># Pedido</th>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($v = $lista->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int) $v['id_venda'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?></td>
                                <td><strong><?= htmlspecialchars($v['nome_cliente']) ?></strong></td>
                                <td>R$ <?= number_format((float) $v['valor_total'], 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($v['status_pagamento']) ?></td>
                                <td class="acoes">
                                    <a href="?acao=excluir&id=<?= (int) $v['id_venda'] ?>" class="btn-acao btn-excluir" onclick="return confirm('Cancelar e excluir esta venda?')">Excluir</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="sem-registros">Nenhuma venda registrada.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>