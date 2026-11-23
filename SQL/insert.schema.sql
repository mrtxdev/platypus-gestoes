
USE restaurant_management;

SET NAMES utf8mb4;

-- ============================================================
-- 1. USUÁRIOS
-- Senha demonstrativa: trocar123
-- Hash ilustrativo: substituir por hash bcrypt real da aplicação.
-- ============================================================

INSERT INTO users (name, email, password_hash, role)
SELECT 'Administrador', 'admin@platypus.com',
       '$2y$10$EXEMPLO_HASH_BCRYPT_SUBSTITUIR',
       'ADMIN'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'admin@platypus.com'
);

INSERT INTO users (name, email, password_hash, role)
SELECT 'Cozinheiro', 'cozinha@platypus.com',
       '$2y$10$EXEMPLO_HASH_BCRYPT_SUBSTITUIR',
       'KITCHEN'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'cozinha@platypus.com'
);

INSERT INTO users (name, email, password_hash, role)
SELECT 'Atendente', 'atendente@platypus.com',
       '$2y$10$EXEMPLO_HASH_BCRYPT_SUBSTITUIR',
       'ATTENDANT'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'atendente@platypus.com'
);

-- ============================================================
-- 2. CATEGORIAS
-- ============================================================

INSERT INTO categories (name, description)
SELECT dados.name, dados.description
FROM (
    SELECT 'Hambúrgueres' name, 'Hambúrgueres artesanais' description
    UNION ALL SELECT 'Pizzas', 'Pizzas tradicionais e especiais'
    UNION ALL SELECT 'Bebidas', 'Bebidas geladas'
    UNION ALL SELECT 'Sobremesas', 'Doces e sobremesas'
    UNION ALL SELECT 'Porções', 'Porções para compartilhar'
    UNION ALL SELECT 'Massas', 'Massas italianas'
    UNION ALL SELECT 'Saladas', 'Saladas frescas'
    UNION ALL SELECT 'Combos', 'Combos promocionais'
) dados
WHERE NOT EXISTS (
    SELECT 1 FROM categories c WHERE c.name = dados.name
);

-- ============================================================
-- 3. MESAS
-- ============================================================

INSERT INTO dining_tables (table_number, qr_token)
SELECT dados.numero, UUID()
FROM (
    SELECT '01' numero UNION ALL
    SELECT '02' UNION ALL
    SELECT '03' UNION ALL
    SELECT '04' UNION ALL
    SELECT '05' UNION ALL
    SELECT '06' UNION ALL
    SELECT '07' UNION ALL
    SELECT '08' UNION ALL
    SELECT '09' UNION ALL
    SELECT '10'
) dados
WHERE NOT EXISTS (
    SELECT 1 FROM dining_tables t
    WHERE t.table_number = dados.numero
);

-- ============================================================
-- 4. INGREDIENTES
-- ============================================================

INSERT INTO ingredients
(name, unit, current_quantity, minimum_quantity)
VALUES
('Pão de hambúrguer', 'un', 100, 20),
('Carne bovina', 'kg', 30, 5),
('Queijo cheddar', 'kg', 10, 2),
('Alface', 'un', 30, 5),
('Tomate', 'kg', 15, 3),
('Cebola', 'kg', 10, 2),
('Bacon', 'kg', 8, 2),
('Batata', 'kg', 40, 10),
('Óleo', 'l', 20, 5),
('Mussarela', 'kg', 20, 5),
('Molho de tomate', 'kg', 15, 3),
('Farinha de trigo', 'kg', 30, 5),
('Fermento', 'kg', 2, 0.5),
('Presunto', 'kg', 8, 2),
('Frango', 'kg', 20, 5),
('Creme de leite', 'l', 10, 2),
('Macarrão', 'kg', 15, 3),
('Chocolate', 'kg', 8, 2),
('Leite condensado', 'un', 30, 5),
('Morango', 'kg', 5, 1),
('Refrigerante lata', 'un', 100, 20),
('Água mineral', 'un', 80, 15),
('Suco natural', 'l', 20, 5),
('Orégano', 'kg', 1, 0.2),
('Sal', 'kg', 5, 1)
ON DUPLICATE KEY UPDATE
name = VALUES(name);

