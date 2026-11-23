<?php

class User
{
    public ?int $id = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'ATTENDANT';
    public bool $isActive = true;
}
