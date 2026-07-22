<?php

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verifica se existe empresa ativa
if (!isset($_SESSION['company_id'])) {
    header('Location: login.php');
    exit;
}
