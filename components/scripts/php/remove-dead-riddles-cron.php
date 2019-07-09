<?php
require "db.php";

$stmt = $_pdo->prepare("DELETE FROM riddles WHERE expiration_date <= CURRENT_TIMESTAMP");
$stmt->execute();
?>
