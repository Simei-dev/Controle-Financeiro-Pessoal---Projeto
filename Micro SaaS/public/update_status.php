<?php

session_start();
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'];
$status = $data['status'];
$company_id = $_SESSION['company_id'];

$stmt = $pdo->prepare("
UPDATE transactions
SET status = ?
WHERE id = ?
AND company_id = ?
");

$stmt->execute([$status,$id,$company_id]);

echo json_encode([
"success"=>true
]);