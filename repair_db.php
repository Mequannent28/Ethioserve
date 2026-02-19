<?php
require_once 'includes/db.php';

echo "<h2>Force Database Repair</h2>";

if (isset($_POST['repair'])) {
    try {
        $sqlPath = 'database.sql';
        if (!file_exists($sqlPath)) {
            die("Error: database.sql not found!");
        }

        $sql = file_get_contents($sqlPath);

        // Remove DATABASE/USE lines
        $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
        $sql = preg_replace('/USE .*?;/i', '', $sql);

        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        $pdo->exec($sql);
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

        echo "<div style='color: green; font-weight: bold;'>SUCCESS: Database has been re-imported and repaired!</div>";
        echo "<br><a href='customer/index.php'>Go to Homepage</a>";
    } catch (Exception $e) {
        echo "<div style='color: red;'>ERROR: " . $e->getMessage() . "</div>";
    }
} else {
    ?>
    <p>Use this tool if you see "Table not found" errors on the website.</p>
    <form method="POST">
        <button type="submit" name="repair"
            style="padding: 15px 30px; background: red; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
            REPAIR DATABASE NOW
        </button>
    </form>
    <?php
}
?>