-- ============================================================
-- 5. PRODUTOS
-- ============================================================

INSERT INTO products
(category_id, name, description, price, stock_quantity)
SELECT c.id, p.name, p.description, p.price, p.stock
FROM (
    SELECT 'Hambúrgueres' categoria, 'Hambúrguer Clássico' name,
           'Pão, carne, queijo e salada' description, 25.90 price, 40 stock
    UNION ALL SELECT 'Hambúrgueres', 'X-Bacon', 'Hambúrguer com bacon', 29.90, 30
    UNION ALL SELECT 'Hambúrgueres', 'X-Salada', 'Hambúrguer com salada', 27.90, 30
    UNION ALL SELECT 'Hambúrgueres', 'X-Tudo', 'Hambúrguer completo', 35.90, 25
    UNION ALL SELECT 'Hambúrgueres', 'Cheddar Burger', 'Hambúrguer com cheddar', 31.90, 25

    UNION ALL SELECT 'Pizzas', 'Pizza Margherita', 'Mussarela e tomate', 45.00, 15
    UNION ALL SELECT 'Pizzas', 'Pizza Calabresa', 'Calabresa e cebola', 49.00, 15
    UNION ALL SELECT 'Pizzas', 'Pizza Frango', 'Frango com queijo', 52.00, 15
    UNION ALL SELECT 'Pizzas', 'Pizza Quatro Queijos', 'Mix de queijos', 58.00, 12
    UNION ALL SELECT 'Pizzas', 'Pizza Bacon', 'Pizza especial de bacon', 55.00, 12

    UNION ALL SELECT 'Bebidas', 'Coca-Cola Lata', '350ml', 6.00, 100
    UNION ALL SELECT 'Bebidas', 'Guaraná Lata', '350ml', 6.00, 100
    UNION ALL SELECT 'Bebidas', 'Água Mineral', '500ml', 4.00, 80
    UNION ALL SELECT 'Bebidas', 'Suco de Laranja', 'Natural 300ml', 9.00, 30
    UNION ALL SELECT 'Bebidas', 'Suco de Morango', 'Natural 300ml', 10.00, 25

    UNION ALL SELECT 'Sobremesas', 'Brownie', 'Brownie de chocolate', 12.00, 20
    UNION ALL SELECT 'Sobremesas', 'Petit Gateau', 'Bolo com chocolate cremoso', 18.00, 15
    UNION ALL SELECT 'Sobremesas', 'Pudim', 'Pudim de leite', 10.00, 20
    UNION ALL SELECT 'Sobremesas', 'Mousse de Chocolate', 'Mousse artesanal', 12.00, 20

    UNION ALL SELECT 'Porções', 'Batata Frita', 'Porção de batata', 22.00, 30
    UNION ALL SELECT 'Porções', 'Batata com Cheddar', 'Batata e cheddar', 29.00, 20
    UNION ALL SELECT 'Porções', 'Onion Rings', 'Anéis de cebola', 25.00, 20
    UNION ALL SELECT 'Porções', 'Frango Frito', 'Porção de frango', 32.00, 20

    UNION ALL SELECT 'Massas', 'Espaguete Bolonhesa', 'Massa com molho bolonhesa', 32.00, 20
    UNION ALL SELECT 'Massas', 'Macarrão ao Molho Branco', 'Massa cremosa', 34.00, 20

    UNION ALL SELECT 'Saladas', 'Salada Caesar', 'Salada com molho especial', 25.00, 20
    UNION ALL SELECT 'Saladas', 'Salada Tropical', 'Salada com frutas', 23.00, 20

    UNION ALL SELECT 'Combos', 'Combo Clássico', 'Hambúrguer e refrigerante', 29.90, 30
    UNION ALL SELECT 'Combos', 'Combo Família', 'Pizza e bebidas', 79.90, 15
    UNION ALL SELECT 'Combos', 'Combo Casal', 'Dois hambúrgueres e bebidas', 59.90, 20
) p
INNER JOIN categories c ON c.name = p.categoria
WHERE NOT EXISTS (
    SELECT 1 FROM products existing
    WHERE existing.name = p.name
);

