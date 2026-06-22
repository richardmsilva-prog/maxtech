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
    if ($mysqli->query("DELETE FROM instalacoes WHERE id_instalacao = $id")) {
        $mensagem = "Ordem de serviço excluída.";
        $tipo_msg = "sucesso";
    } else {
        $mensagem = "Erro ao excluir: " . $mysqli->error;
        $tipo_msg = "erro";
    }
}

// ── Ação: SALVAR ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_item = (int) ($_POST['id_item'] ?? 0);
    $tecnico = $mysqli->real_escape_string($_POST['tecnico_responsavel'] ?? '');
    $data_agendada = !empty($_POST['data_agendada']) ? "'" . $_POST['data_agendada'] . "'" : "NULL";
    $status_servico = $mysqli->real_escape_string($_POST['status_servico'] ?? 'Agendado');
    $observacoes = $mysqli->real_escape_string($_POST['observacoes'] ?? '');

    if ($id_item === 0) {
        $mensagem = "Selecione um item vendido para agendar a instalação.";
        $tipo_msg = "erro";
    } else {
        $sql = "INSERT INTO instalacoes (id_item, tecnico_responsavel, data_agendada, status_servico, observacoes) VALUES ($id_item, '$tecnico', $data_agendada, '$status_servico', '$observacoes')";
        if ($mysqli->query($sql)) {
            $mensagem = "Serviço agendado com sucesso.";
            $tipo_msg = "sucesso";
        } else {
            $mensagem = "Erro ao agendar: " . $mysqli->error;
            $tipo_msg = "erro";
        }
    }
}

// ── LISTAR Instalações ───────────
$sql_lista = "
    SELECT inst.*, p.nome_produto, c.nome AS nome_cliente
    FROM instalacoes inst
    JOIN itens_venda i ON inst.id_item = i.id_item
    JOIN vendas v ON i.id_venda = v.id_venda
    JOIN clientes c ON v.id_cliente = c.id_cliente
    JOIN produtos p ON i.id_produto = p.id_produto
    ORDER BY inst.data_agendada ASC
";
$lista = $mysqli->query($sql_lista);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - Instalações</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-painel">

    <header class="topbar">
        <span class="topbar-titulo">&#128736; MaxTech - Serviços Técnicos</span>
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
            <h2>Agendar Instalação</h2>
            <form action="" method="POST">
                <div class="campo">
                    <label for="id_item">Produto Vendido (Item da Venda)</label>
                    <select id="id_item" name="id_item" required style="width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">-- Selecione o Produto --</option>
                        <?php while ($i = $itens_opcoes->fetch_assoc()): ?>
                            <option value="<?= $i['id_item'] ?>">Pedido #<?= $i['id_venda'] ?> - <?= htmlspecialchars($i['nome_produto']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-linha">
                    <div class="campo">
                        <label for="tecnico_responsavel">Técnico Responsável</label>
                        <input type="text" id="tecnico_responsavel" name="tecnico_responsavel" placeholder="Nome do técnico">
                    </div>
                    <div class="campo campo-pequeno">
                        <label for="data_agendada">Data Agendada</label>
                        <input type="datetime-local" id="data_agendada" name="data_agendada">
                    </div>
                    <div class="campo campo-pequeno">
                        <label for="status_servico">Status</label>
                        <select id="status_servico" name="status_servico" style="width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="Agendado">Agendado</option>
                            <option value="Em andamento">Em andamento</option>
                            <option value="Concluído">Concluído</option>
                            <option value="Cancelado">Cancelado</option>
                            <option value="Reagendado">Reagendado</option>
                        </select>
                    </div>
                </div>

                <div class="campo">
                    <label for="observacoes">Observações (Endereço, referências, etc)</label>
                    <textarea id="observacoes" name="observacoes" rows="2" style="width: 100%; padding: 9px; border: 1px solid #cccccc; border-radius: 4px; font-family: inherit;"></textarea>
                </div>

                <div class="form-acoes">
                    <button type="submit" class="btn btn-principal">Salvar Ordem de Serviço</button>
                </div>
            </form>
        </section>

        <section class="card-tabela">
            <h2>Agenda de Instalações</h2>
            <?php if ($lista && $lista->num_rows > 0): ?>
                <table class="tabela-produtos">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Equipamento</th>
                            <th>Técnico</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($inst = $lista->fetch_assoc()): ?>
                            <tr>
                                <td><?= !empty($inst['data_agendada']) ? date('d/m/Y H:i', strtotime($inst['data_agendada'])) : 'A definir' ?></td>
                                <td><?= htmlspecialchars($inst['nome_cliente']) ?></td>
                                <td><?= htmlspecialchars($inst['nome_produto']) ?></td>
                                <td><?= htmlspecialchars($inst['tecnico_responsavel'] ?: 'Não designado') ?></td>
                                <td><strong><?= htmlspecialchars($inst['status_servico']) ?></strong></td>
                                <td class="acoes">
                                    <a href="?acao=excluir&id=<?= (int) $inst['id_instalacao'] ?>" class="btn-acao btn-excluir" onclick="return confirm('Excluir esta OS?')">Excluir</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="sem-registros">Nenhuma instalação agendada.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>