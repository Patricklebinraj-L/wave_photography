<?php
declare(strict_types=1);
function runMigrations(PDO $pdo): void {
 $lock = $pdo->query("SELECT GET_LOCK('wave_photography_schema_migrations', 15)")->fetchColumn();
 if ((int)$lock !== 1) throw new RuntimeException('Could not obtain database migration lock. Please retry.');
 try {
  $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(190) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $files=glob(dirname(__DIR__).'/database/migrations/*.php'); sort($files,SORT_STRING);
  foreach($files as $file){$version=basename($file,'.php');$q=$pdo->prepare('SELECT 1 FROM schema_migrations WHERE version=?');$q->execute([$version]);if($q->fetchColumn())continue;
   $migration=require $file; if(!is_callable($migration))throw new RuntimeException('Invalid migration: '.$version);
   $migration($pdo); $q=$pdo->prepare('INSERT INTO schema_migrations(version) VALUES(?)');$q->execute([$version]);
  }
 } finally { $pdo->query("SELECT RELEASE_LOCK('wave_photography_schema_migrations')"); }
}
