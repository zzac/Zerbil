<?php
require "../../components/scripts/php/db.php";

if (valid_requests($_POST["riddle_id"], $_POST["content"])) {
    if (isset($_SESSION["uid"])) {
        if (strlen($_POST["content"]) <= 128) {
            $stmt = $_pdo->prepare("INSERT INTO comments (riddle_fk, author_fk, content, creation_date) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
            if ($stmt->execute([$_POST["riddle_id"], $_SESSION["uid"], $_POST["content"]])) {
                $stmt = $_pdo->prepare("SELECT users.username, users.icon_fk AS icon_id, users.role, animals.level AS icon_level FROM users LEFT JOIN animals ON users.icon_fk = animals.id WHERE users.id = ?");
                $stmt->execute([$_SESSION["uid"]]);
                $author_infos = $stmt->fetch();
                echo_safe(json_encode(array(
                    "content" => $_POST["content"],
                    "time_elapsed" => 0,
                    "author_id" => $_SESSION["uid"],
                    "author_username" => $author_infos["username"],
                    "author_icon_id" => $author_infos["icon_id"],
                    "author_role" => $author_infos["role"],
                    "author_icon_level" => $author_infos["icon_level"]
                )));
            } else echo "!c";
        } else echo "!l";
    } else echo "!s";
} else if (valid_requests($_GET["r"], $_GET["s"], $_GET["m"])) {
    $stmt = $_pdo->prepare(
        "SELECT comments.content, TIMESTAMPDIFF(SECOND, comments.creation_date, NOW()) AS time_elapsed, users.id AS author_id, users.username AS author_username, users.icon_fk AS author_icon_id, users.role AS author_role, animals.level AS author_icon_level
        FROM comments JOIN users ON comments.author_fk = users.id LEFT JOIN animals ON users.icon_fk = animals.id
        WHERE comments.riddle_fk = ? AND TIMESTAMPDIFF(SECOND, comments.creation_date, NOW()) >= ?
        ORDER BY comments.creation_date DESC LIMIT ?, 5"
    );
    $stmt->execute([$_GET["r"], $_GET["m"], 5 * (int)$_GET["s"]]);
    echo_safe(json_encode($stmt->fetchAll()));
}
?>
