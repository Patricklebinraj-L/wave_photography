<?php
declare(strict_types=1);
return static function(PDO $pdo): void {
 $pdo->exec("CREATE TABLE IF NOT EXISTS admin_form_defaults (
  admin_id INT UNSIGNED NOT NULL, form_action VARCHAR(64) NOT NULL, payload_json LONGTEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (admin_id, form_action),
  CONSTRAINT fk_admin_form_defaults_user FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
};
