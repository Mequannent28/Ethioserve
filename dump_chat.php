<?php
require_once 'includes/db.php';
$res = $pdo->query("SHOW CREATE TABLE rental_chat_messages")->fetch();
echo $res['Create Table'];
