<?php

$pdo = new PDO('mysql:host=db', 'root', 'root');
$pdo->exec('CREATE DATABASE IF NOT EXISTS posiflora_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec("GRANT ALL PRIVILEGES ON posiflora_test.* TO 'posiflora'@'%'");
$pdo->exec('FLUSH PRIVILEGES');
