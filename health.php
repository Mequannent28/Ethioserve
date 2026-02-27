<?php
// Simple health check for Render to ensure the container is running
// This allows the container to start even if the database is still initializing.
http_response_code(200);
echo "OK - EthioServe is running.";
?>