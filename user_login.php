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

    $email = trim($data['email'] ?? '');
    $plain_pw = trim($data['password'] ?? '');

    if (empty($email) || empty($plain_pw)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'all keys require values'
        ]);
        exit;
    }

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if (!$user || !password_verify($plain_pw, $user['password'])) {
        http_response_code(400);
        echo json_encode([
            'error' => 'invalid credentials'
        ]);
        exit;
    }

    $user_id = $user['id'];
    $token = bin2hex(random_bytes(32));

    $sql = "SELECT * FROM token WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $sql = "INSERT INTO token (token, user_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $token, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            http_response_code(500);
            echo json_encode([
                'error' => 'could not process token'
            ]);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'message' => 'login successful',
            'token' => $token
        ]);
        exit;
    }

    $sql = "UPDATE token SET token = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $token, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(500);
        echo json_encode([
            'error' => 'could not process token'
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'message' => 'login successful',
        'token' => $token
    ]);
    exit;
}