<?php

session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once '../config/database.php';
$company_id = $_SESSION['company_id'];

$tipo        = $_POST['tipo'] ?? null;
$descricao   = $_POST['descricao'] ?? null;
$valor       = $_POST['valor'] ?? 0;
$data        = $_POST['data'] ?? null;
$category_id = $_POST['category_id'] ?? null;

$repeat_type            = $_POST['repeat_type'] ?? null;
$installments           = $_POST['installments'] ?? null;
$installment_frequency  = $_POST['installment_frequency'] ?? 'monthly';

/* =========================
   Corrigir valor monetário
========================= */

$valor = str_replace('.', '', $valor);
$valor = str_replace(',', '.', $valor);
$valor = floatval($valor);

if (!$tipo || !$descricao || !$valor || !$data || !$category_id) {
    echo "Erro: dados inválidos";
    exit;
}

$hoje = date('Y-m-d');

/* =====================================================
   🔵 SE FOR PARCELADO
===================================================== */

if ($repeat_type === "parcelado" && $installments > 1) {

    $installments = intval($installments);
    $valor_parcela = round($valor / $installments, 2);

    $data_base = new DateTime($data);
    $hoje = date('Y-m-d');

    $stmt = $pdo->prepare("
        INSERT INTO transactions
        (company_id, type, description, amount, category_id, date, status, received_at, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    for ($i = 1; $i <= $installments; $i++) {

        $nova_data = clone $data_base;

        if ($installment_frequency === "monthly") {
            $nova_data->modify("+" . ($i - 1) . " month");
        } elseif ($installment_frequency === "weekly") {
            $nova_data->modify("+" . ($i - 1) . " week");
        } elseif ($installment_frequency === "daily") {
            $nova_data->modify("+" . ($i - 1) . " day");
        }

        $data_formatada = $nova_data->format('Y-m-d');

        // STATUS CALCULADO INDIVIDUALMENTE
        if ($data_formatada <= $hoje) {
            $status = "paid";
            $received_at = date('Y-m-d H:i:s'); // horário real do cadastro
        } else {
            $status = "pending";
            $received_at = null;
        }

        $stmt->execute([
            $company_id,
            $tipo,
            $descricao . " ({$i}/{$installments})",
            $valor_parcela,
            $category_id,
            $data_formatada,
            $status,
            $received_at
        ]);
    }

    echo "OK";
    exit;
}

/* =====================================================
   🟢 NORMAL OU FIXO
===================================================== */

if ($data <= $hoje) {
    $status = "paid";
    $received_at = date('Y-m-d H:i:s');
} else {
    $status = "pending";
    $received_at = null;
}

$stmt = $pdo->prepare("
    INSERT INTO transactions
    (company_id, type, description, amount, category_id, date, status, received_at, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmt->execute([
    $company_id,
    $tipo,
    $descricao,
    $valor,
    $category_id,
    $data,
    $status,
    $received_at
]);

echo "OK";
exit;
