<?php
header("Content-Type: application/json");

$server = "localhost";
$user = "root";
$pw = "1234";
$db = "todo_list_api";
$port = 3307;

$conn = mysqli_connect($server, $user, $pw, $db, $port);
/*
if (mysqli_connect_error()) {
    echo json_encode([
        'error' => 'database connection unsuccessful'
    ]);
    exit;
}

echo json_encode([
    'message' => 'database connection successful'
]);
exit;
*/