<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function redirectIngredients(string $message, bool $success = false): void
{
    $parameter = $success ? 'sucesso' : 'erro';

    header(
        'Location: /platypus_gestoes/router.php?pagina=ingredientes&'
        . $parameter . '=' . urlencode($message)
    );
    exit;
}

function getAllIngredients(bool $activeOnly = false): array
{
    $pdo = getDatabaseConnection();

    $sql = '
        SELECT
            i.id,
            i.name,
            i.unit,
            i.current_quantity,
            i.minimum_quantity,
            i.is_active,
            (
                SELECT COUNT(*)
                FROM recipes r
                WHERE r.ingredient_id = i.id
            ) AS recipe_links,
            (
                SELECT COUNT(*)
                FROM inventory_movements im
                WHERE im.ingredient_id = i.id
            ) AS stock_movements
        FROM ingredients i
    ';

    if ($activeOnly) {
        $sql .= ' WHERE i.is_active = 1';
    }

    $sql .= ' ORDER BY i.name ASC';

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function ingredientUserId(): ?int
{
    $value = filter_var(
        $_SESSION['user_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    return $value !== false && $value !== null && $value > 0
        ? (int) $value
        : null;
}

function validateIngredientUnit(string $unit): string
{
    $unit = trim($unit);

    $allowed = ['un', 'g', 'kg', 'ml', 'l', 'mg'];

    if (!in_array($unit, $allowed, true)) {
        throw new InvalidArgumentException(
            'Selecione uma unidade de medida válida.'
        );
    }

    return $unit;
}

function registerIngredient(): void
{
    $pdo = null;

    try {
        $name = trim((string) ($_POST['name'] ?? ''));

        $unit = validateIngredientUnit(
            (string) ($_POST['unit'] ?? 'un')
        );

        $initialRaw = str_replace(
            ',',
            '.',
            trim((string) ($_POST['current_quantity'] ?? '0'))
        );

        $minimumRaw = str_replace(
            ',',
            '.',
            trim((string) ($_POST['minimum_quantity'] ?? '0'))
        );

        if ($name === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException(
                'Informe um nome de ingrediente de até 120 caracteres.'
            );
        }

        if (!is_numeric($initialRaw) || !is_numeric($minimumRaw)) {
            throw new InvalidArgumentException(
                'Informe quantidades válidas.'
            );
        }

        $initial = (float) $initialRaw;
        $minimum = (float) $minimumRaw;

        if (
            $initial < 0 ||
            $minimum < 0 ||
            $initial > 999999999.999 ||
            $minimum > 999999999.999
        ) {
            throw new InvalidArgumentException(
                'As quantidades não podem ser negativas ou exceder o limite permitido.'
            );
        }

        $pdo = getDatabaseConnection();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO ingredients
                (name, unit, current_quantity, minimum_quantity, is_active)
             VALUES
                (:name, :unit, :current_quantity, :minimum_quantity, 1)'
        );

        $stmt->execute([
            'name' => $name,
            'unit' => $unit,
            'current_quantity' => number_format($initial, 3, '.', ''),
            'minimum_quantity' => number_format($minimum, 3, '.', '')
        ]);

        $ingredientId = (int) $pdo->lastInsertId();

        if ($initial > 0) {
            $movement = $pdo->prepare(
                "INSERT INTO inventory_movements
                    (ingredient_id, user_id, type, quantity, reason)
                 VALUES
                    (:ingredient_id, :user_id, 'IN', :quantity, 'Estoque inicial do ingrediente')"
            );

            $movement->execute([
                'ingredient_id' => $ingredientId,
                'user_id' => ingredientUserId(),
                'quantity' => number_format($initial, 3, '.', '')
            ]);
        }

        $pdo->commit();

        redirectIngredients(
            'Ingrediente cadastrado com sucesso!',
            true
        );

    } catch (Throwable $exception) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Ingredient registration error: ' . $exception->getMessage()
        );

        redirectIngredients(
            $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : 'Não foi possível cadastrar o ingrediente. Verifique se o nome já está em uso.'
        );
    }
}

