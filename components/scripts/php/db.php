<?php
session_name("SESSID");
session_start();

// Connect to the database
try {
    $_pdo = new PDO("mysql:host=db5000095983.hosting-data.io;dbname=dbs90615;charset=utf8", "dbu301196", "FAKEPASS", [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo "Database connection error !<br>".$e->getMessage();
    die();
}

// Check if the user still exists
if (isset($_SESSION["uid"])) {
    $stmt = $_pdo->prepare("SELECT COUNT(1), hashed_confirmation_key FROM users WHERE id = ?");
    $stmt->execute([$_SESSION["uid"]]);
    $user_session_check = $stmt->fetch();
    if ($user_session_check["COUNT(1)"] == 0 || isset($user_session_check["hashed_confirmation_key"])) session_destroy();
}

// Valid requests
function valid_requests(&...$requests) {
    foreach ($requests as $request)
        if (!isset($request) || is_array($request))
            return false;
    return true;
}

// Echo safe
function echo_safe($text, $quotes = false) {
    echo htmlspecialchars($text, $quotes ? ENT_QUOTES : ENT_NOQUOTES);
}
?>
