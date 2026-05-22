CREATE DATABASE IF NOT EXISTS loja_maxtech;
USE loja_maxtech;

-- tabela dos clientes
CREATE TABLE clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf_cnpj VARCHAR(20) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(100),
    endereco TEXT
);

-- tabela dos produtos
CREATE TABLE produtos (
    id_produto INT AUTO_INCREMENT PRIMARY KEY,
    nome_produto VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    estoque INT NOT NULL DEFAULT 0
);

-- tabela das vendas
CREATE TABLE vendas (
    id_venda INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    data_venda DATE NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    status_pagamento VARCHAR(50),

    FOREIGN KEY (id_cliente)
    REFERENCES clientes(id_cliente)
);

-- tabela dos itens da venda
CREATE TABLE itens_venda (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    id_venda INT NOT NULL,
    id_produto INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (id_venda)
    REFERENCES vendas(id_venda),

    FOREIGN KEY (id_produto)
    REFERENCES produtos(id_produto)
);

-- tabela das entregas
CREATE TABLE entregas (
    id_entrega INT AUTO_INCREMENT PRIMARY KEY,
    id_venda INT NOT NULL,
    data_saida DATE,
    previsao_entrega DATE,
    status_entrega VARCHAR(50),
    rastreio VARCHAR(100),

    FOREIGN KEY (id_venda)
    REFERENCES vendas(id_venda)
);

-- tabela das instalaçoes
CREATE TABLE instalacoes (
    id_instalacao INT AUTO_INCREMENT PRIMARY KEY,
    id_venda INT NOT NULL,
    tecnico_responsavel VARCHAR(100),
    data_agendada DATE,
    status_servico VARCHAR(50),
    observacoes TEXT,

    FOREIGN KEY (id_venda)
    REFERENCES vendas(id_venda)
);