-- ============================================================
-- 6. RECEITAS
-- ============================================================

INSERT INTO recipes
(product_id, ingredient_id, quantity, is_optional)
SELECT p.id, i.id, r.quantity, r.optional
FROM (
    SELECT 'Hambúrguer Clássico' produto, 'Pão de hambúrguer' ingrediente, 1 quantity, 0 optional
    UNION ALL SELECT 'Hambúrguer Clássico', 'Carne bovina', 0.150, 0
    UNION ALL SELECT 'Hambúrguer Clássico', 'Queijo cheddar', 0.030, 0
    UNION ALL SELECT 'Hambúrguer Clássico', 'Alface', 0.100, 0
    UNION ALL SELECT 'Hambúrguer Clássico', 'Tomate', 0.050, 0
    UNION ALL SELECT 'X-Bacon', 'Pão de hambúrguer', 1, 0
    UNION ALL SELECT 'X-Bacon', 'Carne bovina', 0.150, 0
    UNION ALL SELECT 'X-Bacon', 'Bacon', 0.040, 0
    UNION ALL SELECT 'X-Bacon', 'Queijo cheddar', 0.030, 0
    UNION ALL SELECT 'Batata Frita', 'Batata', 0.250, 0
    UNION ALL SELECT 'Batata Frita', 'Óleo', 0.030, 0
    UNION ALL SELECT 'Pizza Margherita', 'Farinha de trigo', 0.200, 0
    UNION ALL SELECT 'Pizza Margherita', 'Molho de tomate', 0.100, 0
    UNION ALL SELECT 'Pizza Margherita', 'Mussarela', 0.200, 0
    UNION ALL SELECT 'Pizza Margherita', 'Tomate', 0.100, 0
    UNION ALL SELECT 'Pizza Margherita', 'Orégano', 0.002, 1
    UNION ALL SELECT 'Espaguete Bolonhesa', 'Macarrão', 0.200, 0
    UNION ALL SELECT 'Espaguete Bolonhesa', 'Carne bovina', 0.100, 0
    UNION ALL SELECT 'Espaguete Bolonhesa', 'Molho de tomate', 0.100, 0
) r
INNER JOIN products p ON p.name = r.produto
INNER JOIN ingredients i ON i.name = r.ingrediente
WHERE NOT EXISTS (
    SELECT 1 FROM recipes x
    WHERE x.product_id = p.id
      AND x.ingredient_id = i.id
);

-- ============================================================
-- 7. MOVIMENTAÇÕES DE ESTOQUE
-- ============================================================

INSERT INTO inventory_movements
(ingredient_id, user_id, type, quantity, reason)
SELECT i.id, u.id, 'IN', 10, 'Estoque inicial'
FROM ingredients i
CROSS JOIN users u
WHERE u.email = 'admin@platypus.com'
AND i.name IN ('Carne bovina', 'Queijo cheddar', 'Batata')
AND NOT EXISTS (
    SELECT 1 FROM inventory_movements m
    WHERE m.ingredient_id = i.id
      AND m.reason = 'Estoque inicial'
);

INSERT INTO product_stock_movements
(product_id, user_id, type, quantity, reason)
SELECT p.id, u.id, 'IN', 10, 'Cadastro de estoque inicial'
FROM products p
CROSS JOIN users u
WHERE u.email = 'admin@platypus.com'
AND p.name IN ('Hambúrguer Clássico', 'Pizza Margherita', 'Batata Frita')
AND NOT EXISTS (
    SELECT 1 FROM product_stock_movements m
    WHERE m.product_id = p.id
      AND m.reason = 'Cadastro de estoque inicial'
);

-- ============================================================
-- 8. CARRINHOS
-- ============================================================

