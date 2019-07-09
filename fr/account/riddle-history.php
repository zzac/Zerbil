<?php
require "../../components/scripts/php/db.php";

if (valid_requests($_GET["u"], $_GET["s"], $_GET["m"])) {
    $stmt = $_pdo->prepare(
        "SELECT riddle_history.reward_fk AS reward_id, riddle_history.level, riddle_history.name, TIMESTAMPDIFF(SECOND, riddle_history.solving_date, NOW()) AS time_elapsed, users.id AS author_id, users.username AS author_username, users.role AS author_role, animals.level AS reward_level, animals.name_fr AS reward_name_fr
        FROM riddle_history JOIN users ON riddle_history.author_fk = users.id LEFT JOIN animals ON riddle_history.reward_fk = animals.id
        WHERE riddle_history.solver_fk = ? AND TIMESTAMPDIFF(SECOND, riddle_history.solving_date, NOW()) >= ?
        ORDER BY riddle_history.solving_date DESC LIMIT ?, 5"
    );
    $stmt->execute([$_GET["u"], $_GET["m"], 5 * (int)$_GET["s"]]);
    echo json_encode($stmt->fetchAll());
}
?>
