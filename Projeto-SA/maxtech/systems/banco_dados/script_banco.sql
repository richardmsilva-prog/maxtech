CREATE DATABASE IF NOT EXISTS maxtech_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE maxtech_db;

-- 1. TABELA DE CLIENTES / USUÁRIOS (Com UNIQUE no telefone e no usuário)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE, -- Login único
    senha VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NULL,
    endereco VARCHAR(255) NULL
) ENGINE=InnoDB;

-- 2. TABELA DE PRODUTOS
CREATE TABLE IF NOT EXISTS produtos (
    id_produto INT AUTO_INCREMENT PRIMARY KEY,
    nome_produto VARCHAR(150) NOT NULL,
    descricao VARCHAR(150) NOT NULL,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estoque INT NOT NULL DEFAULT 0,
    imagem VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- 3. TABELA DE VENDAS
CREATE TABLE IF NOT EXISTS vendas (
    id_venda INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    valor_total TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valor_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_vendas_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. TABELA DE ITENS DA VENDA
CREATE TABLE IF NOT EXISTS itens_venda (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    id_venda INT NOT NULL,
    id_produto INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_itens_vendas FOREIGN KEY (id_venda) REFERENCES vendas(id_venda) ON DELETE CASCADE,
    CONSTRAINT fk_itens_produtos FOREIGN KEY (id_produto) REFERENCES produtos(id_produto) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 5. TABELA DE ENTREGAS
CREATE TABLE IF NOT EXISTS entregas (
    id_entrega INT AUTO_INCREMENT PRIMARY KEY,
    id_venda INT NOT NULL,
    status_entrega ENUM('Processando', 'Em Rota de Entrega', 'Entregue') NOT NULL DEFAULT 'Processando',
    previsao_entrega DATE NULL,
    CONSTRAINT fk_entregas_vendas FOREIGN KEY (id_venda) REFERENCES vendas(id_venda) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. TABELA DE INSTALAÇÕES
CREATE TABLE IF NOT EXISTS instalacoes (
    id_instalacao INT AUTO_INCREMENT PRIMARY KEY,
    id_venda INT NOT NULL,
    status_instalacao ENUM('Aguardando Entrega', 'Agendada', 'Concluída') NOT NULL DEFAULT 'Aguardando Entrega',
    data_instalacao DATETIME NULL,
    tecnico_responsavel VARCHAR(100) NULL,
    CONSTRAINT fk_instalacoes_vendas FOREIGN KEY (id_venda) REFERENCES vendas(id_venda) ON DELETE CASCADE
) ENGINE=InnoDB;