function updateIngredient(): void
{
    $id = filter_var(
        $_POST['ingredient_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        redirectIngredients('Ingrediente inválido.');
    }

    try {
        $name = trim((string) ($_POST['name'] ?? ''));

        $unit = validateIngredientUnit(
            (string) ($_POST['unit'] ?? 'un')
        );

        $minimumRaw = str_replace(
            ',',
            '.',
            trim((string) ($_POST['minimum_quantity'] ?? '0'))
        );

        $isActive = (string) ($_POST['is_active'] ?? '1');

        if ($name === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException(
                'Informe um nome de ingrediente de até 120 caracteres.'
            );
        }

        if (
            !is_numeric($minimumRaw) ||
            (float) $minimumRaw < 0 ||
            (float) $minimumRaw > 999999999.999
        ) {
            throw new InvalidArgumentException(
                'Estoque mínimo inválido.'
            );
        }

        if (!in_array($isActive, ['0', '1'], true)) {
            throw new InvalidArgumentException(
                'Estado do ingrediente inválido.'
            );
        }

        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare(
            'UPDATE ingredients
             SET name = :name,
                 unit = :unit,
                 minimum_quantity = :minimum_quantity,
                 is_active = :is_active
             WHERE id = :id'
        );

        $stmt->execute([
            'name' => $name,
            'unit' => $unit,
            'minimum_quantity' => number_format(
                (float) $minimumRaw,
                3,
                '.',
                ''
            ),
            'is_active' => (int) $isActive,
            'id' => (int) $id
        ]);

        redirectIngredients(
            'Ingrediente atualizado com sucesso!',
            true
        );

    } catch (Throwable $exception) {
        error_log(
            'Ingredient update error: ' . $exception->getMessage()
        );

        redirectIngredients(
            $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : 'Não foi possível atualizar o ingrediente. Verifique se o nome já está em uso.'
        );
    }
}

function adjustIngredientStock(): void
{
    $id = filter_var(
        $_POST['ingredient_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $quantityRaw = str_replace(
        ',',
        '.',
        trim((string) ($_POST['current_quantity'] ?? ''))
    );

    if ($id === false || !is_numeric($quantityRaw)) {
        redirectIngredients(
            'Informe um ingrediente e uma quantidade válidos.'
        );
    }

    $newQuantity = (float) $quantityRaw;

    if ($newQuantity < 0 || $newQuantity > 999999999.999) {
        redirectIngredients(
            'A quantidade de estoque informada é inválida.'
        );
    }

    $pdo = null;

    try {
        $pdo = getDatabaseConnection();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT current_quantity
             FROM ingredients
             WHERE id = :id
             FOR UPDATE'
        );

        $stmt->execute(['id' => (int) $id]);

        $oldValue = $stmt->fetchColumn();

        if ($oldValue === false) {
            throw new RuntimeException(
                'Ingrediente não encontrado.'
            );
        }

        $oldQuantity = (float) $oldValue;
        $difference = abs($newQuantity - $oldQuantity);

        $update = $pdo->prepare(
            'UPDATE ingredients
             SET current_quantity = :quantity
             WHERE id = :id'
        );

        $update->execute([
            'quantity' => number_format($newQuantity, 3, '.', ''),
            'id' => (int) $id
        ]);

        if ($difference > 0.000001) {
            $movement = $pdo->prepare(
                "INSERT INTO inventory_movements
                    (ingredient_id, user_id, type, quantity, reason)
                 VALUES
                    (:ingredient_id, :user_id, 'ADJUSTMENT', :quantity, :reason)"
            );

            $movement->execute([
                'ingredient_id' => (int) $id,
                'user_id' => ingredientUserId(),
                'quantity' => number_format($difference, 3, '.', ''),
                'reason' => 'Ajuste manual de estoque: '
                    . number_format($oldQuantity, 3, ',', '.')
                    . ' para '
                    . number_format($newQuantity, 3, ',', '.')
            ]);
        }

        $pdo->commit();

        redirectIngredients(
            'Estoque atualizado com sucesso!',
            true
        );

    } catch (Throwable $exception) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Ingredient stock adjustment error: '
            . $exception->getMessage()
        );

        redirectIngredients(
            'Não foi possível ajustar o estoque.'
        );
    }
}

/**
 * Exclui definitivamente um ingrediente.
 *
 * Também remove seus vínculos com receitas e movimentações
 * de estoque para evitar violações de chaves estrangeiras.
 */
function deleteIngredient(): void
{
    $id = filter_var(
        $_POST['ingredient_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        redirectIngredients('Ingrediente inválido.');
    }

    $pdo = null;

    try {
        $pdo = getDatabaseConnection();
        $pdo->beginTransaction();

        // Verifica a existência do ingrediente.
        $stmt = $pdo->prepare(
            'SELECT id, name
             FROM ingredients
             WHERE id = :id
             FOR UPDATE'
        );

        $stmt->execute(['id' => (int) $id]);

        $ingredient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ingredient) {
            throw new RuntimeException(
                'Ingrediente não encontrado.'
            );
        }

        // Remove os vínculos com receitas.
        $deleteRecipes = $pdo->prepare(
            'DELETE FROM recipes
             WHERE ingredient_id = :id'
        );

        $deleteRecipes->execute([
            'id' => (int) $id
        ]);

        // Remove o histórico de movimentações.
        $deleteMovements = $pdo->prepare(
            'DELETE FROM inventory_movements
             WHERE ingredient_id = :id'
        );

        $deleteMovements->execute([
            'id' => (int) $id
        ]);

        // Exclui definitivamente o ingrediente.
        $deleteIngredient = $pdo->prepare(
            'DELETE FROM ingredients
             WHERE id = :id'
        );

        $deleteIngredient->execute([
            'id' => (int) $id
        ]);

        $pdo->commit();

        redirectIngredients(
            'Ingrediente excluído permanentemente!',
            true
        );

    } catch (Throwable $exception) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Ingredient deletion error: '
            . $exception->getMessage()
        );

        redirectIngredients(
            'Não foi possível excluir o ingrediente. Verifique os vínculos existentes no banco de dados.'
        );
    }
}
