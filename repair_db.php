<?php
require_once 'includes/db.php';

echo "<h2>Force Database Repair</h2>";

if (isset($_POST['repair'])) {
    try {
        $sqlPath = 'database.sql';
        if (!file_exists($sqlPath)) {
            die("Error: database.sql not found!");
        }

        echo "<p>Starting import... please wait...</p>";
        
        // Use system command for more reliable import of large files
        // We use root because it's local to the container and has full permissions
        $command = "mysql -u root ethioserve < " . escapeshellarg($sqlPath) . " 2>&1";
        
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            echo "<div style='color: green; font-weight: bold;'>SUCCESS: Database has been re-imported and repaired!</div>";
        } else {
            $errorInfo = implode("<br>", $output);
            echo "<div style='color: red; font-weight: bold;'>PARTIAL FAILURE: Command returned $returnVar</div>";
            echo "<pre>$errorInfo</pre>";
            
            // Still check if 'hotels' exists now
            $check = $pdo->query("SHOW TABLES LIKE 'hotels'")->fetch();
            if ($check) {
                echo "<div style='color: orange;'>Update: The 'hotels' table SEEMS to exist now despite warnings.</div>";
            }
        }

        echo "<br><a href='customer/index.php' style='display:inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Return to Homepage</a>";
    } catch (Exception $e) {
        echo "<div style='color: red;'>ERROR: " . $e->getMessage() . "</div>";
    }
} else {
    ?>
    <div style="background: #fff3cd; padding: 20px; border-radius: 10px; border: 1px solid #ffeeba; margin-bottom: 20px;">
        <h4 style="color: #856404; margin-top: 0;">⚠️ Database Not Ready?</h4>
        <p>If you see errors like <b>"Table ethioserve.hotels doesn't exist"</b>, it means the server skipped the initial data setup. 
        Click the button below to force the server to load all your data manually.</p>
    </div>
    <form method="POST">
        <button type="submit" name="repair"
            style="padding: 20px 40px; background: #dc3545; color: white; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; font-size: 1.2rem; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);">
            REPAIR & RESTORE ALL DATA
        </button>
    </form>
    <?php
}
?>