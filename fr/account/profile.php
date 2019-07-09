<?php
require "../../components/scripts/php/db.php";
require "../../components/scripts/php/html-parts-fr.php";

if (!valid_requests($_GET["u"]) && !isset($_SESSION["uid"])) {
    header("Location: /fr/account");
    die();
}

if (isset($_SESSION["uid"]) && (!valid_requests($_GET["u"]) || $_GET["u"] == $_SESSION["uid"])) {

    // Change the user's password
    if (valid_requests($_POST["update-current-password"], $_POST["update-new-password"], $_POST["update-new-password-repeat"]) && !empty($_POST["update-current-password"]) || !empty($_POST["update-new-password"]) || !empty($_POST["update-new-password-repeat"])) {
        $update_password_errors = array();

        // If the user's password is incorrect
        $stmt = $_pdo->prepare("SELECT COUNT(1) FROM users WHERE id = ? AND hashed_password = SHA2(?, 256)");
        $stmt->execute([$_SESSION["uid"], $_POST["update-current-password"]]);
        if ($stmt->fetch()["COUNT(1)"] == 0) array_push($update_password_errors, "Ton mot de passe n'est pas correct.");

        // If the new password is too short
        if (strlen($_POST["update-new-password"]) < 8) array_push($update_password_errors, "Ton nouveau mot de passe est trop court.");

        // If the new passwords are not the same
        if ($_POST["update-new-password"] != $_POST["update-new-password-repeat"]) array_push($update_password_errors, "Les deux nouveaux mots de passe ne correspondent pas.");

        // If there is no error
        if (count($update_password_errors) == 0) {
            $stmt = $_pdo->prepare("UPDATE users SET hashed_password = SHA2(?, 256) WHERE id = ?");
            $stmt->execute([$_POST["update-new-password"], $_SESSION["uid"]]);
        }
    }

    // Change the user's icon
    if (valid_requests($_POST["update-icon"])) {
        // If the user has the animal he asked
        if ($_POST["update-icon"] == "default") {
            $stmt = $_pdo->prepare("UPDATE users SET icon_fk = NULL WHERE id = ?");
            $stmt->execute([$_SESSION["uid"]]);
        } else {
            $stmt = $_pdo->prepare("SELECT COUNT(1) FROM riddle_history WHERE solver_fk = ? AND reward_fk = ?");
            $stmt->execute([$_SESSION["uid"], $_POST["update-icon"]]);
            if ($stmt->fetch()["COUNT(1)"] > 0) {
                $stmt = $_pdo->prepare("UPDATE users SET icon_fk = ? WHERE id = ?");
                $stmt->execute([$_POST["update-icon"], $_SESSION["uid"]]);
            }
        }
    }
}

// User's infos
$stmt = $_pdo->prepare("SELECT users.id, users.username, users.email, users.role, users.icon_fk AS icon_id, TIMESTAMPDIFF(SECOND, NOW(), users.creation_date) AS time_elapsed, animals.level AS icon_level, animals.name_fr AS icon_name_fr FROM users LEFT JOIN animals ON animals.id = users.icon_fk WHERE users.id = ?");
$stmt->execute([valid_requests($_GET["u"]) ? $_GET["u"] : $_SESSION["uid"]]);
$user_infos = $stmt->fetch();

