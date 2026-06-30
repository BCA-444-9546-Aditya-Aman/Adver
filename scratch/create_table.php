<?php
require_once __DIR__ . '/../db_connect.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `admin_read_leads` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `admin_id` int(11) NOT NULL,
        `lead_type` varchar(20) NOT NULL COMMENT 'web | seo | smm | automation',
        `lead_id` int(11) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_admin_lead` (`admin_id`,`lead_type`,`lead_id`),
        CONSTRAINT `fk_read_admin_id` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $pdo->exec($sql);
    echo "Table 'admin_read_leads' created successfully!\n";
} catch (\PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>
