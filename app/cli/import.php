<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Service\Importer;
use App\Repository\PostIndexRepository;
use App\Database\Connection;

$archivePath = $argv[1] ?? null;

if (!$archivePath || !file_exists($archivePath)) {
    echo "Usage: php cli/import.php /path/to/postindex.zip\n";
    exit(1);
}

$pdo = Connection::make();
$repository = new PostIndexRepository($pdo);
$importer = new Importer($repository);
$importer->run($archivePath);

echo "Import finished\n";
