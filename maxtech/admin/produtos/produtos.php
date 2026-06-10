<?php

include 'conexao.php'; // Lembre-se de colocar 'maxtech' no seu conexao.php

$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';
$mensagem    = "";
$tipo_msg    = "";
$produto_editar = null;

// ── Ação: EXCLUIR ────────────────────────────────────────
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    
    if ($mysqli->query("DELETE FROM produtos WHERE id_produto = $id")) {
        $mensagem = "Produto excluído com sucesso.";
        $tipo_msg = "sucesso";
    } else {
        // Verifica se o erro é de restrição de chave estrangeira (Produto já vendido)
        if ($mysqli->errno === 1451) {
            $mensagem = "Aviso: Este produto não pode ser excluído pois já está vinculado a uma ou mais vendas.";
        } else {
            $mensagem = "Erro ao excluir: " . $mysqli->error;
        }
        $tipo_msg = "erro";
    }
}

// ── Ação: CARREGAR DADOS PARA EDITAR ────────────────────
if (isset($_GET['acao']) && $_GET['acao'] === 'editar' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $res = $mysqli->query("SELECT * FROM produtos WHERE id_produto = $id");
    if ($res && $res->num_rows === 1) {
        $produto_editar = $res->fetch_assoc();
    }
}

// ── Ação: SALVAR (novo ou atualização) ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = $mysqli->real_escape_string(trim($_POST['nome_produto'] ?? ''));
    $descricao = $mysqli->real_escape_string(trim($_POST['descricao'] ?? ''));
    $estoque   = (int) ($_POST['estoque'] ?? 0);
    $preco     = number_format((float) str_replace(',', '.', $_POST['preco'] ?? '0'), 2, '.', '');
    $id_post   = (int) ($_POST['id_produto'] ?? 0);

    if (empty($nome)) {
        $mensagem = "O nome do produto é obrigatório.";
        $tipo_msg = "erro";
    } elseif ($estoque < 0) {
        $mensagem = "O estoque não pode ser negativo.";
        $tipo_msg = "erro";
    } elseif ($preco < 0) {
        $mensagem = "O preço não pode ser negativo.";
        $tipo_msg = "erro";
    } else {
        if ($id_post > 0) {
            // ATUALIZAR
            $sql = "UPDATE produtos SET nome_produto='$nome', descricao='$descricao', estoque=$estoque, preco=$preco WHERE id_produto=$id_post";
            if ($mysqli->query($sql)) {
                $mensagem = "Produto atualizado com sucesso.";
                $tipo_msg = "sucesso";
            } else {
                $mensagem = "Erro ao atualizar: " . $mysqli->error;
                $tipo_msg = "erro";
            }
        } else {
            // INSERIR
            $sql = "INSERT INTO produtos (nome_produto, descricao, preco, estoque) VALUES ('$nome', '$descricao', $preco, $estoque)";
            if ($mysqli->query($sql)) {
                $mensagem = "Produto cadastrado com sucesso.";
                $tipo_msg = "sucesso";
            } else {
                $mensagem = "Erro ao cadastrar: " . $mysqli->error;
                $tipo_msg = "erro";
            }
        }
        $produto_editar = null;
    }
}

// ── LISTAR todos os produtos ─────────────────────────────
$lista = $mysqli->query("SELECT * FROM produtos ORDER BY id_produto ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - Produtos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-painel">

    <header class="topbar">
        <span class="topbar-titulo">&#128230; MaxTech - Produtos</span>
        <div class="topbar-usuario">
            Usuário: <strong><?= htmlspecialchars($nomeUsuario) ?></strong>
            &nbsp;|&nbsp;
            <a href="logout.php">Sair</a>
        </div>
    </header>

    <main class="container painel-conteudo">

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem <?= htmlspecialchars($tipo_msg) ?>">
                <?= htmlspecialchars($mensagem) ?>
            </p>
        <?php endif; ?>

        <section class="card-form">
            <h2><?= $produto_editar ? 'Editar Produto' : 'Cadastrar Produto' ?></h2>

            <form action="" method="POST">

                <?php if ($produto_editar): ?>
                    <input type="hidden" name="id_produto" value="<?= (int) $produto_editar['id_produto'] ?>">
                <?php endif; ?>

                <div class="form-linha">
                    <div class="campo">
                        <label for="nome_produto">Nome do produto</label>
                        <input type="text" id="nome_produto" name="nome_produto" required
                               value="<?= htmlspecialchars($produto_editar['nome_produto'] ?? '') ?>">
                    </div>
                    <div class="campo campo-pequeno">
                        <label for="estoque">Estoque</label>
                        <input type="number" id="estoque" name="estoque" min="0" required
                               value="<?= htmlspecialchars($produto_editar['estoque'] ?? '0') ?>">
                    </div>
                    <div class="campo campo-pequeno">
                        <label for="preco">Preço (R$)</label>
                        <input type="number" id="preco" name="preco" min="0" step="0.01" required
                               value="<?= htmlspecialchars($produto_editar['preco'] ?? '0.00') ?>">
                    </div>
                </div>

                <div class="campo">
                    <label for="descricao">Descrição do Produto (Opcional)</label>
                    <textarea id="descricao" name="descricao" rows="3" style="width: 100%; padding: 9px; border: 1px solid #cccccc; border-radius: 4px; font-family: inherit;"><?= htmlspecialchars($produto_editar['descricao'] ?? '') ?></textarea>
                </div>

                <div class="form-acoes">
                    <button type="submit" class="btn btn-principal">
                        <?= $produto_editar ? 'Salvar alterações' : 'Cadastrar produto' ?>
                    </button>
                    <?php if ($produto_editar): ?>
                        <a href="produtos.php" class="btn btn-cancelar">Cancelar</a>
                    <?php endif; ?>
                </div>

            </form>
        </section>

        <section class="card-tabela">
            <h2>Produtos cadastrados</h2>

            <?php if ($lista && $lista->num_rows > 0): ?>
                <table class="tabela-produtos">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Estoque</th>
                            <th>Preço</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $lista->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int) $p['id_produto'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($p['nome_produto']) ?></strong><br>
                                    <small style="color: #666;"><?= htmlspecialchars($p['descricao']) ?></small>
                                </td>
                                <td><?= (int) $p['estoque'] ?></td>
                                <td>R$ <?= number_format((float) $p['preco'], 2, ',', '.') ?></td>
                                <td class="acoes">
                                    <a href="?acao=editar&id=<?= (int) $p['id_produto'] ?>"
                                       class="btn-acao btn-editar">Editar</a>
                                    <a href="?acao=excluir&id=<?= (int) $p['id_produto'] ?>"
                                       class="btn-acao btn-excluir"
                                       onclick="return confirm('Excluir o produto \'<?= htmlspecialchars(addslashes($p['nome_produto'])) ?>\'?')">
                                       Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="sem-registros">Nenhum produto cadastrado ainda.</p>
            <?php endif; ?>
        </section>

    </main>
</body>
</html>