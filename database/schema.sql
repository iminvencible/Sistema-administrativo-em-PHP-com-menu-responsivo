-- MySQL 8.0+ ou MariaDB 10.6+. Execute em um banco vazio.
CREATE DATABASE IF NOT EXISTS oficina_motos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oficina_motos;

CREATE TABLE clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf CHAR(11) NOT NULL UNIQUE,
    telefone VARCHAR(20) NOT NULL,
    email VARCHAR(120) NOT NULL DEFAULT '',
    modelo_moto VARCHAR(80) NOT NULL,
    placa_moto VARCHAR(7) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE produtos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(40) NOT NULL UNIQUE,
    nome VARCHAR(120) NOT NULL,
    categoria VARCHAR(60) NOT NULL,
    compatibilidade VARCHAR(100) NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (preco > 0)
) ENGINE=InnoDB;

CREATE TABLE estoque (
    produto_id INT UNSIGNED PRIMARY KEY,
    quantidade INT NOT NULL DEFAULT 0,
    quantidade_minima INT NOT NULL DEFAULT 0,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    CHECK (quantidade >= 0 AND quantidade_minima >= 0)
) ENGINE=InnoDB;

CREATE TABLE ordens_servico (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    modelo_moto VARCHAR(80) NOT NULL,
    placa_moto VARCHAR(7) NOT NULL,
    descricao VARCHAR(500) NOT NULL,
    status ENUM('aberta', 'em_andamento', 'concluida') NOT NULL DEFAULT 'aberta',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
) ENGINE=InnoDB;

CREATE TABLE vendas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    ordem_servico_id INT UNSIGNED NULL UNIQUE,
    -- Histórico preservado mesmo após edição do cadastro do cliente.
    cliente_nome VARCHAR(100) NOT NULL,
    cliente_cpf CHAR(11) NOT NULL,
    modelo_moto VARCHAR(80) NOT NULL,
    placa_moto VARCHAR(7) NOT NULL,
    servico VARCHAR(500) NOT NULL DEFAULT '',
    mao_obra DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (ordem_servico_id) REFERENCES ordens_servico(id),
    INDEX (criado_em),
    CHECK (mao_obra >= 0 AND total > 0)
) ENGINE=InnoDB;

CREATE TABLE venda_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venda_id INT UNSIGNED NOT NULL,
    produto_id INT UNSIGNED NOT NULL,
    produto_nome VARCHAR(120) NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    CHECK (quantidade > 0 AND preco_unitario > 0)
) ENGINE=InnoDB;

CREATE TABLE movimentacoes_estoque (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    produto_id INT UNSIGNED NOT NULL,
    venda_id INT UNSIGNED NULL,
    tipo ENUM('entrada', 'saida', 'venda') NOT NULL,
    quantidade INT NOT NULL,
    motivo VARCHAR(200) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (venda_id) REFERENCES vendas(id),
    CHECK (quantidade > 0)
) ENGINE=InnoDB;

CREATE TABLE sessoes (
    chave_sessao CHAR(64) PRIMARY KEY,
    iniciada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultima_atividade TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    encerrada_em TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE configuracoes (
    chave_sessao CHAR(64) PRIMARY KEY,
    tema ENUM('claro', 'escuro') NOT NULL DEFAULT 'claro',
    fonte_ampliada BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (chave_sessao) REFERENCES sessoes(chave_sessao)
) ENGINE=InnoDB;