INSERT INTO carts (table_id, status)
SELECT t.id, 'OPEN'
FROM dining_tables t
WHERE t.table_number IN ('01', '02')
AND NOT EXISTS (
    SELECT 1 FROM carts c
    WHERE c.table_id = t.id AND c.status = 'OPEN'
);

-- ============================================================
-- 9. ITENS DOS CARRINHOS
-- ============================================================

INSERT INTO cart_items
(cart_id, product_id, quantity, unit_price)
SELECT c.id, p.id, 1, p.price
FROM carts c
INNER JOIN dining_tables t ON t.id = c.table_id
INNER JOIN products p ON p.name = 'Hambúrguer Clássico'
WHERE t.table_number = '01'
AND c.status = 'OPEN'
AND NOT EXISTS (
    SELECT 1 FROM cart_items ci WHERE ci.cart_id = c.id
);

INSERT INTO cart_items
(cart_id, product_id, quantity, unit_price)
SELECT c.id, p.id, 2, p.price
FROM carts c
INNER JOIN dining_tables t ON t.id = c.table_id
INNER JOIN products p ON p.name = 'Coca-Cola Lata'
WHERE t.table_number = '02'
AND c.status = 'OPEN'
AND NOT EXISTS (
    SELECT 1 FROM cart_items ci WHERE ci.cart_id = c.id
);

-- ============================================================
-- 10. CAIXA
-- ============================================================

INSERT INTO cash_registers
(opened_by_user_id, opening_amount, status)
SELECT u.id, 200.00, 'OPEN'
FROM users u
WHERE u.email = 'admin@platypus.com'
AND NOT EXISTS (
    SELECT 1 FROM cash_registers WHERE status = 'OPEN'
);

INSERT INTO cash_registers
(opened_by_user_id, closed_by_user_id, opening_amount,
 closing_amount, opened_at, closed_at, status)
SELECT u.id, u.id, 200.00, 850.00,
       NOW() - INTERVAL 2 DAY,
       NOW() - INTERVAL 2 DAY + INTERVAL 8 HOUR,
       'CLOSED'
FROM users u
WHERE u.email = 'admin@platypus.com'
AND NOT EXISTS (
    SELECT 1 FROM cash_registers WHERE status = 'CLOSED'
);

-- ============================================================
-- 11. PEDIDOS
-- ============================================================

INSERT INTO orders
(table_id, user_id, status, total_amount, notes, created_at)
SELECT t.id, u.id, d.status, d.total, d.notes, d.created
FROM (
    SELECT '03' mesa, 'PENDING' status, 31.90 total,
           'Sem cebola' notes, NOW() - INTERVAL 20 MINUTE created
    UNION ALL
    SELECT '04', 'PREPARING', 49.00,
           'Bem assada', NOW() - INTERVAL 35 MINUTE
    UNION ALL
    SELECT '05', 'READY', 29.90,
           'Entregar na mesa', NOW() - INTERVAL 45 MINUTE
    UNION ALL
    SELECT '06', 'DELIVERED', 22.00,
           NULL, NOW() - INTERVAL 2 HOUR
    UNION ALL
    SELECT '07', 'CANCELLED', 18.00,
           'Pedido cancelado pelo cliente', NOW() - INTERVAL 3 HOUR
) d
INNER JOIN dining_tables t ON t.table_number = d.mesa
INNER JOIN users u ON u.email = 'atendente@platypus.com'
WHERE NOT EXISTS (
    SELECT 1 FROM orders o
    WHERE o.table_id = t.id
      AND o.notes <=> d.notes
      AND o.created_at >= NOW() - INTERVAL 1 DAY
);

-- ============================================================
-- 12. ITENS DOS PEDIDOS
-- ============================================================

