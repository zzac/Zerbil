<?php
require "../components/scripts/php/html-parts-fr.php";
?>

<!DOCTYPE html>
<html lang="fr">
    <?php html_parts_head("Accueil", "Zerbil est un site d'énigmes grandeur nature. Résous des énigmes afin de trouver des lieux précis dans lesquels sont cachées des récompenses !", "home"); ?>
    <body>
        <?php html_parts_header("home"); ?>
        <main id="page-main">
            <div id="home">
                <h2>Bienvenue sur Zerbil !</h2>
                <article id="rules">
                    <h3>Le but du jeu est simple :</h3>
                    <ol>
                        <li>Cherche une énigme près de chez toi sur cette <a href="riddles/search">page</a>. Attention ! certaines sont plus difficiles que d'autres. Tu peux voir les différents niveaux de difficulté <a href="#levels">ici</a>.</li>
                        <li>La résolution d'une énigme te mènera à un lieu précis sur lequel sera inscrit un code (ex : sur un papier collé sous un banc).</li>
                        <li>Entre ce code sur la page de l'énigme que tu as résolue.</li>
                        <li>Tu recevras alors une <a href="#reward">récompense</a> si le code est correct. Les énigmes sont donc en quelque sorte des jeux de piste.</li>
                    </ol>
                </article>
                <article id="levels">
                    <h3>Niveaux de difficulté :</h3>
                    <p>Pour corser le jeu, les énigmes possèdent différents niveaux de difficulté.</p>
                    <p>Leurs icônes permettent de les différencier :</p>
                    <ul>
                        <li><img class="pixelated" src="/components/img/levels/1.png" alt="Très facile">Très facile</li>
                        <li><img class="pixelated" src="/components/img/levels/2.png" alt="Facile">Facile</li>
                        <li><img class="pixelated" src="/components/img/levels/3.png" alt="Moyen">Moyen</li>
                        <li><img class="pixelated" src="/components/img/levels/4.png" alt="Difficile">Difficile</li>
                        <li><img class="pixelated" src="/components/img/levels/5.png" alt="Hardcore">Hardcore</li>
                    </ul>
                </article>
                <article id="reward">
                    <h3>Les récompenses :</h3>
                    <p>Chaque énigme résolue donne un animal.<br>
                        Les animaux sont plus ou moins rares et leur rareté dépend très généralement du niveau de difficulté de l'énigme résolue.
                    </p>
                    <p>Tu peux collectionner les animaux et les choisir en tant qu'icône de profil, visible des autres joueurs.</p>
                </article>
            </div>
        </main>
        <?php html_parts_footer(); ?>
    </body>
</html>
