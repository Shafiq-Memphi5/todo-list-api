<?php
header("Content-Type: application/json");
require_once('db.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(400);
    echo json_encode([
        'error' => 'invalid method'
    ]);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $plain_pw = trim($data['password'] ?? '');

    if (empty($name) || empty($email) || empty($plain_pw)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'all keys require values'
        ]);
        exit;
    }

    $hash_pw = password_hash($plain_pw, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (name, email, password) VALUES (?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $email, $hash_pw);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'user was not added'
        ]);
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'message' => 'user was added'
    ]);
    exit;
}