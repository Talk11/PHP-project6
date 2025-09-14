<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Получение списка задач
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tasks);
        break;

    case 'POST':
        // Добавление задачи
        $data = json_decode(file_get_contents('php://input'), true);
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $status = $data['status'] ?? 'pending';

        if (!empty($title) && in_array($status, ['pending', 'completed'])) {
            $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, status) VALUES (:user_id, :title, :description, :status)");
            $stmt->execute([
                'user_id' => $_SESSION['user_id'],
                'title' => $title,
                'description' => $description,
                'status' => $status
            ]);
            echo json_encode(['message' => 'Task created', 'id' => $pdo->lastInsertId()]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid title or status']);
        }
        break;

    case 'PUT':
        // Обновление задачи
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $status = $data['status'] ?? '';

        if ($id && !empty($title) && in_array($status, ['pending', 'completed'])) {
            $stmt = $pdo->prepare("UPDATE tasks SET title = :title, description = :description, status = :status WHERE id = :id AND user_id = :user_id");
            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'id' => $id,
                'user_id' => $_SESSION['user_id']
            ]);
            if ($stmt->rowCount()) {
                echo json_encode(['message' => 'Task updated']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Task not found or not yours']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
        }
        break;

    case 'DELETE':
        // Удаление задачи
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;

        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id AND user_id = :user_id");
            $stmt->execute(['id' => $id, 'user_id' => $_SESSION['user_id']]);
            if ($stmt->rowCount()) {
                echo json_encode(['message' => 'Task deleted']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Task not found or not yours']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid task ID']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>