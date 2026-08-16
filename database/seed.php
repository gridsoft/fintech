<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$pdo = Database::connection();

$count = (int) $pdo->query('SELECT COUNT(*) FROM accounts')->fetchColumn();

if ($count > 0) {
    echo "Контниот план веќе содржи $count сметки — прескокнувам seed.\n";
    exit(0);
}

$sql = file_get_contents(__DIR__ . '/seed.sql');

$pdo->beginTransaction();
try {
    $pdo->exec($sql);
    $pdo->commit();
    echo "Почетниот контен план е внесен.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Грешка при seed: ' . $e->getMessage() . "\n");
    exit(1);
}
