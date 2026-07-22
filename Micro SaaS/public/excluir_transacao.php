<?php

session_start();
require_once '../config/database.php';

$company_id = $_SESSION['company_id'];

$id = $_POST['id'] ?? null;

if(!$id){
    echo "Erro: ID inválido";
    exit;
}

/* =========================
   DELETE DIRETO
========================= */

$stmt = $pdo->prepare("
    DELETE FROM transactions
    WHERE id = ? AND company_id = ?
");

$stmt->execute([$id, $company_id]);

echo "OK";
exit;