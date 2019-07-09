<?php
require "../../components/scripts/php/db.php";
require "../../components/scripts/php/html-parts-fr.php";

if (!isset($_SESSION["uid"]) && valid_requests($_GET["u"], $_GET["k"])) {
    // Check if the key is correct
    $stmt = $_pdo->prepare("SELECT COUNT(1) FROM users WHERE id = ? AND hashed_confirmation_key = SHA2(?, 256)");
    $stmt->execute([$_GET["u"], $_GET["k"]]);
    $correct_confirmation_key = $stmt->fetch()["COUNT(1)"] == 1;

    if ($correct_confirmation_key) {
        // Confirm the user
        $stmt = $_pdo->prepare("UPDATE users SET hashed_confirmation_key = NULL WHERE id = ?");
        $stmt->execute([$_GET["u"]]);
    }
} else {
    // Redirect to the account page
    header("Location: ./");
    die();
}
?>

<!DOCTYPE html>
<html lang="fr">
    <?php html_parts_head("Confirmer un compte", "Confirmer un compte."); ?>
    <body>
        <?php html_parts_header(); ?>
        <div class="message-container">
            <?php if ($correct_confirmation_key): ?>
                <div class="message success">
                    <p>Ton compte a bien été confirmé ! <a href="./login">Se connecter</a></p>
                </div>
            <?php else: ?>
                <div class="message error">
                    <p>Impossible de confirmer ce compte.</p>
                </div>
            <?php endif; ?>
        </div>
    </body>
</html>
