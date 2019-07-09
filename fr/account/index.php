<?php
require "../../components/scripts/php/db.php";

// Redirect to the profile page if the user if logged in
if (isset($_SESSION["uid"])) header("Location: profile");
else header("Location: login");
?>
