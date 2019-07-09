<?php
require "../../components/scripts/php/db.php";
require "../../components/scripts/php/html-parts-fr.php";

if (valid_requests($_GET["r"])) {
    // Get the riddle
    $stmt = $_pdo->prepare(
        "SELECT riddles.id, riddles.name, riddles.location, riddles.description, riddles.level, riddles.reward_fk AS reward_id, riddles.creation_date, TIMESTAMPDIFF(SECOND, NOW(), riddles.expiration_date) AS time_left, riddles.author_fk AS author_id, riddles.hashed_code,
        animals.name_fr AS reward_name_fr, animals.level AS reward_level, users.username AS author_username, users.role AS author_role
        FROM riddles JOIN animals ON riddles.reward_fk = animals.id JOIN users ON riddles.author_fk = users.id
        WHERE riddles.id = ?");
    $stmt->execute([$_GET["r"]]);
    $riddle = $stmt->fetch();
} else {
    // Redirect to the search page
    header("Location: search");
    die();
}

// If the code is correct
$correct_code = false;
if (!empty($riddle) && valid_requests($_POST["riddle-code"], $_SESSION["uid"]) && $_SESSION["uid"] != $riddle["author_id"] && hash("sha256", strtolower($_POST["riddle-code"])) == $riddle["hashed_code"]) {
    try {
        $_pdo->beginTransaction();

        // Delete the riddle
        $stmt = $_pdo->prepare("DELETE FROM riddles WHERE id = ?");
        $stmt->execute([$riddle["id"]]);

        // Give the reward to the user
        $stmt = $_pdo->prepare(
            "INSERT INTO riddle_history (name, level, reward_fk, author_fk, creation_date, solver_fk, solving_date)
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute([$riddle["name"], $riddle["level"], $riddle["reward_id"], $riddle["author_id"], $riddle["creation_date"], $_SESSION["uid"]]);

        $_pdo->commit();
    } catch (Exception $e) {
        echo "ERREUR ".$e->getMessage();
        $_pdo->rollback();
    }

    $correct_code = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
    <?php html_parts_head("Énigme : ".(!empty($riddle) ? htmlspecialchars($riddle["name"]) : "Euh..."), (!empty($riddle) ? htmlspecialchars($riddle["description"]) : "Cette énigme n'existe pas."), "read"); ?>
    <body>
        <?php html_parts_header(); ?>
        <main id="page-main">
            <?php if (!empty($riddle)): ?>
                <?php if ($correct_code): ?>
                    <div id="congratulations-container" class="scale-in-animation">
                        Bravo ! voilà ta récompense :
                        <img style="background: url(/components/img/placeholders/<?php echo $riddle["reward_level"]; ?>.gif); background-size: contain;" class="pixelated" src="/components/img/animals/<?php echo $riddle["reward_id"]; ?>.png" alt="<?php echo_safe($riddle["reward_name_fr"], true); ?>" title="<?php echo_safe($riddle["reward_name_fr"], true); ?>">
                        <a href="./">Merci</a>
                    </div>
                <?php else: ?>
                    <div id="riddle-wrapper">
                        <article class="box riddle">
                            <header>
                                <button id="go-back" onclick="history.go(-1)">
                                    <img src="/components/img/ui/back.png" alt="Bouton retour" title="Retour">
                                </button>
                                <img id="reward" style="background: url(/components/img/placeholders/<?php echo $riddle["reward_level"]; ?>.gif); background-size: contain;" class="pixelated" src="/components/img/animals/<?php echo $riddle["reward_id"]; ?>.png" alt="<?php echo_safe($riddle["reward_name_fr"], true); ?>" title="<?php echo_safe($riddle["reward_name_fr"], true); ?>">
                                <h2>
                                    <span class="location"><?php echo_safe($riddle["location"]); ?></span>
                                    <span class="name"><?php echo_safe($riddle["name"]); ?></span>
                                    <span class="author">[<a href="/fr/account/profile?u=<?php echo $riddle["author_id"]; ?>" class="user-role <?php echo_safe(isset($riddle["author_role"]) ? $riddle["author_role"] : "none", true); ?>"><?php echo_safe($riddle["author_username"]); ?></a>]</span>
                                </h2>
                            </header>
                            <p id="description"><?php echo($riddle["description"]); ?></p>
                            <footer>
                                <?php if (isset($_SESSION["uid"])): ?>
                                    <?php if ($_SESSION["uid"] != $riddle["author_id"]): ?>
                                        <?php if (isset($_POST["riddle-code"])): ?>
                                            <div id="wrong-code" class="message-container">
                                                <div class="message error">
                                                    <p>Ce code n'est pas correct...</p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <h3>Tu as trouvé le code ?</h3>
                                        <form method="post" action="#wrong-code">
                                            <input id="code" type="text" name="riddle-code" placeholder="Code" maxlength="16" oninput="formatCode()">
                                            <button type="submit">
                                                <img src="/components/img/ui/send-code.png" alt="Bouton envoyer code">
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="message-container">
                                        <div class="message info">
                                            <p>Connecte-toi pour pouvoir entrer le code de cette énigme.</p>
                                            <p><a href="/fr/account/login">Se connecter</a></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div id="lifetime">Temps restant : <?php echo ($riddle["time_left"] < 0 ? "- " : "").(new DateTime("@0"))->diff(new DateTime("@".$riddle["time_left"]))->format("%aj %hh %im %ss"); ?></div>
                            </footer>
                        </article>
                    </div>
                    <div id="comment-wrapper">
                        <div id="comment-box">
                            <div id="comment-container">
                                <p id="no-comment">Aucun commentaire</p>
                            </div>
                            <button id="more-comments" type="button" onclick="loadMoreComments()">
                                <img src="/components/img/ui/more-comments.png" alt="Bouton plus de commentaires">
                            </button>
                            <img id="loading-wheel" src="/components/img/ui/loading-comments.gif" alt="Roue chargement commentaires">
                            <?php if (isset($_SESSION["uid"])): ?>
                                <div id="comment-error" class="message-container"></div>
                                <div id="comment-bar">
                                    <input id="comment-input" type="text" placeholder="Commentaire" onkeypress="if (event.keyCode == 13) postComment()" maxlength="128">
                                    <button type="button" onclick="postComment()">
                                        <img src="/components/img/ui/send-comment.png" alt="Bouton envoyer commentaire">
                                    </button>
                                </div>
                            <?php endif; ?>
                            <script type="text/javascript">
                            var descriptionP = document.getElementById("description");
                            descriptionP.onload = formatDescription();
                            var codeInput = document.getElementById("code");
    
                            function formatDescription() {
                                var raw = descriptionP.innerHTML;
                                var preview = descriptionP.innerHTML;
    
                                var iMatches = raw.match(/\[\i:.*?\]/g);
                                if (iMatches != null) {
                                    for (var i = 0; i < iMatches.length; i++) {
                                        var img = new Image();
                                        img.src = iMatches[i].replace("[i:", "").replace("]", "");
                                        preview = preview.replace(iMatches[i], img.outerHTML);
                                    }
                                }
                                var cMatches = raw.match(/\[c#[a-f0-9]{6}:.*?\]/g);
                                if (cMatches != null) {
                                    for (var i = 0; i < cMatches.length; i++) {
                                        var span = document.createElement("span");
                                        span.innerHTML = cMatches[i].replace(/\[c#[a-f0-9]{6}:/, "").replace("]", "");
                                        span.style = "color: " + cMatches[i].replace("[c", "").replace(/:.*?\]/g, "") + ";";
                                        preview = preview.replace(cMatches[i], span.outerHTML);
                                    }
                                }
                                descriptionP.innerHTML = preview;
                            }
                            function formatCode() {
                                codeInput.value = codeInput.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
                            };
    
                            var selectStart = 0;
                            var minTimeElapsed = 0;
                            function showErrorMessage(message) {
                                var commentErrorContainer = document.getElementById("comment-error");
                                var messageError = document.createElement("div");
                                messageError.className = "message error";
                                var messageP = document.createElement("p");
                                messageP.innerHTML = message;
                                messageError.appendChild(messageP);
                                commentErrorContainer.appendChild(messageError);
                            }
                            function addComment(authorId, authorUsername, authorIconId, authorIconLevel, authorRole, content, timeElapsed, end = true) {
                                var commentContainer = document.getElementById("comment-container");
    
                                var commentDiv = document.createElement("div");
                                commentDiv.className = "comment";
    
                                var authorAnchor = document.createElement("a");
                                authorAnchor.className = "user-role " + (authorRole == null ? "none" : authorRole);
                                authorAnchor.href = "/fr/account/profile?u=" + authorId;
                                authorAnchor.innerHTML = authorUsername;
    
                                if (authorIconId != null) {
                                    var authorIconImg = document.createElement("img");
                                    authorIconImg.src = "/components/img/animals/" + authorIconId + ".png";
                                    authorIconImg.alt = "Icône " + authorUsername;
                                    authorIconImg.style.background = "url(/components/img/placeholders/" + authorIconLevel + ".gif)";
                                    authorIconImg.style.backgroundSize = "contain";
                                    authorIconImg.style.marginRight = "7px";
                                    authorAnchor.insertBefore(authorIconImg, authorAnchor.firstChild);
                                } else authorAnchor.style.paddingLeft = "39px";
    
                                var contentSpan = document.createElement("span");
                                contentSpan.innerHTML = content;
    
                                var timeElapsedSpan = document.createElement("span");
                                timeElapsedSpan.className = "time-elapsed";
                                timeElapsedSpan.innerHTML = "(il y a " +
                                    (Math.floor(timeElapsed / 86400) > 0 ? Math.floor(timeElapsed / 86400) + "j " : "") +
                                    (Math.floor(timeElapsed / 3600) % 24 > 0 ? Math.floor(timeElapsed / 3600) % 24 + "h " : "") +
                                    (Math.floor(timeElapsed / 60) % 60 ? Math.floor(timeElapsed / 60) % 60 + "m " : "") +
                                    timeElapsed % 60 + "s)";
    
                                commentDiv.appendChild(authorAnchor);
                                commentDiv.appendChild(contentSpan);
                                commentDiv.appendChild(timeElapsedSpan);
    
                                if (end) commentContainer.appendChild(commentDiv);
                                else commentContainer.insertBefore(commentDiv, commentContainer.firstChild);
                            }
                            function loadMoreComments() {
                                var loadingWheelImg = document.getElementById("loading-wheel");
                                loadingWheelImg.style.display = "inline";
                                var moreCommentsButton = document.getElementById("more-comments");
                                moreCommentsButton.style.display = "none";
    
                                const xhr = new XMLHttpRequest();
                                xhr.open("get", "comments?r=<?php echo $riddle["id"] ?>&s=" + selectStart + "&m=" + minTimeElapsed);
                                xhr.onload = function() {
                                    try {
                                        if (xhr.responseText != "[]") {
                                            const json = JSON.parse(xhr.responseText);
                                            for (var k = 0; k < json.length; k++) {
                                                if (selectStart == 0 && k == 0) minTimeElapsed = json[k]["time_elapsed"];
                                                addComment(json[k]["author_id"], json[k]["author_username"], json[k]["author_icon_id"], json[k]["author_icon_level"], json[k]["author_role"], json[k]["content"], json[k]["time_elapsed"]);
                                            }
                                            selectStart++;
                                            document.getElementById("no-comment").style.display = "none";
                                            if (json.length == 5) moreCommentsButton.style.display = "inline";
                                        }
                                    } catch (e) {
                                        console.warn("Erreur ! " + e);
                                    } finally {
                                        loadingWheelImg.style.display = "none";
                                    }
                                }
                                xhr.send();
                            }
                            function postComment() {
                                var commentInput = document.getElementById("comment-input");
                                if (commentInput.value != "") {
                                    const xhr = new XMLHttpRequest();
                                    xhr.open("POST", "comments");
                                    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                                    xhr.onload = function() {
                                        try {
                                            switch (xhr.responseText) {
                                                case "!s":
                                                    showErrorMessage("Tu dois être connecté(e) pour pouvoir envoyer un commentaire ! <a href='/fr/account/login'>Se connecter</a>");
                                                    document.getElementById("comment-bar").style.display = "none";
                                                    break;
                                                case "!c":
                                                    showErrorMessage("Oups, une erreur est survenue lors de l'envoi de ton commentaire...");
                                                    break;
                                                case "!l":
                                                    showErrorMessage("Hep hep hep ! Ton commentaire peut comporter au maximum 128 caractères, pas plus !");
                                                    break;
                                                default:
                                                    const json = JSON.parse(xhr.responseText);
                                                    addComment(json["author_id"], json["author_username"], json["author_icon_id"], json["author_icon_level"], json["author_role"], json["content"], json["time_elapsed"], false);
                                                    document.getElementById("no-comment").style.display = "none";
                                            }
                                        } catch (e) { console.warn("Erreur !" + e); }
                                        commentInput.value = "";
                                    }
                                    xhr.send("riddle_id=<?php echo $riddle["id"]; ?>&content=" + commentInput.value.replace(/&/g, ""));
                                }
                            }
    
                            document.onload = loadMoreComments();
                            </script>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="message-container">
                    <div class="message error">
                        <p>Cette énigme n'existe pas, a expiré ou a déjà été résolue.</p>
                    </div>
                </div>
            <?php endif; ?>
        </main>
        <?php html_parts_footer(); ?>
    </body>
</html>
