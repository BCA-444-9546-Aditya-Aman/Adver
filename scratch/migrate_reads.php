<?php
require_once __DIR__ . '/../db_connect.php';

try {
    // Get all admin IDs
    $admin_ids = $pdo->query("SELECT id FROM admin_users")->fetchAll(PDO::FETCH_COLUMN);
    
    $tables = ['web_leads', 'seo_leads', 'smm_leads', 'automation_leads'];
    
    foreach ($tables as $t) {
        $lead_type = str_replace('_leads', '', $t);
        
        // Fetch all leads where is_read = 1
        $stmt = $pdo->query("SELECT id FROM `$t` WHERE is_read = 1");
        $lead_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($lead_ids as $lid) {
            foreach ($admin_ids as $aid) {
                $ins = $pdo->prepare("INSERT IGNORE INTO admin_read_leads (admin_id, lead_type, lead_id) VALUES (?, ?, ?)");
                $ins->execute([$aid, $lead_type, $lid]);
            }
        }
    }
    echo "Successfully migrated old read lead statuses to 'admin_read_leads' table!\n";
} catch (\PDOException $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
?>