INSERT INTO order_items
(order_id, product_id, product_name, quantity, unit_price, notes)
SELECT o.id, p.id, p.name, 1, p.price, o.notes
FROM orders o
INNER JOIN products p ON (
    (o.total_amount = 31.90 AND p.name = 'Cheddar Burger') OR
    (o.total_amount = 49.00 AND p.name = 'Pizza Calabresa') OR
    (o.total_amount = 29.90 AND p.name = 'X-Bacon') OR
    (o.total_amount = 22.00 AND p.name = 'Batata Frita') OR
    (o.total_amount = 18.00 AND p.name = 'Petit Gateau')
)
WHERE o.created_at >= NOW() - INTERVAL 1 DAY
AND NOT EXISTS (
    SELECT 1 FROM order_items oi WHERE oi.order_id = o.id
);

-- ============================================================
-- 13. HISTÓRICO DOS PEDIDOS
-- ============================================================

INSERT INTO order_status_history
(order_id, user_id, previous_status, new_status, notes)
SELECT o.id, u.id, NULL, o.status, 'Pedido criado'
FROM orders o
CROSS JOIN users u
WHERE u.email = 'atendente@platypus.com'
AND o.created_at >= NOW() - INTERVAL 1 DAY
AND NOT EXISTS (
    SELECT 1 FROM order_status_history h
    WHERE h.order_id = o.id
);

-- ============================================================
-- 14. DESPESAS
-- ============================================================

INSERT INTO expenses
(description, category, amount, expense_date, user_id)
SELECT d.description, d.category, d.amount, d.date, u.id
FROM (
    SELECT 'Compra de carnes' description, 'Fornecedores' category,
           850.00 amount, CURDATE() - INTERVAL 10 DAY date
    UNION ALL SELECT 'Compra de bebidas', 'Estoque', 320.00,
           CURDATE() - INTERVAL 9 DAY
    UNION ALL SELECT 'Conta de energia', 'Contas', 480.00,
           CURDATE() - INTERVAL 8 DAY
    UNION ALL SELECT 'Conta de água', 'Contas', 150.00,
           CURDATE() - INTERVAL 7 DAY
    UNION ALL SELECT 'Produtos de limpeza', 'Limpeza', 180.00,
           CURDATE() - INTERVAL 6 DAY
    UNION ALL SELECT 'Manutenção de equipamentos', 'Manutenção',
           250.00, CURDATE() - INTERVAL 5 DAY
    UNION ALL SELECT 'Compra de embalagens', 'Estoque', 210.00,
           CURDATE() - INTERVAL 4 DAY
    UNION ALL SELECT 'Marketing digital', 'Marketing', 300.00,
           CURDATE() - INTERVAL 3 DAY
    UNION ALL SELECT 'Compra de hortifruti', 'Fornecedores',
           190.00, CURDATE() - INTERVAL 2 DAY
    UNION ALL SELECT 'Gás de cozinha', 'Contas', 130.00,
           CURDATE() - INTERVAL 1 DAY
) d
CROSS JOIN users u
WHERE u.email = 'admin@platypus.com'
AND NOT EXISTS (
    SELECT 1 FROM expenses e
    WHERE e.description = d.description
      AND e.expense_date = d.date
);

-- ============================================================
-- 15. VERIFICAÇÃO FINAL
-- ============================================================

SELECT 'USUARIOS' tabela, COUNT(*) total FROM users
UNION ALL SELECT 'CATEGORIAS', COUNT(*) FROM categories
UNION ALL SELECT 'MESAS', COUNT(*) FROM dining_tables
UNION ALL SELECT 'PRODUTOS', COUNT(*) FROM products
UNION ALL SELECT 'INGREDIENTES', COUNT(*) FROM ingredients
UNION ALL SELECT 'RECEITAS', COUNT(*) FROM recipes
UNION ALL SELECT 'CARRINHOS', COUNT(*) FROM carts
UNION ALL SELECT 'ITENS CARRINHO', COUNT(*) FROM cart_items
UNION ALL SELECT 'PEDIDOS', COUNT(*) FROM orders
UNION ALL SELECT 'ITENS PEDIDOS', COUNT(*) FROM order_items
UNION ALL SELECT 'HISTORICO', COUNT(*) FROM order_status_history
UNION ALL SELECT 'DESPESAS', COUNT(*) FROM expenses
UNION ALL SELECT 'CAIXAS', COUNT(*) FROM cash_registers;
