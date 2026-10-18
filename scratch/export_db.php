<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$pdo = get_db_connection();
$tables = ['users', 'resources', 'resource_files', 'access_logs'];

$sql = "-- TikTak Central Database Export\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sql .= "SET NAMES utf8mb4;\n";
$sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $table) {
    $sql .= "-- --------------------------------------------------------\n";
    $sql .= "-- Table structure for `$table`\n";
    $sql .= "-- --------------------------------------------------------\n";
    $sql .= "DROP TABLE IF EXISTS `$table`;\n";
    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
    $sql .= $create . ";\n\n";

    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        $sql .= "-- Dumping data for `$table`\n";
        foreach ($rows as $row) {
            $cols = array_map(fn($c) => "`$c`", array_keys($row));
            $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($row));
            $sql .= "INSERT INTO `$table` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
        }
        $sql .= "\n";
    }
}

$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents(__DIR__ . '/../tiktak_database.sql', $sql);
echo "Export completed successfully to tiktak_database.sql! Size: " . strlen($sql) . " bytes\n";
