<?php
declare(strict_types=1);

require_once __DIR__ . '/../model/product.model.php';
require_once __DIR__ . '/../config/database.php';

function redirectProduct(string $message, bool $success = false): void
{
    $parameter = $success ? 'sucesso' : 'erro';
    header(
        'Location: /platypus_gestoes/router.php?pagina=cardapio/admin&'
        . $parameter . '=' . urlencode($message)
    );
    exit;
}

function getProductImageDirectory(): string
{
    return dirname(__DIR__) . '/view/public/assets/images/products';
}

function removeProductImage(?string $fileName): void
{
    if ($fileName === null || $fileName === '') {
        return;
    }

    if (
        basename($fileName) !== $fileName
        || !preg_match('/\A[a-f0-9]{32}\.(jpg|png|webp)\z/', $fileName)
    ) {
        return;
    }

    $filePath = getProductImageDirectory() . '/' . $fileName;
    if (is_file($filePath)) {
        @unlink($filePath);
    }
}

function saveProductImage(bool $required, ?string $currentImage = null): ?string
{
    $file = $_FILES['image'] ?? null;

    if ($file === null || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        if ($required) {
            throw new RuntimeException('Selecione uma imagem para o produto.');
        }
        return $currentImage;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Ocorreu um erro no envio da imagem.');
    }

    if (
        !is_uploaded_file($file['tmp_name'])
        || (int) $file['size'] > 5 * 1024 * 1024
        || (int) $file['size'] <= 0
    ) {
        throw new RuntimeException('A imagem deve ter até 5 MB.');
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($extensions[$mimeType]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Envie uma imagem JPEG, PNG ou WebP válida.');
    }

    $directory = getProductImageDirectory();
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Não foi possível criar a pasta de imagens.');
    }

    $fileName = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $fileName)) {
        throw new RuntimeException('Não foi possível salvar a imagem.');
    }

    return $fileName;
}

function getProductSelectSql(): string
{
    return "SELECT
                p.id,
                p.category_id AS categoryId,
                p.name,
                p.description,
                p.price,
                p.is_available AS isAvailable,
                p.stock_quantity AS stockQuantity,
                CASE
                    WHEN p.stock_quantity IS NOT NULL THEN 'PRODUCT'
                    WHEN EXISTS (SELECT 1 FROM recipes r WHERE r.product_id = p.id) THEN 'INGREDIENTS'
                    ELSE 'NONE'
                END AS stockMode,
                p.image,
                p.created_at AS createdAt
            FROM products p";
}

function getAllProducts(): array
{
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query(getProductSelectSql() . ' ORDER BY p.name ASC');
    return $stmt->fetchAll(PDO::FETCH_CLASS, Product::class);
}

