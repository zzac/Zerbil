<?php
require "../../components/scripts/php/db.php";
require "../../components/scripts/php/html-parts-fr.php";

// Redirect the user if he is not logged in
if (!isset($_SESSION["uid"])) {
    header("Location: ./");
    die();
}

if (valid_requests($_POST["create-riddle-code"], $_POST["create-riddle-location"], $_POST["create-riddle-name"], $_POST["create-riddle-description"], $_POST["create-riddle-level"], $_POST["create-riddle-lifetime"])) {
    $errors = array();

    // If the code's size is invalid
    if (strlen($_POST["create-riddle-code"]) < 6 || strlen($_POST["create-riddle-code"]) > 16) array_push($errors, "La longueur du code est incorrecte.");

    // If the code is not alphanumeric
    if (!ctype_alnum($_POST["create-riddle-code"])) array_push($errors, "Le code ne peut contenir que des caractères alphanumériques.");

    // If the location's size is invalid
    if (strlen($_POST["create-riddle-location"]) < 6 || strlen($_POST["create-riddle-name"]) > 64) array_push($errors, "La longueur de la location est incorrecte.");

    // If the location contains illegal characters
    if (!preg_match("/^[0-9a-zA-ZÀ-ÿ ',\-]*$/", $_POST["create-riddle-location"])) array_push($errors, "La location contient un ou plusieurs caractère(s) incorrect(s).");

    // If the name's size is invalid
    if (strlen($_POST["create-riddle-name"]) < 6 || strlen($_POST["create-riddle-name"]) > 32) array_push($errors, "La longueur du nom est incorrecte.");

    // If the name contains illegal characters
    if (!preg_match("/^[0-9a-zA-ZÀ-ÿ ',\-]*$/", $_POST["create-riddle-name"])) array_push($errors, "Le nom contient un ou plusieurs caractère(s) incorrect(s).");

    // If the description's size is invalid
    if (strlen($_POST["create-riddle-description"]) < 16 || strlen($_POST["create-riddle-description"]) > 512) array_push($errors, "La longueur de la description est incorrecte.");

    // If the description contains illegal characters
    if (!preg_match("/^[\s\S]*$/", $_POST["create-riddle-description"])) array_push($errors, "La description contient un ou plusieurs caractère(s) incorrect(s).");

    // If the level is invalid
    if (!in_array($_POST["create-riddle-level"], [1, 2, 3, 4, 5])) array_push($errors, "Ce niveau est incorrect.");

    // If the lifetime is invalid
    if ((int)$_POST["create-riddle-lifetime"] < 7 || (int)$_POST["create-riddle-lifetime"] > 90) array_push($errors, "Cette durée de vie est incorrecte.");

    if (count($errors) == 0) {
        // If the name already exists
        $stmt = $_pdo->prepare("SELECT COUNT(1) FROM riddles WHERE name = ?");
        $stmt->execute([$_POST["create-riddle-name"]]);
        if ($stmt->fetch()["COUNT(1)"] > 0) array_push($errors, "Ce nom est déjà utilisé.");
    }
    if (count($errors) == 0) {
        $stmt = $_pdo->prepare("SELECT id FROM animals WHERE level = ? ORDER BY RAND() LIMIT 1");
        $stmt->execute([(int)$_POST["create-riddle-level"]]);
        $create_riddle_reward_id = $stmt->fetch()["id"];

        $stmt = $_pdo->prepare("INSERT INTO riddles (name, location, description, level, reward_fk, author_fk, hashed_code, expiration_date) VALUES (?, ?, ?, ?, ?, ?, SHA2(LOWER(?), 256), DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ? DAY))");
        $stmt->execute([$_POST["create-riddle-name"], $_POST["create-riddle-location"], str_replace("\r\n", "<br>", htmlspecialchars($_POST["create-riddle-description"])), (int)$_POST["create-riddle-level"], $create_riddle_reward_id, $_SESSION["uid"], $_POST["create-riddle-code"], (int)$_POST["create-riddle-lifetime"]]);
        $riddle_id = $_pdo->lastInsertId();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
    <?php html_parts_head("Créer une énigme", "Les créateurs d'énigmes font vivre Zerbil. Rejoins-les afin d'exprimer ta créativité et satisfaire la soif d'énigmes des autres joueurs !", "create"); ?>
    <body>
        <?php html_parts_header(); ?>
        <main id="page-main">
            <?php if (isset($riddle_id)): ?>
                <div class="message-container">
                    <div class="message success">
                        <p>Ton énigme a été créée avec succès !</p>
                        <p>Tu  peux la voir <a href="./read?r=<?php echo $riddle_id; ?>">ici</a>.</p>
                    </div>
                </div>
            <?php else: ?>
                <form id="create-box" method="post">
                    <h2>Créer une énigme</h2>
                    <?php if (isset($errors) && count($errors) > 0): ?>
                        <div class="message-container">
                            <div class="message error">
                                <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="step-box">
                        <p>
                            <span class="number">1</span>
                            Cache le code de ton énigme.
                        </p>
                        <p class="example">Exemple : j'ai inscrit le code de mon énigme sur une pierre au pied d'un arbre.</p>
                    </div>
                    <div id="step2" class="step-box">
                        <p>
                            <span class="number">2</span>
                            Quel-est ce code ?
                        </p>
                        <p class="example">Exemple : "<span class="value">Z3RB1L</span>".</p>
                        <input id="code" type="text" name="create-riddle-code" placeholder="Code" <?php if (valid_requests($_POST["create-riddle-code"])): ?>value="<?php echo_safe($_POST["create-riddle-code"], true); ?>"<?php endif; ?> oninput="formatCode(); checkInputs(0)" maxlength="16">
                        <div id="error-code-message" class="error"></div>
                    </div>
                    <div id="step3" class="step-box">
                        <p>
                            <span class="number">3</span>
                            Bien, à quel endroit ton énigme se trouve-t-elle ?<br>
                            Attention ! la location sert seulement à informer les joueurs que ton énigme est proche de chez eux ou non, elle ne doit pas indiquer le lieu précis où a été caché le code de ton énigme.
                        </p>
                        <p class="example">Exemple : le code que j'ai caché se trouve dans le boulevard des Anglais, à Nantes, en France, donc j'entre "<span class="value">France, Nantes, Boulevard des Anglais</span>".</p>
                        <input id="location" type="text" name="create-riddle-location" placeholder="Location" <?php if (valid_requests($_POST["create-riddle-location"])): ?>value="<?php echo_safe($_POST["create-riddle-location"], true); ?>"<?php endif; ?> oninput="checkInputs(1)" maxlength="64">
                        <div id="error-location-message" class="error"></div>
                    </div>
                    <div id="step4" class="step-box">
                        <p>
                            <span class="number">4</span>
                            Comment ton énigme s'appelle-t-elle ?<br>
                            Son nom ne doit pas être vulgaire ou offensant.
                        </p>
                        <p class="example">Exemple : "<span class="value">La pierre cachée</span>".</p>
                        <input id="name" type="text" name="create-riddle-name" placeholder="Nom" <?php if (valid_requests($_POST["create-riddle-name"])): ?>value="<?php echo_safe($_POST["create-riddle-name"], true); ?>"<?php endif; ?> oninput="checkInputs(2)" maxlength="32">
                        <div id="error-name-message" class="error"></div>
                    </div>
                    <div id="step5" class="step-box">
                        <p>
                            <span class="number">5</span>
                            Quelle-est la description de ton énigme ?<br>
                            Cette description sera le pilier de ton énigme car elle permettra aux joueurs de trouver le lieu précis où a été caché le code.<br>
                            Un conseil : donne-lui un air mystérieux.
                        </p>
                        <p class="example">Exemple : "<span class="value">À l'ombre d'un arbre dépourvu de branches tu me trouveras</span>".</p>
                        <div id="description-box">
                            <textarea id="description" name="create-riddle-description" rows="8" cols="50" placeholder="Description" oninput="checkInputs(3); previewDescription();" maxlength="512"><?php if (valid_requests($_POST["create-riddle-description"])) echo_safe($_POST["create-riddle-description"]); ?></textarea>
                            <p id="description-preview"></p>
                            <div id="tool-bar">
                                <button type="button" onclick="appendColorPattern()">
                                    <img src="/components/img/ui/color.png" alt="Bouton couleur" title="Ajouter un texte coloré">
                                </button>
                                <button type="button" onclick="appendImagePattern()">
                                    <img src="/components/img/ui/image.png" alt="Bouton image" title="Ajouter une image">
                                </button>
                            </div>
                        </div>
                        <div id="error-description-message" class="error"></div>
                    </div>
                    <div class="step-box">
                        <p>
                            <span class="number">6</span>
                            Tu as presque fini ! Quel-est le niveau de ton énigme ?<br>
                        </p>
                        <p class="example">Exemple : "<span class="value">Très facile</span>".</p>
                        <select name="create-riddle-level">
                            <?php foreach (array(1 => "Très facile", 2 => "Facile", 3 => "Moyen", 4 => "Difficile", 5 => "Hardcore") as $l => $level): ?>
                                <option value="<?php echo $l; ?>" <?php if (valid_requests($_POST["create-riddle-level"]) && $_POST["create-riddle-level"] == $l): ?>selected<?php endif; ?>><?php echo $level; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="step-box">
                        <p>
                            <span class="number">7</span>
                            Dernière étape : Quelle-est la durée de vie de ton énigme ?<br>
                        </p>
                        <p class="example">Exemple : "<span class="value">1 semaine</span>".</p>
                        <select name="create-riddle-lifetime">
                            <?php foreach (array(7 => "1 semaine", 14 => "2 semaines", 30 => "1 mois", 60 => "2 mois") as $l => $lifetime): ?>
                                <option value="<?php echo $l; ?>" <?php if (valid_requests($_POST["create-riddle-lifetime"]) && $_POST["create-riddle-lifetime"] == $l): ?>selected<?php endif; ?>><?php echo $lifetime; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input id="submit-riddle" type="submit" value="Publier mon énigme">
                    <script type="text/javascript">
                    var submitRiddleButton = document.getElementById("submit-riddle");

                    var codeInput = document.getElementById("code");
                    var errorCodeSpan = document.getElementById("error-code-message");

                    var locationInput = document.getElementById("location");
                    var errorLocationSpan = document.getElementById("error-location-message");

                    var nameInput = document.getElementById("name");
                    var errorNameSpan = document.getElementById("error-name-message");

                    var descriptionInput = document.getElementById("description");
                    var errorDescriptionSpan = document.getElementById("error-description-message");
                    var descriptionPreviewP = document.getElementById("description-preview");

                    function formatCode() {
                        codeInput.value = codeInput.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
                    };

                    const stepArray = [
                        [codeInput, 6, errorCodeSpan, /^[0-9A-Z]*$/],
                        [locationInput, 6, errorLocationSpan, /^[0-9a-zA-ZÀ-ÿ ',\-]*$/],
                        [nameInput, 6, errorNameSpan, /^[0-9a-zA-ZÀ-ÿ ',\-]*$/],
                        [descriptionInput, 16, errorDescriptionSpan, /^[\s\S]*$/]
                    ];

                    function checkInputs(informIndex) {
                        var allInputsOK = true;
                        for (var i = 0; i < 4; i++) {
                            var tooShort = stepArray[i][0].value.length < stepArray[i][1];
                            var invalidChars = !stepArray[i][3].test(stepArray[i][0].value);
                            if (informIndex == i) {
                                if (stepArray[i][0].value.length > 0) {
                                    document.getElementById("step" + (i + 2)).className = "step-box" + (tooShort || invalidChars ? " invalid" : "");
                                    stepArray[i][2].innerHTML = (tooShort ? "Trop court" : "") + (tooShort && invalidChars ? " & " : "") + (invalidChars ? "Caractère(s) invalide(s)" : "");
                                } else {
                                    document.getElementById("step" + (i + 2)).className = "step-box";
                                    stepArray[i][2].innerHTML = "";
                                }

                            }
                            if (allInputsOK && (tooShort || invalidChars)) allInputsOK = false;
                        }
                        submitRiddleButton.disabled = !allInputsOK;
                    }

                    function previewDescription() {
                        var raw = descriptionInput.value;
                        var preview = descriptionInput.value;

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
                        descriptionPreviewP.innerHTML = preview.replace(/\n/g, "<br>");
                    }
                    function appendImagePattern() {
                        descriptionInput.insertAtCaret("[i:URL]");
                        previewDescription();
                        checkInputs(3);
                    };
                    function appendColorPattern() {
                        descriptionInput.insertAtCaret("[c#ffffff:TEXT]");
                        previewDescription();
                        checkInputs(3);
                    };

                    HTMLTextAreaElement.prototype.insertAtCaret = function(text) {
                        text = text || "";
                        if (document.selection) {
                            // IE
                            this.focus();
                            var sel = document.selection.createRange();
                            sel.text = text;
                        } else if (this.selectionStart || this.selectionStart == 0) {
                            // Others
                            var startPos = this.selectionStart;
                            var endPos = this.selectionEnd;
                            this.value = this.value.substring(0, startPos) + text + this.value.substring(endPos, this.value.length);
                            this.selectionStart = startPos + text.length;
                            this.selectionEnd = startPos + text.length;
                        } else this.value += text;
                    };
    
                    document.onload = checkInputs();
                    </script>
                </form>
            <?php endif; ?>
        </main>
        <?php html_parts_footer(); ?>
    </body>
</html>
