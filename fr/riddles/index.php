<?php
require "../../components/scripts/php/db.php";
require "../../components/scripts/php/html-parts-fr.php";

if (!isset($_SESSION["uid"])) {
    header("Location: search");
    die();
}
?>

<!DOCTYPE html>
<html lang="fr">
    <?php html_parts_head("Énigmes", "Résous des énigmes afin de trouver des lieux précis dans lesquels sont cachées des récompenses ! Tu peux chercher des énigmes près de chez toi ou bien en créer.", "riddle-home"); ?>
    <body>
        <?php html_parts_header("riddles"); ?>
        <main id="page-main">
            <div id="riddle-choice-container">
                <p>Recherche des énigmes près de chez toi.</p>
                <img src="/components/img/ui/double-down.png" alt="Double flèche bas">
                <a href="search">
                    <img src="/components/img/ui/search.png" alt="Icône recherche d'énigmes">
                    Rechercher une énigme
                </a>
                <a href="create">
                    <img src="/components/img/ui/create.png" alt="Icône création d'énigmes">
                    Créer une énigme
                </a>
                <img src="/components/img/ui/double-up.png" alt="Double flèche haut">
                <p>Ou bien crée de toutes pièces une énigme.</p>
            </div>
        </main>
        <?php html_parts_footer(); ?>
    </body>
</html>
