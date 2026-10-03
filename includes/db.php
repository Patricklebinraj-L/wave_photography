<?php
declare(strict_types=1);
function db(): PDO {
 static $pdo = null; if ($pdo instanceof PDO) return $pdo;
 $c = require dirname(__DIR__) . '/config.php';
 $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',$c['host'],$c['port'],$c['database'],$c['charset']);
 $pdo = new PDO($dsn,$c['username'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
 return $pdo;
}
