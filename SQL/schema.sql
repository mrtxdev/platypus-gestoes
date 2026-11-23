
    -- ============================================================
    -- PLATYPUS | BANCO DE DADOS COMPLETO
    -- Banco: restaurant_management
    -- SGBD: MariaDB 12.3+
    --
    -- Estrutura original + tabela de despesas.
    --
    -- IMPORTANTE:
    -- CREATE TABLE IF NOT EXISTS preserva tabelas existentes.
    -- Nenhuma tabela ou registro será apagado.
    -- ============================================================

    SET NAMES utf8mb4;

    CREATE DATABASE IF NOT EXISTS restaurant_management
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

    USE restaurant_management;

    SET FOREIGN_KEY_CHECKS = 0;


    -- ============================================================
    -- 1. USERS
    -- ============================================================

    CREATE TABLE IF NOT EXISTS users (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('ADMIN','KITCHEN','ATTENDANT')
            NOT NULL DEFAULT 'ATTENDANT',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        UNIQUE KEY email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- 2. CATEGORIES
    -- ============================================================

    CREATE TABLE IF NOT EXISTS categories (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,

        PRIMARY KEY (id),
        UNIQUE KEY name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- 3. DINING TABLES
    -- ============================================================

    CREATE TABLE IF NOT EXISTS dining_tables (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        table_number VARCHAR(30) NOT NULL,
        qr_token CHAR(36) NOT NULL,
        status ENUM('AVAILABLE','OCCUPIED','INACTIVE')
            NOT NULL DEFAULT 'AVAILABLE',
        session_started_at DATETIME DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        UNIQUE KEY table_number (table_number),
        UNIQUE KEY qr_token (qr_token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- 4. PRODUCTS
    -- ============================================================

    CREATE TABLE IF NOT EXISTS products (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        category_id INT(10) UNSIGNED DEFAULT NULL,
        name VARCHAR(150) NOT NULL,
        description VARCHAR(500) DEFAULT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        is_available TINYINT(1) NOT NULL DEFAULT 1,
        stock_quantity INT(10) UNSIGNED DEFAULT NULL,
        image VARCHAR(255) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        KEY fk_products_category (category_id),
        KEY idx_products_available (is_available),

        CONSTRAINT fk_products_category
            FOREIGN KEY (category_id)
            REFERENCES categories(id)
            ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- 5. INGREDIENTS
    -- ============================================================

    CREATE TABLE IF NOT EXISTS ingredients (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(120) NOT NULL,
        unit VARCHAR(10) NOT NULL DEFAULT 'un',
        current_quantity DECIMAL(12,3) NOT NULL DEFAULT 0.000,
        minimum_quantity DECIMAL(12,3) NOT NULL DEFAULT 0.000,
        is_active TINYINT(1) NOT NULL DEFAULT 1,

        PRIMARY KEY (id),
        UNIQUE KEY name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- 6. CARTS
    -- ============================================================

    CREATE TABLE IF NOT EXISTS carts (
        id INT(11) NOT NULL AUTO_INCREMENT,
        table_id INT(11) NOT NULL,
        status ENUM('OPEN','CLOSED') NOT NULL DEFAULT 'OPEN',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        KEY idx_carts_table_status (table_id,status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- 7. CART ITEMS
    -- ============================================================

    CREATE TABLE IF NOT EXISTS cart_items (
        id INT(11) NOT NULL AUTO_INCREMENT,
        cart_id INT(11) NOT NULL,
        product_id INT(11) NOT NULL,
        quantity INT(11) NOT NULL DEFAULT 1,
        unit_price DECIMAL(10,2) NOT NULL,
        removed_ingredients TEXT DEFAULT NULL,

        PRIMARY KEY (id),
        KEY idx_cart_items_cart (cart_id),
        KEY idx_cart_items_product (product_id),

        CONSTRAINT fk_cart_items_cart
            FOREIGN KEY (cart_id)
            REFERENCES carts(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- 8. ORDERS
    -- ============================================================

    CREATE TABLE IF NOT EXISTS orders (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        table_id INT(10) UNSIGNED DEFAULT NULL,
        user_id INT(10) UNSIGNED DEFAULT NULL,
        status ENUM(
            'PENDING',
            'PREPARING',
            'READY',
            'DELIVERED',
            'CANCELLED'
        ) NOT NULL DEFAULT 'PENDING',
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        notes VARCHAR(255) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT NULL,

        PRIMARY KEY (id),
        KEY fk_orders_table (table_id),
        KEY fk_orders_user (user_id),

        CONSTRAINT fk_orders_table
            FOREIGN KEY (table_id)
            REFERENCES dining_tables(id)
            ON DELETE SET NULL,

        CONSTRAINT fk_orders_user
            FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- 9. ORDER ITEMS
    -- ============================================================

    CREATE TABLE IF NOT EXISTS order_items (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id INT(10) UNSIGNED NOT NULL,
        product_id INT(10) UNSIGNED DEFAULT NULL,
        product_name VARCHAR(150) NOT NULL,
        quantity INT(10) UNSIGNED NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        notes VARCHAR(250) DEFAULT NULL,
        removed_ingredients TEXT DEFAULT NULL,

        PRIMARY KEY (id),
        KEY fk_order_items_product (product_id),
        KEY idx_order_items_order (order_id),

        CONSTRAINT fk_order_items_order
            FOREIGN KEY (order_id)
            REFERENCES orders(id)
            ON DELETE CASCADE,

        CONSTRAINT fk_order_items_product
            FOREIGN KEY (product_id)
            REFERENCES products(id)
            ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- 10. ORDER STATUS HISTORY
    -- ============================================================

    CREATE TABLE IF NOT EXISTS order_status_history (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id INT(10) UNSIGNED NOT NULL,
        user_id INT(10) UNSIGNED DEFAULT NULL,
        previous_status VARCHAR(20) DEFAULT NULL,
        new_status VARCHAR(20) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        notes VARCHAR(255) DEFAULT NULL,

        PRIMARY KEY (id),
        KEY fk_order_history_order (order_id),
        KEY fk_order_history_user (user_id),

        CONSTRAINT fk_order_history_order
            FOREIGN KEY (order_id)
            REFERENCES orders(id)
            ON DELETE CASCADE,

        CONSTRAINT fk_order_history_user
            FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- 11. RECIPES
    -- ============================================================

    CREATE TABLE IF NOT EXISTS recipes (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id INT(10) UNSIGNED NOT NULL,
        ingredient_id INT(10) UNSIGNED NOT NULL,
        quantity DECIMAL(12,3) NOT NULL,
        is_optional TINYINT(1) NOT NULL DEFAULT 0,

        PRIMARY KEY (id),
        UNIQUE KEY uq_recipe_product_ingredient
            (product_id,ingredient_id),
        KEY fk_recipes_ingredient (ingredient_id),

        CONSTRAINT fk_recipes_ingredient
            FOREIGN KEY (ingredient_id)
            REFERENCES ingredients(id),

        CONSTRAINT fk_recipes_product
            FOREIGN KEY (product_id)
            REFERENCES products(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- 12. INVENTORY MOVEMENTS
    -- ============================================================

    CREATE TABLE IF NOT EXISTS inventory_movements (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        ingredient_id INT(10) UNSIGNED NOT NULL,
        user_id INT(10) UNSIGNED DEFAULT NULL,
        type ENUM('IN','OUT','ADJUSTMENT') NOT NULL,
        quantity DECIMAL(12,3) NOT NULL,
        reason VARCHAR(255) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        KEY fk_inventory_movements_ingredient (ingredient_id),
        KEY fk_inventory_movements_user (user_id),

        CONSTRAINT fk_inventory_movements_ingredient
            FOREIGN KEY (ingredient_id)
            REFERENCES ingredients(id),

        CONSTRAINT fk_inventory_movements_user
            FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- 13. PRODUCT STOCK MOVEMENTS
    -- ============================================================

    CREATE TABLE IF NOT EXISTS product_stock_movements (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id INT(10) UNSIGNED DEFAULT NULL,
        user_id INT(10) UNSIGNED DEFAULT NULL,
        type ENUM('IN','OUT','ADJUSTMENT') NOT NULL,
        quantity INT(10) UNSIGNED NOT NULL,
        reason VARCHAR(255) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        KEY fk_product_stock_movements_user (user_id),
        KEY idx_product_stock_movements_product_date
            (product_id,created_at),

        CONSTRAINT fk_product_stock_movements_product
            FOREIGN KEY (product_id)
            REFERENCES products(id)
            ON DELETE SET NULL,

        CONSTRAINT fk_product_stock_movements_user
            FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- 14. CASH REGISTERS
    -- ============================================================

    CREATE TABLE IF NOT EXISTS cash_registers (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        opened_by_user_id INT(10) UNSIGNED DEFAULT NULL,
        closed_by_user_id INT(10) UNSIGNED DEFAULT NULL,
        opening_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        closing_amount DECIMAL(10,2) DEFAULT NULL,
        opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        closed_at DATETIME DEFAULT NULL,
        status ENUM('OPEN','CLOSED') NOT NULL DEFAULT 'OPEN',

        PRIMARY KEY (id),
        KEY fk_cash_registers_opened_by (opened_by_user_id),
        KEY fk_cash_registers_closed_by (closed_by_user_id),

        CONSTRAINT fk_cash_registers_closed_by
            FOREIGN KEY (closed_by_user_id)
            REFERENCES users(id)
            ON DELETE SET NULL,

        CONSTRAINT fk_cash_registers_opened_by
            FOREIGN KEY (opened_by_user_id)
            REFERENCES users(id)
            ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- 15. EXPENSES
    -- NOVA TABELA PARA O MODULO DE RELATORIOS
    -- ============================================================

    CREATE TABLE IF NOT EXISTS expenses (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,

        description VARCHAR(255) NOT NULL,

        category VARCHAR(100) NOT NULL,

        amount DECIMAL(10,2) NOT NULL,

        expense_date DATE NOT NULL,

        user_id INT(10) UNSIGNED DEFAULT NULL,

        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),

        KEY idx_expenses_date (expense_date),

        KEY idx_expenses_category (category),

        KEY idx_expenses_user (user_id),

        CONSTRAINT fk_expenses_user
            FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE SET NULL

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


    -- ============================================================
    -- FINALIZACAO
    -- ============================================================

    SET FOREIGN_KEY_CHECKS = 1;


    -- ============================================================
    -- VERIFICACAO
    -- ============================================================

    SHOW TABLES;

    DESCRIBE expenses;
