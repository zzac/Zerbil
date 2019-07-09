<?php
require "../../components/scripts/php/db.php";
require "../../components/scripts/php/html-parts-fr.php";

$q = valid_requests($_GET["q"]) ? htmlspecialchars($_GET["q"]) : "";
$page = (valid_requests($_GET["p"]) && ctype_digit($_GET["p"])) ? $_GET["p"] : 1;
$like_query = "%".$q."%";

// Number of results
$stmt = $_pdo->prepare(
    "SELECT COUNT(1)
    FROM riddles
    WHERE riddles.name LIKE ? OR riddles.location LIKE ? OR riddles.description LIKE ?"
);
$stmt->execute([$like_query, $like_query, $like_query]);
$result_count = $stmt->fetch()["COUNT(1)"];

// Get the riddles
$stmt = $_pdo->prepare(
    "SELECT riddles.id, riddles.name, riddles.location, riddles.level, TIMESTAMPDIFF(SECOND, NOW(), riddles.expiration_date) AS time_left
    FROM riddles
    WHERE riddles.name LIKE ? OR riddles.location LIKE ? OR riddles.description LIKE ?
    ORDER BY ".(valid_requests($_GET["s"]) && $_GET["s"] == "d" ? "riddles.level" : "riddles.expiration_date").(valid_requests($_GET["o"]) && $_GET["o"] == "d" ? " DESC" : " ASC")." LIMIT ?, ?"
);
$stmt->execute([$like_query, $like_query, $like_query, 10 * $page - 10, 10]);
$riddles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
    <?php html_parts_head("Rechercher une énigme", "Trouve des énigmes près de chez toi. Tu peux les trier selon leurs niveaux de difficulté ou leurs dates d'expiration.", "search"); ?>
    <body>
        <?php html_parts_header(); ?>
        <main id="page-main">
            <div id="search-wrapper">
                <form id="search-box" action="search" method="get">
                    <div id="search-bar">
                        <input type="text" name="q" placeholder="Recherche" value="<?php echo $q; ?>">
                        <button type="submit">
                            <img src="/components/img/ui/search.png" alt="Rechercher">
                        </button>
                        <button type="button" id="search-options-button" onclick="toggleSearchOptions()">
                            <img src="/components/img/ui/search-options.png" alt="Bouton options recherche">
                        </button>
                    </div>
                    <script type="text/javascript" src="/components/scripts/js/toggle-search-options.js"></script>
                    <div id="search-options">
                        <fieldset>
                            <legend>Trier par :</legend>
                            <label>
                                <input type="radio" name="s" value="e" <?php if (!valid_requests($_GET["s"]) || $_GET["s"] != "d"): ?>checked<?php endif; ?>><span>Date d'expiration</span>
                            </label>
                            |
                            <label>
                                <input type="radio" name="s" value="d" <?php if (valid_requests($_GET["s"]) && $_GET["s"] == "d"): ?>checked<?php endif; ?>><span>Difficulté</span>
                            </label>
                        </fieldset>
                        <fieldset>
                            <legend>Dans quel ordre ?</legend>
                            <label>
                                <input type="radio" name="o" value="a" <?php if (!valid_requests($_GET["o"]) || $_GET["o"] != "d"): ?>checked<?php endif; ?>><span>Croissant</span>
                            </label>
                            |
                            <label>
                                <input type="radio" name="o" value="d" <?php if (valid_requests($_GET["o"]) && $_GET["o"] == "d"): ?>checked<?php endif; ?>><span>Décroissant</span>
                            </label>
                        </fieldset>
                    </div>
                </form>
            </div>
            <div id="riddle-wrapper">
                <?php if (count($riddles) > 0): ?>
                    <div class="message-container">
                        <div class="message info">
                            <p><?php echo $result_count." énigme".($result_count > 1 ? "s ont été trouvées" : " a été trouvée"); ?></p>
                        </div>
                    </div>
                    <?php foreach ($riddles as $riddle): ?>
                        <a class="riddle-anchor" href="read?r=<?php echo $riddle["id"]; ?>">
                            <article class="box riddle">
                                <img id="level" class="pixelated" src="/components/img/levels/<?php echo $riddle["level"]; ?>.png" alt="Difficulté" title="Difficulté">
                                <header>
                                    <h2>
                                        <span class="location"><?php echo_safe($riddle["location"]); ?></span>
                                        <span class="name"><?php echo_safe($riddle["name"]); ?></span>
                                    </h2>
                                </header>
                                <div id="lifetime">Temps restant : <?php echo ($riddle["time_left"] < 0 ? "-" : "").(new DateTime("@0"))->diff(new DateTime("@".$riddle["time_left"]))->format("%aj %hh %im %ss"); ?></div>
                            </article>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="message-container">
                        <div class="message error">
                            <p>Aucune énigme n'a été trouvée...</p>
                        </div>
                    </div>
                <?php endif; ?>
                <nav id="page-navigation">
                    <?php if ($page > 1): ?>
                        <a id="previous" href="<?php echo "?q=".$q."&s=".(valid_requests($_GET["s"]) && $_GET["s"] == "d" ? "d" : "e")."&o=".($o = valid_requests($_GET["o"]) && $_GET["o"] == "d" ? "d" : "a")."&p=".($page - 1); ?>">
                            <img src="/components/img/ui/previous.png" alt="Page précédente" title="Page précédente">
                        </a>
                    <?php endif; ?>
                    <?php if ($page * 10 < $result_count): ?>
                        <a id="next" href="<?php echo "?q=".$q."&s=".(valid_requests($_GET["s"]) && $_GET["s"] == "d" ? "d" : "e")."&o=".($o = valid_requests($_GET["o"]) && $_GET["o"] == "d" ? "d" : "a")."&p=".($page + 1); ?>">
                            <img src="/components/img/ui/next.png" alt="Page suivante" title="Page suivante">
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </main>
        <?php html_parts_footer(); ?>
    </body>
</html>
