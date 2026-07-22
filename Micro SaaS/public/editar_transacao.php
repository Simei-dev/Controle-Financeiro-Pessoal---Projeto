<?php

session_start();
require_once '../config/database.php';

$company_id = $_SESSION['company_id'];

$id          = $_POST['id'] ?? null;
$tipo        = $_POST['tipo'] ?? null;
$descricao   = $_POST['descricao'] ?? null;
$valor       = $_POST['valor'] ?? null;
$data        = $_POST['data'] ?? null;
$category_id = $_POST['category_id'] ?? null;
$client     = $_POST['client'] ?? null;


/* =========================
   Corrigir valor monetário
========================= */

$valor = str_replace('.', '', $valor);
$valor = str_replace(',', '.', $valor);


/* =========================
   Validação
========================= */

if (!$id) {
    echo "Erro: ID inválido";
    exit;
}


/* =========================
   Se não enviou tipo, mantém o atual
========================= */

if (!$tipo) {

    $stmt = $pdo->prepare("
        SELECT type
        FROM transactions
        WHERE id = ? AND company_id = ?
    ");

    $stmt->execute([$id, $company_id]);

    $tipo = $stmt->fetchColumn();

    if (!$tipo) {
        echo "Erro: transação não encontrada";
        exit;
    }
}


/* =========================
   Se não enviou category_id, mantém o atual
========================= */

if (!$category_id) {

    $stmt = $pdo->prepare("
        SELECT category_id
        FROM transactions
        WHERE id = ? AND company_id = ?
    ");

    $stmt->execute([$id, $company_id]);

    $category_id = $stmt->fetchColumn();
}


/* =========================
   Cliente só existe se for receber
========================= */

$cliente = ($tipo === 'receber') ? $cliente : null;


/* =========================
   UPDATE
========================= */

$stmt = $pdo->prepare("
    UPDATE transactions
    SET
        type = ?,
        client = ?,
        description = ?,
        amount = ?,
        category_id = ?,
        date = ?
    WHERE id = ?
    AND company_id = ?
");

$stmt->execute([
    $tipo,
    $client,
    $descricao,
    $valor,
    $category_id,
    $data,
    $id,
    $company_id
]);


echo "OK";
exit;