// User's animal collection
if (isset($_SESSION["uid"]) && $user_infos["id"] == $_SESSION["uid"]) {
    $stmt = $_pdo->prepare("SELECT DISTINCT riddle_history.reward_fk AS id, animals.name_fr AS name_fr FROM riddle_history JOIN animals ON riddle_history.reward_fk = animals.id WHERE riddle_history.solver_fk = ? ORDER BY animals.name_fr");
    $stmt->execute([$_SESSION["uid"]]);
    $user_animals = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
    <?php html_parts_head((isset($_SESSION["uid"]) && $user_infos["id"] == $_SESSION["uid"]) ? "Mon profil" : ("Profil de".(!empty($user_infos) ? " ".htmlspecialchars($user_infos["username"]) : "... euh...")), "Regarder un profil de joueur.", "profile"); ?>
    <body>
        <?php html_parts_header((isset($_SESSION["uid"]) && $user_infos["id"] == $_SESSION["uid"]) ? "account" : NULL); ?>
        <main id="page-main">
            <?php if (!empty($user_infos)): ?>
                <div class="box profile">
                    <header>
                        <?php if (isset($user_infos["icon_id"])): ?>
                            <img style="background: url(/components/img/placeholders/<?php echo $user_infos["icon_level"]; ?>.gif); background-size: contain;" class="pixelated icon" src="/components/img/animals/<?php echo $user_infos["icon_id"]; ?>.png" alt="<?php echo_safe($user_infos["icon_name_fr"], true); ?>" title="<?php echo_safe($user_infos["icon_name_fr"], true); ?>">
                        <?php else: ?>
                            <img class="pixelated icon" src="/components/img/logos/zerbil-16.png" alt="Icône par défaut">
                        <?php endif; ?>
                        <h2>
                            <span id="user"><?php echo_safe($user_infos["username"]); ?></span>
                            <?php if (isset($user_infos["role"])): ?>
                                <span id="role" class="user-role <?php echo_safe($user_infos["role"], true); ?>">
                                    <?php echo array("r" => "Auteur d'énigmes", "m" => "Modérateur", "z" => "Créateur")[$user_infos["role"]]; ?>
                                </span>
                            <?php endif; ?>
                            <?php if (isset($_SESSION["uid"]) && $user_infos["id"] == $_SESSION["uid"]): ?><div id="email">(<?php echo_safe($user_infos["email"]); ?>)</div><?php endif; ?>
                        </h2>
                        <div id="time-elapsed">Inscrit(e) il y a <?php echo (new DateTime("@0"))->diff(new DateTime("@".$user_infos["time_elapsed"]))->format("%aj %hh %im %ss"); ?></div>
                    </header>
                    <?php
                    $stmt = $_pdo->prepare("SELECT COUNT(1) FROM riddle_history WHERE solver_fk = ?");
                    $stmt->execute([$user_infos["id"]]);
                    if ($stmt->fetch()["COUNT(1)"] > 0): ?>
                    <div id="solved-riddles-container">
                        <table id="solved-riddles-table">
                            <caption>Énigmes que <?php echo_safe(isset($_SESSION["uid"]) && $user_infos["id"] == $_SESSION["uid"] ? "tu as" : $user_infos["username"]." a"); ?> résolues :</caption>
                        </table>
                        <button id="more-solved-riddles" type="button" onclick="loadMoreSolvedRiddles()">
                            <img src="/components/img/ui/more-solved-riddles.png" alt="Bouton plus d'énigmes">
                        </button>
                        <img id="loading-wheel" src="/components/img/ui/loading-solved-riddles.gif" alt="Roue chargement énigmes">
                        <script type="text/javascript">
                        document.onload = loadMoreSolvedRiddles();
    
                        var moreSolvedRiddlesButton = document.getElementById("more-solved-riddles");
                        var selectStart = 0;
                        var minTimeElapsed = 0;
                        function addSolvedRiddle(rewardId, rewardLevel, rewardName, level, name, authorId, authorUsername, authorRole, timeElapsed) {
                            var solvedRiddlesTable = document.getElementById("solved-riddles-table");
                            var solvedRiddleTr = document.createElement("tr");
    
                            var rewardTd = document.createElement("td");
                            rewardTd.className = "reward";
                            var rewardImg = document.createElement("img");
                            rewardImg.style.background = "url(/components/img/placeholders/" + rewardLevel + ".gif)";
                            rewardImg.style.backgroundSize = "contain";
                            rewardImg.src = "/components/img/animals/" + rewardId + ".png";
                            rewardImg.title = rewardName;
    
                            var levelTd = document.createElement("td");
                            levelTd.className = "level";
                            var levelImg = document.createElement("img");
                            levelImg.className = "pixelated";
                            levelImg.src = "/components/img/levels/" + level + ".png";
    
                            var nameTd = document.createElement("td");
                            nameTd.className = "name";
                            nameTd.innerHTML = name;
    
                            var authorTd = document.createElement("td");
                            var authorA = document.createElement("a");
                            authorA.className = "user-role " + (authorRole == null ? "none" : authorRole);
                            authorA.href = "/fr/account/profile?u=" + authorId;
                            authorA.innerHTML = authorUsername;
    
                            var timeElapsedTd = document.createElement("td");
                            timeElapsedTd.className = "time-elapsed";
                            timeElapsedTd.innerHTML = Math.round(timeElapsed / 86400) + "j";
    
                            rewardTd.appendChild(rewardImg);
                            levelTd.appendChild(levelImg);
                            authorTd.appendChild(authorA);
    
                            solvedRiddleTr.appendChild(rewardTd);
                            solvedRiddleTr.appendChild(levelTd);
                            solvedRiddleTr.appendChild(nameTd);
                            solvedRiddleTr.appendChild(authorTd);
                            solvedRiddleTr.appendChild(timeElapsedTd);
    
                            solvedRiddlesTable.appendChild(solvedRiddleTr);
                        }
                        function loadMoreSolvedRiddles() {
                            var loadingWheelImg = document.getElementById("loading-wheel");
                            loadingWheelImg.style.display = "inline";
                            var moreSolvedRiddlesButton = document.getElementById("more-solved-riddles");
                            moreSolvedRiddlesButton.style.display = "none";
    
                            const xhr = new XMLHttpRequest();
                            xhr.open("get", "riddle-history?u=<?php echo $user_infos["id"]; ?>&s=" + selectStart + "&m=" + minTimeElapsed);
                            xhr.onload = function() {
                                try {
                                    if (xhr.responseText != "[]") {
                                        const json = JSON.parse(xhr.responseText);
                                        for (var k = 0; k < json.length; k++) {
                                            if (selectStart == 0 && k == 0) minTimeElapsed = json[k]["time_elapsed"];
                                            addSolvedRiddle(json[k]["reward_id"], json[k]["reward_level"], json[k]["reward_name_fr"], json[k]["level"], json[k]["name"], json[k]["author_id"], json[k]["author_username"], json[k]["author_role"], json[k]["time_elapsed"]);
                                        }
                                        if (json.length == 5) moreSolvedRiddlesButton.style.display = "inline";
                                        selectStart++;
                                    }
                                } catch (e) {
                                    console.warn("Erreur ! " + e);
                                } finally {
                                    loadingWheelImg.style.display = "none";
                                }
                            }
                            xhr.send();
                        }
                        </script>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION["uid"]) && $user_infos["id"] == $_SESSION["uid"]): ?>
                        <footer>
                            <a href="logout?sessid=<?php echo session_id(); ?>"><img src="/components/img/ui/logout.png" alt="Se déconnecter" title="Se déconnecter"></a>
                        </footer>
                    <?php endif; ?>
                </div>
                <?php if (isset($_SESSION["uid"]) && $user_infos["id"] == $_SESSION["uid"]): ?>
                    <div id="update-account-box">
                        <h3>Modifier mon compte</h3>
                        <form method="post" action="#update-account-box">
                            <fieldset>
                                <?php if (isset($update_password_errors)): ?>
                                    <?php if (count($update_password_errors) > 0): ?>
                                        <div class="message-container">
                                            <div class="message error">
                                                <?php foreach ($update_password_errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="message-container">
                                            <div class="message success">
                                                <p>Ton mot de passe a été mis à jour.</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <legend>Mot de passe :</legend>
                                <input type="password" name="update-current-password" placeholder="Mot de passe actuel">
                                <input type="password" name="update-new-password" placeholder="Nouveau mot de passe">
                                <input type="password" name="update-new-password-repeat" placeholder="Répéter le nouveau mot de passe">
                            </fieldset>
                            <?php if (!empty($user_animals)): ?>
                                <fieldset>
                                    <legend>Icône :</legend>
                                    <select name="update-icon">
                                        <option value="default">Par défaut</option>
                                        <?php foreach ($user_animals as $user_animal): ?>
                                            <option value="<?php echo $user_animal["id"]; ?>" <?php if (isset($user_infos["icon_id"]) && $user_infos["icon_id"] == $user_animal["id"]): ?>selected<?php endif; ?>><?php echo_safe($user_animal["name_fr"]); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </fieldset>
                            <?php endif; ?>
                            <input type="submit" value="Enregistrer les modifications">
                        </form>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="message-box">
                    <div class="message error">
                        <p>Cet utilisateur n'existe pas ou a été banni...</p>
                    </div>
                </div>
            <?php endif; ?>
        </main>
        <?php html_parts_footer(); ?>
    </body>
</html>
