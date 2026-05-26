<?php
// api/get_reviews.php — AJAX: devuelve valoraciones de clientes en JSON
require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');

$db = getDB();
$reviews = $db->query("
    SELECT r.rating, r.comment, r.created_at, c.customer_name, cu.nationality
    FROM review r
    JOIN customer c ON r.customer_id = c.customer_id
    LEFT JOIN customer cu ON cu.customer_id = r.customer_id
    WHERE r.visible = 1
    ORDER BY r.created_at DESC
    LIMIT 10
")->fetchAll();

echo json_encode(['reviews' => $reviews]);
