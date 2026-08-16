<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$pdo = Database::connection();

$before = (int) $pdo->query('SELECT COUNT(*) FROM accounts')->fetchColumn();

$sql = file_get_contents(__DIR__ . '/seed.sql');

$pdo->beginTransaction();
try {
    $pdo->exec($sql);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Грешка при seed: ' . $e->getMessage() . "\n");
    exit(1);
}

$after = (int) $pdo->query('SELECT COUNT(*) FROM accounts')->fetchColumn();

echo ($after - $before) . " нови сметки додадени (вкупно $after).\n";
