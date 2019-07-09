<?php
require "../../components/scripts/php/db.php";
require "../../components/scripts/php/html-parts-fr.php";

// Redirect the user if he is already logged in
if (isset($_SESSION["uid"])) {
    header("Location: ./");
    die();
}

if (valid_requests($_POST["register-username"], $_POST["register-email"], $_POST["register-password"], $_POST["register-password-repeat"])) {
    $errors = array();

    // If the username's length is incorrect
    if (strlen($_POST["register-username"]) < 2 || strlen($_POST["register-username"]) > 32) array_push($errors, "La longueur du nom d'utilisateur n'est pas valide (2 à 32 caractères).");

    // If the username does not contain only letters and digits
    if (!ctype_alnum($_POST["register-username"])) array_push($errors, "Le nom d'utilisateur ne peut contenir que des caractères alphanumériques.");

    // If the email is incorrect
    if (!filter_var($_POST["register-email"], FILTER_VALIDATE_EMAIL) || strlen($_POST["register-email"]) > 64) array_push($errors, "Cette adresse email n'est pas valide.");

    // If the password is too short
    if (strlen($_POST["register-password"]) < 8) array_push($errors, "Ce mot de passe est trop court.");

    // If passwords do not match
    if ($_POST["register-password"] != $_POST["register-password-repeat"]) array_push($errors, "Les deux mots de passe ne correspondent pas.");

    // Check if these credentials are already used if there is no error
    if (count($errors) == 0) {
        // If the username already exists
        $stmt = $_pdo->prepare("SELECT 1 FROM users WHERE username = ?");
        $stmt->execute([$_POST["register-username"]]);
        if ($stmt->rowCount() > 0) array_push($errors, "Ce nom d'utilisateur est déjà utilisé.");

        // If email already exists
        $stmt = $_pdo->prepare("SELECT 1 FROM users WHERE email = ?");
        $stmt->execute([$_POST["register-email"]]);
        if ($stmt->rowCount() > 0) array_push($errors, "Cette adresse email est déjà utilisée.");
    }

    // Register if there is no error
    if (count($errors) == 0) {
        // Generate the confirmation key
        $confirmation_key = "";
        for ($_ = 0; $_ < 32; $_++) $confirmation_key .= "abcedfghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"[random_int(0, 61)];

        // Add the user into the database
        $stmt = $_pdo->prepare("INSERT INTO users (username, email, hashed_password, hashed_confirmation_key, last_ip_address) VALUES (LOWER(?), LOWER(?), SHA2(?, 256), SHA2(?, 256), ?)");
        $stmt->execute([$_POST["register-username"], $_POST["register-email"], $_POST["register-password"], $confirmation_key, $_SERVER["REMOTE_ADDR"]]);
        $user_id = $_pdo->lastInsertId();

        // Send a confirmation email
		$message = '<div style="text-align: center; font: 1em Roboto; background: #0d052e; padding: 20px"><h1 style="color: #c41bc4; font-size: 2em">Bienvenue '.$_POST["register-username"].' !</h1><p style="color: #5e3ee2; margin: 25px">Merci de t\'être inscrit(e) sur Zerbil &hearts;.</p><a style="display: inline-block; background: #140747; color: #b1a0f6; text-decoration: none; padding: 10px; border: 1px solid #5e3ee2" href="https://zerbil.org/fr/account/confirm?u='.$user_id.'&k='.$confirmation_key.'">Confirmer mon compte</a></div>';
      	$headers = "MIME-Version: 1.0"."\r\n";
		$headers .= "Content-type: text/html; charset=iso-8859-1"."\r\n";
		$headers .= "From: Zerbil <zcb@zerbil.org>"."\r\n";
		$headers .= "To: ".$_POST["register-email"];
      	mail($_POST["register-email"], "Confirmation de compte", $message, $headers);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
    <?php html_parts_head("S'enregister", "Rejoins Zerbil pour pouvoir résoudre des énigmes et partir à la chasse aux récompenses partout en France !"); ?>
    <body>
        <?php html_parts_header(); ?>
        <main id="page-main">
            <?php if (isset($user_id)): ?>
                <div class="message-container">
                    <div class="message info">
                        <p>Ton compte a bien été créé ! Il ne te reste plus qu'à le confirmer grâce au lien de confirmation qui t'a été envoyé par email.</p>
                        <p><a href="login">Se connecter</a></p>
                  </div>
                </div>
            <?php else: ?>
                <form class="box account scale-in-animation" action="register" method="post">
                    <h2>S'enregistrer</h2>
                    <?php if (isset($errors) && count($errors) > 0): ?>
                        <div class="message-container">
                            <div class="message error">
                                <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input type="text" name="register-username" placeholder="Nom d'utilisateur" maxlength="20" <?php if (valid_requests($_POST["register-username"])): ?>value="<?php echo_safe($_POST["register-username"], true); ?>"<?php endif; ?>>
                    <input type="email" name="register-email" placeholder="Adresse email" maxlength="100" <?php if (valid_requests($_POST["register-email"])): ?>value="<?php echo_safe($_POST["register-email"], true); ?>"<?php endif; ?>>
                    <input type="password" name="register-password" placeholder="Mot de passe">
                    <input type="password" name="register-password-repeat" placeholder="Répéter le mot de passe">
                    <input type="submit" value="S'enregistrer">
                    <a href="login">J'ai déjà un compte</a>
                </form>
            <?php endif; ?>
        </main>
        <?php html_parts_footer(); ?>
    </body>
</html>
