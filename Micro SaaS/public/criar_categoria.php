<?php
session_start();
require_once '../config/database.php';

$company_id = $_SESSION['company_id'];

$name = $_POST['name'] ?? null;
$color = $_POST['color'] ?? "#3b82f6";

if(!$name){
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["erro"=>"Nome vazio"]);
    exit;
}

$stmt = $pdo->prepare(
"INSERT INTO categories
(company_id, name, color)
VALUES (?, ?, ?)"
);

$stmt->execute([
    $company_id,
    $name,
    $color
]);

$id = $pdo->lastInsertId();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    "id"=>$id,
    "name"=>$name,
    "color"=>$color
]);
exit;