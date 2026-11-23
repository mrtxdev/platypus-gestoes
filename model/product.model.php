<?php

class Product {
    public ?int $id = null;
    public ?int $categoryId = null;
    public string $name = '';
    public ?string $description = null;
    public float $price = 0.00;
    public bool $isAvailable = true;
    public ?int $stockQuantity = null;
    public ?string $stockMode = null;
    public ?string $image = null;
    public ?string $createdAt = null;
}
