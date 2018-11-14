<?php declare(strict_types=1);

namespace App;

interface DataLoaderInterface {
    public function loadUsers(): array;
}