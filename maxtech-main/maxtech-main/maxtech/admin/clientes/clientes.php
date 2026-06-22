<?php

include 'conexao.php';

$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';
$mensagem    = "";
$tipo_msg    = "";
$cliente_editar = null;

// ── Ação: EXCLUIR ────────────────────────────────────────
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($mysqli->query("DELETE FROM clientes WHERE id_cliente = $id")) {
        $mensagem = "Cliente excluído com sucesso.";
        $tipo_msg = "sucesso";
    } else {
        if ($mysqli->errno === 1451) {
            $mensagem = "Aviso: Este cliente não pode ser excluído pois possui vendas registradas.";
        } else {
            $mensagem = "Erro ao excluir: " . $mysqli->error;
        }
        $tipo_msg = "erro";
    }
}

// ── Ação: CARREGAR DADOS PARA EDITAR ────────────────────
if (isset($_GET['acao']) && $_GET['acao'] === 'editar' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $res = $mysqli->query("SELECT * FROM clientes WHERE id_cliente = $id");
    if ($res && $res->num_rows === 1) {
        $cliente_editar = $res->fetch_assoc();
    }
}

// ── Ação: SALVAR (novo ou atualização) ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = $mysqli->real_escape_string(trim($_POST['nome'] ?? ''));
    $cpf_cnpj = $mysqli->real_escape_string(trim($_POST['cpf_cnpj'] ?? ''));
    $telefone = $mysqli->real_escape_string(trim($_POST['telefone'] ?? ''));
    $email    = $mysqli->real_escape_string(trim($_POST['email'] ?? ''));
    $endereco = $mysqli->real_escape_string(trim($_POST['endereco'] ?? ''));
    $id_post  = (int) ($_POST['id_cliente'] ?? 0);

    if (empty($nome) || empty($cpf_cnpj)) {
        $mensagem = "Os campos Nome e CPF/CNPJ são obrigatórios.";
        $tipo_msg = "erro";
    } else {
        if ($id_post > 0) {
            // ATUALIZAR
            $sql = "UPDATE clientes SET nome='$nome', cpf_cnpj='$cpf_cnpj', telefone='$telefone', email='$email', endereco='$endereco' WHERE id_cliente=$id_post";
            if ($mysqli->query($sql)) {
                $mensagem = "Cliente atualizado com sucesso.";
                $tipo_msg = "sucesso";
            } else {
                $mensagem = ($mysqli->errno === 1062) ? "Erro: Este CPF/CNPJ já está cadastrado." : "Erro: " . $mysqli->error;
                $tipo_msg = "erro";
            }
        } else {
            // INSERIR
            $sql = "INSERT INTO clientes (nome, cpf_cnpj, telefone, email, endereco) VALUES ('$nome', '$cpf_cnpj', '$telefone', '$email', '$endereco')";
            if ($mysqli->query($sql)) {
                $mensagem = "Cliente cadastrado com sucesso.";
                $tipo_msg = "sucesso";
            } else {
                $mensagem = ($mysqli->errno === 1062) ? "Erro: Este CPF/CNPJ já está cadastrado." : "Erro: " . $mysqli->error;
                $tipo_msg = "erro";
            }
        }
        if($tipo_msg === 'sucesso') $cliente_editar = null;
    }
}

// ── LISTAR todos os clientes ─────────────────────────────
$lista = $mysqli->query("SELECT * FROM clientes ORDER BY nome ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - Clientes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-painel">

    <header class="topbar">
        <span class="topbar-titulo">&#128101; MaxTech - Clientes</span>
        <div class="topbar-usuario">
            Usuário: <strong><?= htmlspecialchars($nomeUsuario) ?></strong>
            &nbsp;|&nbsp;
            <a href="produtos.php" style="color:white; margin-right: 10px;">Ir para Produtos</a> |
            <a href="vendas.php" style="color:white; margin-right: 10px; margin-left:10px;">Ir para Vendas</a> |
            <a href="logout.php">Sair</a>
        </div>
    </header>

    <main class="container painel-conteudo">

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem <?= htmlspecialchars($tipo_msg) ?>"><?= htmlspecialchars($mensagem) ?></p>
        <?php endif; ?>

        <section class="card-form">
            <h2><?= $cliente_editar ? 'Editar Cliente' : 'Cadastrar Cliente' ?></h2>
            <form action="" method="POST">
                <?php if ($cliente_editar): ?>
                    <input type="hidden" name="id_cliente" value="<?= (int) $cliente_editar['id_cliente'] ?>">
                <?php endif; ?>

                <div class="form-linha">
                    <div class="campo">
                        <label for="nome">Nome Completo / Razão Social</label>
                        <input type="text" id="nome" name="nome" required value="<?= htmlspecialchars($cliente_editar['nome'] ?? '') ?>">
                    </div>
                    <div class="campo campo-pequeno">
                        <label for="cpf_cnpj">CPF / CNPJ</label>
                        <input type="text" id="cpf_cnpj" name="cpf_cnpj" required value="<?= htmlspecialchars($cliente_editar['cpf_cnpj'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-linha">
                    <div class="campo campo-pequeno">
                        <label for="telefone">Telefone</label>
                        <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($cliente_editar['telefone'] ?? '') ?>">
                    </div>
                    <div class="campo">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($cliente_editar['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="campo">
                    <label for="endereco">Endereço Completo</label>
                    <input type="text" id="endereco" name="endereco" value="<?= htmlspecialchars($cliente_editar['endereco'] ?? '') ?>">
                </div>

                <div class="form-acoes">
                    <button type="submit" class="btn btn-principal"><?= $cliente_editar ? 'Salvar' : 'Cadastrar' ?></button>
                    <?php if ($cliente_editar): ?><a href="clientes.php" class="btn btn-cancelar">Cancelar</a><?php endif; ?>
                </div>
            </form>
        </section>

        <section class="card-tabela">
            <h2>Clientes Cadastrados</h2>
            <?php if ($lista && $lista->num_rows > 0): ?>
                <table class="tabela-produtos">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Documento</th>
                            <th>Contato</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($c = $lista->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                                <td><?= htmlspecialchars($c['cpf_cnpj']) ?></td>
                                <td><?= htmlspecialchars($c['telefone']) ?><br><small><?= htmlspecialchars($c['email']) ?></small></td>
                                <td class="acoes">
                                    <a href="?acao=editar&id=<?= (int) $c['id_cliente'] ?>" class="btn-acao btn-editar">Editar</a>
                                    <a href="?acao=excluir&id=<?= (int) $c['id_cliente'] ?>" class="btn-acao btn-excluir" onclick="return confirm('Excluir cliente?')">Excluir</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="sem-registros">Nenhum cliente cadastrado.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>