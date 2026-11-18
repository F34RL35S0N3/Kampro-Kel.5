<?php
declare(strict_types=1);

use App\Support\Env;

return [
    'driver'   => Env::get('DB_DRIVER', 'mysql'),
    'host'     => Env::get('DB_HOST', '127.0.0.1'),
    'port'     => (int) Env::get('DB_PORT', '3306'),
    'database' => Env::get('DB_DATABASE', 'simlap_riset'),
    'username' => Env::get('DB_USERNAME', 'root'),
    'password' => Env::get('DB_PASSWORD', ''),
    'charset'  => 'utf8mb4',
    'sqlite_path' => BASE_PATH . '/' . ltrim(Env::get('DB_SQLITE_PATH', 'storage/database.sqlite'), '/'),
];
