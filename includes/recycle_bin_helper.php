<?php
function moveToRecycleBin($pdo, $table, $id, $actor_type, $user_id, $reason = null) {
    try {
        // Fetch the existing record
        $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            $data_json = json_encode($record);
            
            // Insert into recycle_bin
            $insertStmt = $pdo->prepare("INSERT INTO recycle_bin (user_id, actor_type, original_table, original_id, data_json, reason) VALUES (?, ?, ?, ?, ?, ?)");
            $insertStmt->execute([$user_id, $actor_type, $table, $id, $data_json, $reason]);
            
            return true;
        }
    } catch (Exception $e) {
        error_log("Recycle Bin Error: " . $e->getMessage());
    }
    return false;
}
