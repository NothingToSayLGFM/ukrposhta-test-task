<?php

namespace App\Database;

use PDO;

class Connection
{
    public static function make(): PDO
    {
        $dsn = "mysql:host=db;dbname=ukrposhta;charset=utf8mb4";

        return new PDO($dsn, 'root', 'root', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]);
    }
}
