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
    data_venda TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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

INSERT INTO produtos (nome_produto, descricao, preco, estoque, imagem) VALUES
('Lava e Seca Samsung 11kg Inverter', 'Motor Digital Inverter com economia de energia e lavagem silenciosa. Ciclos rápidos e eficientes.', 3299.00, 12, '../assets/img/produtos/lava-seca-samsung-11kg.jpg'),
('Geladeira Duplex 294L Midea', 'Espaçosa e econômica, com prateleiras de vidro temperado e design compacto ideal para sua cozinha.', 2150.00, 15, '../assets/img/produtos/geladeira-midea-294l.jpg'),
('Micro-ondas Mondial 34L Espelhado', 'Design premium com porta espelhada, 34 litros de capacidade e funções pré-programadas.', 749.90, 20, '../assets/img/produtos/microondas-mondial-34l.jpg'),
('Lava-louças Brastemp 15 Serviços', 'Tecnologia Power Clean para sujeiras difíceis, sem precisar de pré-lavagem. Acabamento em inox.', 4199.00, 8, '../assets/img/produtos/lava-loucas-brastemp-15.jpg'),
('Cooktop Itatiaia Essencial 5 Bocas', 'Mesa de vidro temperado preto, acendimento superautomático e grades individuais esmaltadas.', 489.90, 30, '../assets/img/produtos/cooktop-itatiaia-5b.jpg'),
('Frigobar Midea 124L com Gaveta', 'Super prático, com gaveta transparente, prateleiras de vidro e termostato ajustável.', 999.00, 18, '../assets/img/produtos/frigobar-midea-124l.jpg'),
('Coifa de Parede Fogatti Slim 80cm', 'Design moderno em vidro curvo e inox, dupla função (exaustor e depurador) e iluminação em LED.', 859.50, 10, '../assets/img/produtos/coifa-fogatti-80cm.jpg'),
('Máquina de Lavar Electrolux 13kg', 'Sistema Jet&Clean que dilui o sabão antes do contato com a roupa. Cesto em inox resistente.', 1989.00, 25, '../assets/img/produtos/lavadora-electrolux-13kg.jpg');