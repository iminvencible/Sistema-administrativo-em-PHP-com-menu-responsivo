-- Exemplos de peças de moto; não contém dados pessoais de clientes.
USE oficina_motos;
START TRANSACTION;
INSERT INTO produtos (sku, nome, categoria, compatibilidade, preco) VALUES
('FREIO-001', 'Pastilha de freio dianteira', 'Freios', 'Honda CG 160', 65.90),
('OLEO-001', 'Óleo 20W50 1 litro', 'Lubrificantes', 'Motos 4 tempos', 32.50),
('REL-001', 'Kit relação', 'Transmissão', 'Yamaha Factor 150', 189.90);
INSERT INTO estoque (produto_id, quantidade, quantidade_minima)
SELECT id, CASE sku WHEN 'FREIO-001' THEN 12 WHEN 'OLEO-001' THEN 4 ELSE 0 END, 5
FROM produtos WHERE sku IN ('FREIO-001', 'OLEO-001', 'REL-001');
COMMIT;
