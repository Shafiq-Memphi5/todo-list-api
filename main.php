<?php
header('Content-Type: application/json');
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$baseFolder = '/todo_list_api'; 
$scriptName = 'main.php';

$endpoint = str_replace([$baseFolder, $scriptName], '', $request);
$endpoint = '/' . trim($endpoint, '/');

switch($endpoint)
{
    case '/login':
        require_once 'user_login.php';
        break;
    case '/register':
        require_once 'user_register.php';
        break;
    case '/todo':
        require_once 'user_todo_list.php';
        break;
    default:
        http_response_code(404);
        echo json_encode([
            'error'=> 'endpoint not supported',
            'requested_path' => $endpoint
        ]);
        break;
}