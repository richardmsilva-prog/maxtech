CREATE DATABASE IF NOT EXISTS maxtech;
USE maxtech;

CREATE TABLE clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cpf_cnpj VARCHAR(20) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(150),
    endereco VARCHAR(255)
);

CREATE TABLE produtos (
    id_produto INT AUTO_INCREMENT PRIMARY KEY,
    nome_produto VARCHAR(255) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10, 2) NOT NULL,
    estoque INT DEFAULT 0
);

CREATE TABLE vendas (
    id_venda INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    data_venda DATETIME DEFAULT CURRENT_TIMESTAMP,
    valor_total DECIMAL(10, 2) NOT NULL,
    status_pagamento ENUM('Pendente', 'Aprovado', 'Recusado', 'Cancelado', 'Estornado') DEFAULT 'Pendente',
    CONSTRAINT fk_venda_cliente FOREIGN KEY (id_cliente) 
        REFERENCES clientes(id_cliente) ON DELETE RESTRICT
);

CREATE TABLE itens_venda (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    id_venda INT NOT NULL,
    id_produto INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10, 2) NOT NULL,
    CONSTRAINT fk_item_venda FOREIGN KEY (id_venda) 
        REFERENCES vendas(id_venda) ON DELETE CASCADE,
    CONSTRAINT fk_item_produto FOREIGN KEY (id_produto) 
        REFERENCES produtos(id_produto) ON DELETE RESTRICT
);

CREATE TABLE entregas (
    id_entrega INT AUTO_INCREMENT PRIMARY KEY,
    id_item INT NOT NULL,
    data_saida DATETIME,
    previsao_entrega DATETIME,
    status_entrega ENUM('Em preparação', 'Despachado', 'Em trânsito', 'Entregue', 'Extraviado', 'Devolvido') DEFAULT 'Em preparação',
    rastreio VARCHAR(100),
    CONSTRAINT fk_entrega_item FOREIGN KEY (id_item) 
        REFERENCES itens_venda(id_item) ON DELETE CASCADE
);

CREATE TABLE instalacoes (
    id_instalacao INT AUTO_INCREMENT PRIMARY KEY,
    id_item INT NOT NULL,
    tecnico_responsavel VARCHAR(150),
    data_agendada DATETIME,
    status_servico ENUM('Agendado', 'Em andamento', 'Concluído', 'Cancelado', 'Reagendado') DEFAULT 'Agendado',
    observacoes TEXT,
    CONSTRAINT fk_instalacao_item FOREIGN KEY (id_item) 
        REFERENCES itens_venda(id_item) ON DELETE CASCADE
);
