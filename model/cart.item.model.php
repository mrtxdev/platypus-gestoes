<?php

class CartItem
{
    public ?int $id = null;
    public ?int $cartId = null;
    public ?int $productId = null;
    public int $quantity = 1;
    public float $unitPrice = 0.00;
}
