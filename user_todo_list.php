<?php
header("Content-Type: application/json");
require('db.php');

$method = $_SERVER['REQUEST_METHOD'];

function authenticate(mysqli $conn): int
{
    $headers = getallheaders();
    $auth = $headers['Authorization'];

    if (!preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'unauthorized: invalid token'
        ]);
        exit;
    }

    $token = $matches[1];

    $sql = "SELECT * FROM token WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'token does not exist'
        ]);
        exit;
    }

    $rows = $res->fetch_assoc();

    return (int) $rows['user_id'];
}

if ($method === 'GET') {
    $user_id = authenticate($conn);
    $id = $_GET['id'] ?? '';

    if ($id !== '') {
        $sql = "SELECT * FROM todo WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            http_response_code(400);
            echo json_encode([
                'error' => 'todo ID does not exist or belong to you'
            ]);
            exit;
        }

        $todo = $res->fetch_assoc();
        http_response_code(200);
        echo json_encode([
            "id" => $todo['id'],
            "title" => $todo['title'],
            "description" => $todo['description']
        ]);
        exit;
    }

    $sql = "SELECT * FROM todo WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'you do not have any todos'
        ]);
        exit;
    }

    $todos = $res->fetch_all(MYSQLI_ASSOC);

    http_response_code(200);
    echo json_encode($todos);
    exit;
}

if ($method === 'POST') {
    $user_id = authenticate($conn);
    $data = json_decode(file_get_contents('php://input'), true);

    $title = trim($data['title'] ?? '');
    $desc = trim($data['description'] ?? '');

    if (empty($title) || empty($desc)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'both title and description are needed'
        ]);
        exit;
    }

    $sql = "INSERT INTO todo (title, description, user_id) VALUES (?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $title, $desc, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'todo not added'
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'message' => 'todo added successfully'
    ]);
    exit;
}

if ($method === 'PUT') {
    $user_id = authenticate($conn);
    $id = $_GET['id'] ?? '';

    if ($id === '') {
        http_response_code(400);
        echo json_encode([
            'message' => 'ID needed for editing'
        ]);
        exit;
    }

    $sql = "SELECT * FROM todo WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'todo does not exit or belong to you'
        ]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $title = trim($data['title'] ?? '');
    $desc = trim($data['description'] ?? '');

    if ($title === '' && $desc === '') {
        http_response_code(400);
        echo json_encode([
            'error' => 'todo needs values to be updated'
        ]);
        exit;
    }

    $fields = [];
    $values = [];
    $types = '';

    //if (!empty($title))
    if ($title !== '') {
        $fields[] = "title = ?";
        $values[] = $title;
        $types .= 's';
    }
    //if (!empty($desc))
    if ($desc !== '') {
        $fields[] = "description = ?";
        $values[] = $desc;
        $types .= 's';
    }

    $values[] = $id;
    $types .= 'i';
    $values[] = $user_id;
    $types .= 'i';

    $sql = "UPDATE todo SET " . implode(",", $fields) . " WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'message' => 'update failed try again'
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'message' => 'todo updated successfully'
    ]);
    exit;
}

if ($method === 'DELETE') {
    $user_id = authenticate($conn);
    $id = $_GET['id'] ?? '';

    if ($id === '') {
        http_response_code(400);
        echo json_encode([
            'message' => 'ID needed for delete'
        ]);
        exit;
    }

    $sql = "DELETE FROM todo WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'message' => 'delete failed try again'
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'message' => 'todo deleted successfully'
    ]);
    exit;
}