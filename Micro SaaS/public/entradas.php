<?php
session_start();

require_once '../config/database.php';
require_once '../app/Controllers/EntradasController.php';

// Proteção básica
require_once 'sessao.php';


$controller = new EntradasController($pdo);

$acao = $_GET['acao'] ?? 'listar';

if ($acao === 'criar') {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // ❌ não passa mais company_id
        // ✅ controller pega da SESSION
        $controller->criar($_POST);

        header('Location: entradas.php');
        exit;
    }

    require_once '../app/Views/entradas/criar.php';

} else {

    // ❌ não passa mais company_id
    $entradas = $controller->listar();

    require_once '../app/Views/entradas/listar.php';
}
