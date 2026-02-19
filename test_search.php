<?php
// Simulate GET request
$_GET['q'] = 'a';

// Include the search API
ob_start();
require 'includes/search_api.php';
$output = ob_get_clean();
echo substr($output, 0, 100);
?>