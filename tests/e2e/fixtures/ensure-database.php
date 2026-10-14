<?php

// Create the e2e database if this machine has never had one, so the first run
// needs no hand step. Connects without naming a database — the one named does
// not exist yet. The name has already passed run.php's fence.

$config = config('database.connections.mysql');

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s', $config['host'], $config['port']),
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$name = str_replace('`', '', $config['database']);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

echo "database {$name} is there\n";
