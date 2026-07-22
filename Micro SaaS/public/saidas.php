<?php
session_start();

require_once '../config/database.php';
require_once '../app/Controllers/SaidasController.php';
require_once 'sessao.php';

$controller = new SaidasController($pdo);

$acao = $_GET['acao'] ?? 'listar';

if ($acao === 'criar') {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->criar($_POST);
        header('Location: saidas.php');
        exit;
    }

    require_once '../app/Views/saidas/criar.php';
} else {

    $saidas = $controller->listar();
    require_once '../app/Views/saidas/listar.php';
}
