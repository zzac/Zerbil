<?php
require "../../components/scripts/php/db.php";

if (isset($_SESSION["uid"]) && valid_requests($_GET["sessid"]) && $_GET["sessid"] == session_id()) session_destroy();
header("Location: ./");
?>
