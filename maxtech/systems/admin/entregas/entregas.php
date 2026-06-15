<?php

include 'conexao.php';

$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';
$mensagem    = "";
$tipo_msg    = "";

// Buscar Itens para o dropdown
$itens_opcoes = $mysqli->query("SELECT i.id_item, i.id_venda, p.nome_produto FROM itens_venda i JOIN produtos p ON i.id_produto = p.id_produto ORDER BY i.id_item DESC");

// ── Ação: EXCLUIR ────────────────────────────────────────
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($mysqli->query("DELETE FROM entregas WHERE id_entrega = $id")) {
        $mensagem = "Entrega excluída com sucesso.";
        $tipo_msg = "sucesso";
    } else {
        $mensagem = "Erro ao excluir: " . $mysqli->error;
        $tipo_msg = "erro";
    }
}

// ── Ação: SALVAR ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_item = (int) ($_POST['id_item'] ?? 0);
    $data_saida = !empty($_POST['data_saida']) ? "'" . $_POST['data_saida'] . "'" : "NULL";
    $previsao_entrega = !empty($_POST['previsao_entrega']) ? "'" . $_POST['previsao_entrega'] . "'" : "NULL";
    $status_entrega = $mysqli->real_escape_string($_POST['status_entrega'] ?? 'Em preparação');
    $rastreio = $mysqli->real_escape_string($_POST['rastreio'] ?? '');

    if ($id_item === 0) {
        $mensagem = "Selecione um item para entrega.";
        $tipo_msg = "erro";
    } else {
        $sql = "INSERT INTO entregas (id_item, data_saida, previsao_entrega, status_entrega, rastreio) VALUES ($id_item, $data_saida, $previsao_entrega, '$status_entrega', '$rastreio')";
        if ($mysqli->query($sql)) {
            $mensagem = "Registro de entrega salvo com sucesso.";
            $tipo_msg = "sucesso";
        } else {
            $mensagem = "Erro ao registrar: " . $mysqli->error;
            $tipo_msg = "erro";
        }
    }
}

// ── LISTAR Entregas ───────────
$sql_lista = "
    SELECT e.*, p.nome_produto, i.id_venda 
    FROM entregas e
    JOIN itens_venda i ON e.id_item = i.id_item
    JOIN produtos p ON i.id_produto = p.id_produto
    ORDER BY e.id_entrega DESC
";
$lista = $mysqli->query($sql_lista);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - Entregas</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-painel">

    <header class="topbar">
        <span class="topbar-titulo">&#128666; MaxTech - Logística e Entregas</span>
        <div class="topbar-usuario">
            <a href="itens.php" style="color:white; margin-right: 10px;">Voltar p/ Itens</a> |
            <a href="logout.php">Sair</a>
        </div>
    </header>

    <main class="container painel-conteudo">

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem <?= htmlspecialchars($tipo_msg) ?>"><?= htmlspecialchars($mensagem) ?></p>
        <?php endif; ?>

        <section class="card-form">
            <h2>Registrar Entrega</h2>
            <form action="" method="POST">
                <div class="campo">
                    <label for="id_item">Produto Vendido (Item da Venda)</label>
                    <select id="id_item" name="id_item" required style="width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">-- Selecione o Item --</option>
                        <?php while ($i = $itens_opcoes->fetch_assoc()): ?>
                            <option value="<?= $i['id_item'] ?>">Item #<?= $i['id_item'] ?> (Pedido #<?= $i['id_venda'] ?>) - <?= htmlspecialchars($i['nome_produto']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-linha">
                    <div class="campo">
                        <label for="data_saida">Data de Saída</label>
                        <input type="datetime-local" id="data_saida" name="data_saida">
                    </div>
                    <div class="campo">
                        <label for="previsao_entrega">Previsão de Entrega</label>
                        <input type="datetime-local" id="previsao_entrega" name="previsao_entrega">
                    </div>
                </div>

                <div class="form-linha">
                    <div class="campo">
                        <label for="status_entrega">Status da Entrega</label>
                        <select id="status_entrega" name="status_entrega" style="width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="Em preparação">Em preparação</option>
                            <option value="Despachado">Despachado</option>
                            <option value="Em trânsito">Em trânsito</option>
                            <option value="Entregue">Entregue</option>
                            <option value="Extraviado">Extraviado</option>
                            <option value="Devolvido">Devolvido</option>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="rastreio">Código de Rastreio</label>
                        <input type="text" id="rastreio" name="rastreio" placeholder="Ex: BR123456789BR">
                    </div>
                </div>

                <div class="form-acoes">
                    <button type="submit" class="btn btn-principal">Registrar Logística</button>
                </div>
            </form>
        </section>

        <section class="card-tabela">
            <h2>Histórico de Entregas</h2>
            <?php if ($lista && $lista->num_rows > 0): ?>
                <table class="tabela-produtos">
                    <thead>
                        <tr>
                            <th>Produto (Pedido)</th>
                            <th>Saída</th>
                            <th>Previsão</th>
                            <th>Status</th>
                            <th>Rastreio</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($e = $lista->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($e['nome_produto']) ?> <small>(#<?= $e['id_venda'] ?>)</small></td>
                                <td><?= !empty($e['data_saida']) ? date('d/m/Y H:i', strtotime($e['data_saida'])) : '-' ?></td>
                                <td><?= !empty($e['previsao_entrega']) ? date('d/m/Y H:i', strtotime($e['previsao_entrega'])) : '-' ?></td>
                                <td><strong><?= htmlspecialchars($e['status_entrega']) ?></strong></td>
                                <td><?= htmlspecialchars($e['rastreio'] ?: 'N/A') ?></td>
                                <td class="acoes">
                                    <a href="?acao=excluir&id=<?= (int) $e['id_entrega'] ?>" class="btn-acao btn-excluir" onclick="return confirm('Excluir este registro logístico?')">Excluir</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="sem-registros">Nenhuma entrega registrada.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>