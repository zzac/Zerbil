<?php
require "../../components/scripts/php/db.php";
require "../../components/scripts/php/html-parts-fr.php";

// Redirect the user if he is already logged in
if (isset($_SESSION["uid"])) {
    header("Location: ./");
    die();
}

if (valid_requests($_POST["login-username"], $_POST["login-password"])) {
    // Get the user's username
    $stmt = $_pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND hashed_password = SHA2(?, 256)");
    $stmt->execute([$_POST["login-username"], $_POST["login-username"], $_POST["login-password"]]);

    // If the credentials are correct
    if ($stmt->rowCount() == 1) {
        $user_id = $stmt->fetch()["id"];

        // Check if the user is confirmed
        $stmt = $_pdo->prepare("SELECT 1 FROM users WHERE id = ? AND hashed_confirmation_key IS NULL");
        $stmt->execute([$user_id]);
        if ($stmt->rowCount() == 1) {
            $stmt = $_pdo->prepare("UPDATE users SET last_ip_address = ? WHERE id = ?");
            $stmt->execute([$_SERVER["REMOTE_ADDR"], $user_id]);

            // Login the user
            $_SESSION["uid"] = $user_id;

            // Redirect to the account page
            header("Location: ./");
            die();
        } else $error = "Ton compte n'a pas encore été confirmé.";
    } else $error = "Nom d'utilisateur ou mot de passe incorrect(s) !";
}
?>

<!DOCTYPE html>
<html lang="fr">
    <?php html_parts_head("Connexion", "Connecte-toi pour pouvoir résoudre des énigmes et partir à la chasse aux récompenses partout en France !"); ?>
    <body>
        <?php html_parts_header(); ?>
        <main id="page-main">
            <form class="box account scale-in-animation" action="login" method="post">
                <h2>Se connecter</h2>
                <?php if (isset($error)): ?>
                    <div class="message-container">
                        <div class="message error">
                            <p><?php echo $error; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="text" name="login-username" placeholder="Nom d'utilisateur">
                <input type="password" name="login-password" placeholder="Mot de passe">
                <input type="submit" value="Se connecter">
                <a href="register">Je n'ai pas de compte</a>
            </form>
        </main>
        <?php html_parts_footer(); ?>
    </body>
</html>
