<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'quiz_app');

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

function displayError($message) {
    return "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>
                <span class='block sm:inline'>$message</span>
            </div>";
}

function displaySuccess($message) {
    return "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>
                <span class='block sm:inline'>$message</span>
            </div>";
}
?>