function getProductById(int $id): ?Product
{
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare(getProductSelectSql() . ' WHERE p.id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetchObject(Product::class);
    return $product ?: null;
}

function getProductRecipe(int $productId): array
{
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare(
        'SELECT
            r.ingredient_id AS ingredientId,
            r.quantity,
            r.is_optional AS isOptional,
            i.name AS ingredientName,
            i.unit
         FROM recipes r
         INNER JOIN ingredients i ON i.id = r.ingredient_id
         WHERE r.product_id = :product_id
         ORDER BY i.name ASC'
    );
    $stmt->execute(['product_id' => $productId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function validateProductData(): array
{
    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $categoryRaw = trim((string) ($_POST['category_id'] ?? ''));
    $priceRaw = str_replace(',', '.', trim((string) ($_POST['price'] ?? '')));
    $isAvailableRaw = (string) ($_POST['is_available'] ?? '1');
    $stockMode = strtoupper(trim((string) ($_POST['stock_mode'] ?? 'NONE')));

    if ($name === '' || mb_strlen($name) > 150) {
        throw new InvalidArgumentException('Informe um nome válido de até 150 caracteres.');
    }
    if (mb_strlen($description) > 500) {
        throw new InvalidArgumentException('A descrição deve ter até 500 caracteres.');
    }
    if (!is_numeric($priceRaw)) {
        throw new InvalidArgumentException('Informe um preço válido.');
    }

    $price = (float) $priceRaw;
    if ($price < 0.01 || $price > 99999999.99) {
        throw new InvalidArgumentException('O preço informado é inválido.');
    }
    if (!in_array($isAvailableRaw, ['0', '1'], true)) {
        throw new InvalidArgumentException('A disponibilidade informada é inválida.');
    }
    if (!in_array($stockMode, ['NONE', 'PRODUCT', 'INGREDIENTS'], true)) {
        throw new InvalidArgumentException('Selecione um tipo de controle de estoque válido.');
    }

    $categoryId = null;
    if ($categoryRaw !== '') {
        $categoryId = filter_var($categoryRaw, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        if ($categoryId === false) {
            throw new InvalidArgumentException('A categoria informada é inválida.');
        }
        $categoryId = (int) $categoryId;
    }

    $stockQuantity = null;
    if ($stockMode === 'PRODUCT') {
        $stockRaw = trim((string) ($_POST['stock_quantity'] ?? ''));
        $stockQuantity = filter_var($stockRaw, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => 2147483647]
        ]);
        if ($stockQuantity === false) {
            throw new InvalidArgumentException('Informe uma quantidade inteira válida para o estoque do produto.');
        }
        $stockQuantity = (int) $stockQuantity;
    }

    return [
        'category_id' => $categoryId,
        'name' => $name,
        'description' => $description !== '' ? $description : null,
        'price' => number_format($price, 2, '.', ''),
        'is_available' => (int) $isAvailableRaw,
        'stock_quantity' => $stockQuantity,
        'stock_mode' => $stockMode
    ];
}

function validateRecipeInput(PDO $pdo, array $recipeInput, string $stockMode): array
{
    $ingredientIds = $recipeInput['ingredient_id'] ?? [];
    $quantities = $recipeInput['quantity'] ?? [];
    $optionalValues = $recipeInput['is_optional'] ?? [];

    if (!is_array($ingredientIds) || !is_array($quantities) || !is_array($optionalValues)) {
        throw new InvalidArgumentException('Composição do produto inválida.');
    }

    $recipe = [];
    $usedIngredients = [];

    foreach ($ingredientIds as $key => $rawId) {
        if ($rawId === '' || $rawId === null) {
            continue;
        }

        $ingredientId = filter_var($rawId, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        $quantityRaw = str_replace(',', '.', trim((string) ($quantities[$key] ?? '')));
        $optionalRaw = (string) ($optionalValues[$key] ?? '0');

        if ($ingredientId === false || !is_numeric($quantityRaw) || (float) $quantityRaw <= 0) {
            throw new InvalidArgumentException('Verifique o ingrediente e a quantidade informados na receita.');
        }
        if (!in_array($optionalRaw, ['0', '1'], true)) {
            throw new InvalidArgumentException('Tipo de ingrediente inválido.');
        }
        if (isset($usedIngredients[(int) $ingredientId])) {
            throw new InvalidArgumentException('Não repita o mesmo ingrediente na composição.');
        }

        $stmtIngredient = $pdo->prepare(
            'SELECT id FROM ingredients WHERE id = :id AND is_active = 1 LIMIT 1'
        );
        $stmtIngredient->execute(['id' => (int) $ingredientId]);
        if (!$stmtIngredient->fetchColumn()) {
            throw new InvalidArgumentException('Um ingrediente da receita não existe ou está inativo.');
        }

        $usedIngredients[(int) $ingredientId] = true;
        $recipe[] = [
            'ingredient_id' => (int) $ingredientId,
            'quantity' => number_format((float) $quantityRaw, 3, '.', ''),
            'is_optional' => (int) $optionalRaw
        ];
    }

    if ($stockMode === 'INGREDIENTS' && $recipe === []) {
        throw new InvalidArgumentException('Adicione pelo menos um ingrediente para controlar o estoque pela receita.');
    }

    if ($stockMode !== 'INGREDIENTS' && $recipe !== []) {
        throw new InvalidArgumentException('A composição só pode ser cadastrada quando o controle for por ingredientes.');
    }

    return $recipe;
}

function replaceProductRecipe(PDO $pdo, int $productId, array $recipe): void
{
    $stmtDelete = $pdo->prepare('DELETE FROM recipes WHERE product_id = :product_id');
    $stmtDelete->execute(['product_id' => $productId]);

    if ($recipe === []) {
        return;
    }

    $stmtInsert = $pdo->prepare(
        'INSERT INTO recipes (product_id, ingredient_id, quantity, is_optional)
         VALUES (:product_id, :ingredient_id, :quantity, :is_optional)'
    );

    foreach ($recipe as $ingredient) {
        $stmtInsert->execute([
            'product_id' => $productId,
            'ingredient_id' => $ingredient['ingredient_id'],
            'quantity' => $ingredient['quantity'],
            'is_optional' => $ingredient['is_optional']
        ]);
    }
}

function recordProductStockMovement(
    PDO $pdo,
    int $productId,
    string $type,
    int $quantity,
    string $reason
): void {
    if ($quantity <= 0 || !in_array($type, ['IN', 'OUT', 'ADJUSTMENT'], true)) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO product_stock_movements
            (product_id, user_id, type, quantity, reason)
         VALUES (:product_id, :user_id, :type, :quantity, :reason)'
    );
    $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
    $stmt->execute([
        'product_id' => $productId,
        'user_id' => $userId !== false && $userId !== null && $userId > 0 ? $userId : null,
        'type' => $type,
        'quantity' => $quantity,
        'reason' => mb_substr($reason, 0, 255)
    ]);
}

function registerProduct(): void
{
    $image = null;
    $pdo = null;

    try {
        $data = validateProductData();
        $pdo = getDatabaseConnection();
        $recipe = validateRecipeInput(
            $pdo,
            $_POST['recipe'] ?? [],
            $data['stock_mode']
        );
        $image = saveProductImage(true);

        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO products
                (category_id, name, description, price, is_available, stock_quantity, image)
             VALUES
                (:category_id, :name, :description, :price, :is_available, :stock_quantity, :image)'
        );
        $stmt->execute([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'is_available' => $data['is_available'],
            'stock_quantity' => $data['stock_quantity'],
            'image' => $image
        ]);

        $productId = (int) $pdo->lastInsertId();
        replaceProductRecipe($pdo, $productId, $recipe);

        if ($data['stock_mode'] === 'PRODUCT' && (int) $data['stock_quantity'] > 0) {
            recordProductStockMovement(
                $pdo,
                $productId,
                'IN',
                (int) $data['stock_quantity'],
                'Estoque inicial do produto'
            );
        }

        $pdo->commit();
        redirectProduct('Produto cadastrado com sucesso!', true);
    } catch (Throwable $exception) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        removeProductImage($image);
        error_log('Product registration error: ' . $exception->getMessage());
        redirectProduct($exception instanceof InvalidArgumentException || $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'Não foi possível cadastrar o produto.');
    }
}

function updateProduct(): void
{
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    if ($id === false) {
        redirectProduct('ID do produto inválido.');
    }

    $newImage = null;
    $previousImage = null;
    $pdo = null;

    try {
        $data = validateProductData();
        $pdo = getDatabaseConnection();
        $recipe = validateRecipeInput($pdo, $_POST['recipe'] ?? [], $data['stock_mode']);

        $stmt = $pdo->prepare('SELECT image, stock_quantity FROM products WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) {
            throw new RuntimeException('Produto não encontrado.');
        }

        $previousImage = $current['image'];
        $newImage = saveProductImage(false, $previousImage ?: null);

        $pdo->beginTransaction();
        $stmtUpdate = $pdo->prepare(
            'UPDATE products
             SET category_id = :category_id,
                 name = :name,
                 description = :description,
                 price = :price,
                 is_available = :is_available,
                 stock_quantity = :stock_quantity,
                 image = :image
             WHERE id = :id'
        );
        $stmtUpdate->execute([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'is_available' => $data['is_available'],
            'stock_quantity' => $data['stock_quantity'],
            'image' => $newImage,
            'id' => (int) $id
        ]);

        replaceProductRecipe($pdo, (int) $id, $recipe);

        $oldStock = $current['stock_quantity'] === null ? null : (int) $current['stock_quantity'];
        $newStock = $data['stock_quantity'];
        if ($newStock !== null) {
            if ($oldStock === null && $newStock > 0) {
                recordProductStockMovement($pdo, (int) $id, 'IN', $newStock, 'Estoque configurado no cadastro do produto');
            } elseif ($oldStock !== null && $newStock !== $oldStock) {
                recordProductStockMovement(
                    $pdo,
                    (int) $id,
                    'ADJUSTMENT',
                    abs($newStock - $oldStock),
                    'Ajuste manual de estoque: ' . $oldStock . ' para ' . $newStock
                );
            }
        } elseif ($oldStock !== null && $oldStock > 0) {
            recordProductStockMovement(
                $pdo,
                (int) $id,
                'ADJUSTMENT',
                $oldStock,
                'Produto passou a controlar estoque pelos ingredientes ou ficou sem controle'
            );
        }

        $pdo->commit();
        if ($newImage !== $previousImage) {
            removeProductImage($previousImage ?: null);
        }
        redirectProduct('Produto atualizado com sucesso!', true);
    } catch (Throwable $exception) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($newImage !== null && $newImage !== $previousImage) {
            removeProductImage($newImage);
        }
        error_log('Product update error: ' . $exception->getMessage());
        redirectProduct($exception instanceof InvalidArgumentException || $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'Não foi possível atualizar o produto.');
    }
}

function deleteProduct(): void
{
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    if ($id === false) {
        redirectProduct('ID do produto inválido.');
    }

    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('SELECT image FROM products WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $image = $stmt->fetchColumn();
        if ($image === false) {
            redirectProduct('Produto não encontrado.');
        }

        $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        removeProductImage($image ?: null);
        redirectProduct('Produto excluído com sucesso!', true);
    } catch (Throwable $exception) {
        error_log('Product deletion error: ' . $exception->getMessage());
        redirectProduct('Não foi possível excluir o produto. Verifique se ele está vinculado a itens do carrinho.');
    }
}
