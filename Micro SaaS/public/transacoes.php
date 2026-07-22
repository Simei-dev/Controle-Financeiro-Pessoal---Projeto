<?php
session_start();
require_once '../config/database.php';
require_once 'sessao.php';

$company_id = $_SESSION['company_id'];
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT name 
    FROM users 
    WHERE id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$nome_usuario = $user ? $user['name'] : 'Usuário';


// ===============================
// CONTROLE DE MÊS
// ===============================

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

if ($mes < 1) {
    $mes = 12;
    $ano--;
}
if ($mes > 12) {
    $mes = 1;
    $ano++;
}

$data_inicio = "$ano-$mes-01";
$data_fim = date("Y-m-t", strtotime($data_inicio));

// ===============================
// FILTROS
// ===============================

$filtro_categoria = $_GET['categoria'] ?? 'todos';
$filtro_busca = $_GET['busca'] ?? '';


// ===============================
// TOTAIS FILTRADOS POR MÊS
// ===============================

$where_totais = "company_id = ? AND date BETWEEN ? AND ?";
$params_totais = [$company_id, $data_inicio, $data_fim];

if ($filtro_categoria != 'todos') {

    $where_totais .= " AND category_id = ?";
    $params_totais[] = $filtro_categoria;
}

if (!empty($filtro_busca)) {

    $where_totais .= " AND description LIKE ?";
    $params_totais[] = "%$filtro_busca%";
}


$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN type = 'entrada' THEN amount ELSE 0 END) as total_entrada,
        SUM(CASE WHEN type = 'saida' THEN amount ELSE 0 END) as total_saida
    FROM transactions
    WHERE $where_totais
");

$stmt->execute($params_totais);

$totais = $stmt->fetch();

$income  = $totais['total_entrada'] ?? 0;
$expense = $totais['total_saida'] ?? 0;

// calculate 'saldo atual' using only paid transactions (same as dashboard)
$stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN type = 'entrada' THEN amount ELSE 0 END) as paid_entrada,
        SUM(CASE WHEN type = 'saida' THEN amount ELSE 0 END) as paid_saida
    FROM transactions
    WHERE company_id = ?
      AND status = 'paid'
      AND date BETWEEN ? AND ?");
$stmt->execute([$company_id, $data_inicio, $data_fim]);
$paid_totals = $stmt->fetch();
$current_balance = ($paid_totals['paid_entrada'] ?? 0) - ($paid_totals['paid_saida'] ?? 0);

$balance = $income - $expense; // kept for backward compatibility if used elsewhere


// ===============================
// HISTÓRICO FILTRADO
// ===============================

$where = "t.company_id = ? AND t.date BETWEEN ? AND ?";
$params = [$company_id, $data_inicio, $data_fim];

if ($filtro_categoria != 'todos') {
    $where .= " AND category_id = ?";
    $params[] = (int)$filtro_categoria;
}

if (!empty($filtro_busca)) {

    $where .= " AND (
        t.description LIKE ?
        OR t.client LIKE ?
        OR CAST(t.amount AS CHAR) LIKE ?
        OR c.name LIKE ?
    )";

    $busca = "%$filtro_busca%";

    $params[] = $busca;
    $params[] = $busca;
    $params[] = $busca;
    $params[] = $busca;
}


$stmt = $pdo->prepare("
    SELECT 
        t.id,
        t.description,
        t.amount,
        t.date,
        t.type,
        t.status,
        t.created_at,

        c.id as category_id,
        c.name as categoria_nome,
        c.color as categoria_cor

    FROM transactions t

    LEFT JOIN categories c
        ON c.id = t.category_id

    WHERE $where

    ORDER BY t.date DESC, t.created_at DESC
    LIMIT 50
");

$stmt->execute($params);

$movimentacoes = $stmt->fetchAll();

// ===============================
// CONTROLE BOTÕES MÊS
// ===============================

$mes_anterior = $mes - 1;
$ano_anterior = $ano;

if ($mes_anterior < 1) {
    $mes_anterior = 12;
    $ano_anterior--;
}

$mes_proximo = $mes + 1;
$ano_proximo = $ano;

if ($mes_proximo > 12) {
    $mes_proximo = 1;
    $ano_proximo++;
}

$meses = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Março',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro'
];

$nome_mes = $meses[(int)$mes];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <link rel="stylesheet" href="./css/theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel</title>

    <style>
        html,
        body {
            height: 100%;
        }

        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            /* ocupa no mínimo a tela inteira */
            background: var(--bg-gradient);
            background-attachment: fixed;
            /* deixa o gradiente contínuo */
            margin: 0;
            padding: 30px;
        }

        /* 🔥 MAIS LARGO */
        .container {
            max-width: 1200px;
            /* antes 1000px */
            margin: auto;
        }

        /* ========================= */
        /* SELETOR DE MÊS */
        /* ========================= */

        .month-selector {
            padding: 18px 25px;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            color: var(--text-color);
            background: var(--card-bg);
            box-shadow: var(--shadow-soft);
        }

        .month-selector a {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 14px 22px;
            text-decoration: none;
            font-size: 30px;
            font-weight: bold;
            color: #334155;
            position: relative;
            transition: 0.2s ease;
        }

        .month-selector a:hover {
            transform: scale(1.08);
        }

        .month-selector a::after {
            content: "";
            position: absolute;
            inset: -12px;
        }

        .month-selector span {
            font-size: 22px;
            font-weight: 600;
        }

        /* ========================= */
        /* TABELA */
        /* ========================= */
        .section-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-color);
        }

        /* LISTA */

        .transaction-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .transaction-summary {
            background: var(--card-bg);
            padding: 16px;
            border-radius: 12px;
            box-shadow: var(--shadow-soft);
            text-align: right;
            display: table;            /* shrink to fit content */
            margin-left: auto;        /* align right within container */
            max-width: 320px; /* keep it compact */
            color: var(--text-color); /* adapts for dark/light themes */
        }

        .transaction-card {
            background: var(--unilistbg);
            box-shadow: var(--shadow-soft);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: 0.2s;
        }

        .transaction-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        /* LEFT */

        .transaction-left {
            flex: 1;
        }

        .transaction-top {
            display: flex;
            gap: 10px;
            margin-bottom: 6px;
            align-items: center;
        }

        .description {
            font-weight: 500;
            color: var(--text-color);
            font-size: 14px;
        }

        /* DATE */

        .date {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* agrupamento por dia */
        .transaction-day {
            display: flex;
            gap: 20px;
            align-items: center; /* centra verticalmente o número */
            background: var(--card-bg);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px; /* espaçamento entre dias */
        }
        .day-number {
            font-size: 32px;
            font-weight: bold;
            width: 60px;
            text-align: center;
            color: var(--text-secondary);
            flex-shrink: 0;
        }
        .day-transactions {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }


        /* BADGE */

        .badge {
            font-size: 13px;
            padding: 3px 10px;
            border-radius: 999px;
            font-weight: 500;
        }

        /* RIGHT */

        .transaction-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        /* VALOR */

        .amount {
            font-weight: bold;
            font-size: 18px;
        }

        .amount.positive {
            color: #16a34a;
        }

        .amount.negative {
            color: #dc2626;
        }

        .transaction-category.income {
            background: var(--income-bg-soft);
            color: var(--income-text);
        }

        .transaction-category.expense {
            background: var(--expense-bg-soft);
            color: var(--expense-text);
        }

        /* ACTIONS */

        .actions {
            display: flex;
            gap: 6px;
        }

        .btn-icon {

            border: none;
            background: none;
            cursor: pointer;
            font-size: 16px;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            transition: 0.2s;
        }

        .btn-icon.edit:hover {
            background: var(--hovericonb)
        }

        .btn-icon.delete:hover {
            background: var(--hovericonr)
        }

        /* EMPTY STATE */

        .empty-state {
            text-align: center;
            padding: 40px;
            background: var(--card-bg);
            border-radius: 12px;

        }

        .empty-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        /* ========================= */
        /* HEADER SUPERIOR */
        /* ========================= */

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .top-left h1 {
            color: var(--text-color);
            margin: 0;
            font-size: 28px;
        }

        .top-left p {
            margin: 6px 0 0 0;
            color: var(--text-muted);
            font-size: 15px;
        }

        .top-right {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn {
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            font-size: 14px;
            transition: 0.2s ease;
        }

        /* BOTÃO EXPORTAR — mesmo tamanho, branco com contorno */

        .btn-blue {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .btn-blue:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.3);
        }

        /* ========================= */
        /* ANIMAÇÃO DE ENTRADA */
        /* ========================= */

        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeUp 0.2s ease forwards;
        }

        @keyframes fadeUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* CONTAINER PRINCIPAL */

        .table-container {
            background: var(--card-bg);
            box-shadow: var(--shadow-soft);
            padding: 24px;
            border-radius: 16px;
            border: 1px solid var(--input-border);
        }

        .nt-overlay {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999999999;
        }

        .nt-overlay.active {
            display: flex;
        }

        .filtros {
            margin-bottom: 20px;
        }

        .input-busca {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--input-border);
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .categorias {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .categorias a {
            padding: 6px 14px;
            border: 1px solid var(--input-border);
            border-radius: 6px;
            text-decoration: none;
            color: var(--text-color);
            background: var(--card-bg);
        }

        .categorias a.ativo {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        /* CONTAINER FILTROS */

        .filtros-container {
            background: var(--card-bg);
            box-shadow: var(--shadow-soft);
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        /* LINHA SUPERIOR */

        .filtros-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* BUSCA */

        .filtro-busca {
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid var(--input-border);
            outline: none;
            color: var(--text-color);
            width: 240px;
            transition: 0.2s;
            background: var(--card-bg);
        }

        .filtro-busca:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        /* BOTÃO BUSCAR */

        .btn-buscar {
            background: linear-gradient(135deg, #2563eb, #0d9488);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-buscar:hover {
            opacity: 0.9;
        }

        /* CATEGORIAS */

        .filtros-categorias {
            margin-top: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* BOTÃO CATEGORIA */

        .filtro-btn {
            padding: 8px 14px;
            border-radius: 10px;
            border: 1px solid var(--border-card);
            background: var(--card-bg);
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            transition: 0.2s;
        }

        .filtro-btn:hover {
            border-color: #2563eb;
            color: #2563eb;
        }

        /* ATIVO */

        .filtro-btn.ativo {
            background: linear-gradient(135deg, #2563eb, #0d9488);
            color: white;
            border: none;
        }

        .amount.positive {
            color: #16a34a;
            font-weight: 700;
        }

        .amount.negative {
            color: #dc2626;
            font-weight: 700;
        }

        .amount.receber {
            color: #f59e0b;
            font-weight: 700;
        }

        .status-badge {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
            margin-left: 8px;
        }

        /* Pendente */
        .receber-pendente .status-badge {
            background: #fef3c7;
            color: #b45309;
        }

        /* Atrasado */
        .receber-atrasado .status-badge {
            background: #fee2e2;
            color: #b91c1c;
        }

        /* Pago */
        .receber-pago .status-badge {
            background: #dcfce7;
            color: #166534;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-svg {
            width: 24px;
            height: 24px;
            display: block;
            opacity: var(--opa);
            filter: var(--filtericon);
        }

        .icon-svg1 {
            width: 21px;
            height: 21px;
            display: block;
            opacity: var(--opa);
            filter: var(--filtericon);
        }

        /* status icons smaller and paid image slightly more opaque */
        /* ensure green icon keeps color even in dark mode */
        :root {
            --hoverjoia: rgba(18, 201, 85, 0.06);
        }
        .btn-icon.status img {
            width: 20px;
            height: 20px;
        }
        .btn-icon.status img[src*="joiaverde.svg"] {
            opacity: 0.8;
            filter: none; /* keep green color, especially in dark mode */
        }
        .btn-icon.status img[src*="joia.svg"] {
            /* flip and nudge to stay centered */
            transform: rotate(180deg) scaleX(-1) translate(1px, -1px);
        }

        .btn-icon:hover .icon-svg .icon-svg1 {
            transform: scale(1.1);
            transition: 0.2s;
        }
        /* status button hover matches edit button style */
        .btn-icon.status:hover {
            /* greenish hover similar to edit/delete buttons */
            background: rgba(5, 201, 77, 0.1);
        }
    </style>

</head>

<body>
    <?php include 'header.php'; ?>
    <div class="container fade-in">
        <br><br><br><br>
        <div class="top-bar fade-in">

            <div class="top-left fade-in">
                <h1>Transações</h1>
                <p>
                    Gerencie suas receitas e despesas
                </p>
            </div>

            <div class="top-right fade-in ">
                <?php include 'newtransaction.php'; ?>
            </div>

        </div>
        <br>
        <!-- SELETOR DE MÊS -->
        <div class="month-selector fade-in">
            <a href="?mes=<?= $mes_anterior ?>&ano=<?= $ano_anterior ?>" data-mes="<?= $mes_anterior ?>" data-ano="<?= $ano_anterior ?>">‹</a>
            <span id="monthLabel">
                <?= ucfirst($nome_mes) ?> <?= $ano ?>
            </span>
            <a href="?mes=<?= $mes_proximo ?>&ano=<?= $ano_proximo ?>" data-mes="<?= $mes_proximo ?>" data-ano="<?= $ano_proximo ?>">›</a>
        </div>

        <div class="filtros-container fade-in">

            <form method="GET" class="filtros-top fade-in">

                <input type="hidden" name="mes" value="<?= $mes ?>">
                <input type="hidden" name="ano" value="<?= $ano ?>">

                <input
                    type="text"
                    name="busca"
                    placeholder="Buscar transação..."
                    value="<?= htmlspecialchars($filtro_busca) ?>"
                    class="filtro-busca">

                <button type="submit" class="btn-buscar">
                    Buscar
                </button>

            </form>

            <div class="filtros-categorias fade-in">

                <a href="?categoria=todos&mes=<?= $mes ?>&ano=<?= $ano ?>"
                    class="filtro-btn <?= $filtro_categoria == 'todos' ? 'ativo' : '' ?>">
                    Todos
                </a>

                <?php foreach ($categorias as $cat): ?>

                    <a href="?categoria=<?= $cat['id'] ?>&mes=<?= $mes ?>&ano=<?= $ano ?>"
                        class="filtro-btn <?= $filtro_categoria == $cat['id'] ? 'ativo' : '' ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>

                <?php endforeach; ?>

            </div>
            <br>
            <div id="transactionsTableContainer" class="table-container fade-in">

                <h2 class="section-title">Todas as Transações</h2>

                <?php if (count($movimentacoes) === 0): ?>

                    <div class="empty-state">
                        <div class="empty-icon">📄</div>
                        <h3>Nenhuma transação ainda</h3>
                        <p>Comece adicionando sua primeira movimentação</p>
                    </div>

                <?php else: ?>

                    <div class="transaction-list fade-in">

                        <?php
                        $currentDay = null;
                        foreach ($movimentacoes as $mov):
                            $timestamp = strtotime($mov['date']);
                            $day = date('d', $timestamp);

                            if ($day !== $currentDay) {
                                if ($currentDay !== null) {
                                    // close previous day wrapper
                                    echo '</div></div>'; // .day-transactions and .transaction-day
                                }
                                // start new day group
                                $currentDay = $day;
                                echo '<div class="transaction-day">';
                                echo '<div class="day-number">' . $currentDay . '</div>';
                                echo '<div class="day-transactions">';
                            }

                            $tipo = strtolower($mov['type']);
                            $isIncome = ($tipo === 'entrada');
                            $status = trim(strtolower($mov['status'] ?? ''));

                            $classeExtra = '';
                            $textoStatus = '';

                            if ($status === 'pending') {
                                $classeExtra = 'status-pendente';
                                $textoStatus = 'Pendente';
                            } elseif ($status === 'overdue') {
                                $classeExtra = 'status-atrasado';
                                $textoStatus = 'Atrasado';
                            } elseif ($status === 'paid') {
                                // pago não mostra badge
                            }
                            ?>
                            <div class="transaction-card <?= $classeExtra ?>">

                                <div class="transaction-left">

                                    <div class="transaction-top">
                                        <span class="badge"
                                            style="background: <?= htmlspecialchars($mov['categoria_cor'] ?? '#6b7280') ?>20;
             color: <?= htmlspecialchars($mov['categoria_cor'] ?? '#6b7280') ?>;
             border: 1px solid <?= htmlspecialchars($mov['categoria_cor'] ?? '#6b7280') ?>40;">

                                            <?= htmlspecialchars($mov['categoria_nome'] ?? 'Sem categoria') ?>

                                        </span>

                                        <?php if ($textoStatus): ?>
                                            <span class="status-badge <?= $classeExtra ?>">
                                                <?= $textoStatus ?>
                                            </span>
                                        <?php endif; ?>

                                    </div>

                                    <div class="description">
                                        <?= htmlspecialchars($mov['description']) ?>
                                    </div>

                                </div>

                                <div class="transaction-right">

                                    <div class="amount 
    <?= $mov['type'] === 'entrada' ? 'positive' : ($mov['type'] === 'saida' ? 'negative' : 'receber') ?>">

                                        <?= $mov['type'] === 'entrada' ? '+' : ($mov['type'] === 'saida' ? '-' : '') ?>

                                        R$ <?= number_format($mov['amount'], 2, ',', '.') ?>
                                    </div>

                                    <div class="actions">

                                        <button class="btn-icon status"
                                            onclick="toggleStatus(<?= $mov['id'] ?>,'<?= $status ?>',this)">

                                            <img
                                                src="../imagem/<?=
                                                                $status === 'paid'
                                                                    ? 'joiaverde.svg'
                                                                    : 'joia.svg'
                                                                ?>"
                                                class="icon-svg">
                                        </button>

                                        <button class="btn-icon edit"
                                            onclick='openEditTransaction({
        id: <?= $mov["id"] ?>,
        type: "<?= $mov["type"] ?>",
        descricao: "<?= htmlspecialchars($mov["description"], ENT_QUOTES) ?>",
        valor: "<?= $mov["amount"] ?>",
        data: "<?= $mov["date"] ?>",
        category_id: "<?= htmlspecialchars($mov["category_id"] ?? "", ENT_QUOTES) ?>",
    })'>
                                            <img src="../imagem/lapis.svg" alt="Editar" class="icon-svg">
                                        </button>

                                        <button class="btn-icon delete"
                                            onclick="openDeleteModal(<?= $mov['id'] ?>)">
                                            <img src="../imagem/lixeira.svg" alt="Excluir" class="icon-svg1">
                                        </button>
                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>
                        <?php
                            // fecha último grupo de dia se houver
                            if (isset($currentDay)) {
                                echo '</div></div>'; // fecha .day-transactions e .transaction-day
                            }
                        ?>
                    </div>

                    <?php if(count($movimentacoes) > 0): ?>
                    <div class="transaction-summary" style="margin-top:20px;font-weight:bold;">
                        Saldo atual: R$ <?= number_format($current_balance,2,',','.') ?>
                    </div>
                    <?php endif; ?>

                <?php endif; ?>

            </div>

        </div>
        <br><br><br><br>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const elements = document.querySelectorAll(".fade-in");

                elements.forEach((el, index) => {
                    el.style.animationDelay = (index * 0.05) + "s";
                });
            });
        </script>
        <div class="nt-overlay" id="ntOverlay">

            <div class="nt-modal">

                <div class="nt-header">
                    <h3>Nova transação</h3>
                    <button class="nt-close" onclick="fecharModal()">✕</button>
                </div>

                conteúdo...

            </div>

        </div>
        <script>
            function abrirModal() {

                document.getElementById("ntOverlay")
                    .classList.add("active");

            }

            function fecharModal() {

                document.getElementById("ntOverlay")
                    .classList.remove("active");

            }
        </script>
        <script>
            // Intercepta cliques no seletor de mês e atualiza a lista sem recarregar
            document.addEventListener('click', function(e) {
                const a = e.target.closest('.month-selector a');
                if (!a) return;
                e.preventDefault();
                const mes = a.dataset.mes;
                const ano = a.dataset.ano;
                if (!mes || !ano) return;
                const form = document.querySelector('form.filtros-top');
                const busca = form ? (form.querySelector('input[name="busca"]')?.value || '') : '';
                const categoriaLink = document.querySelector('.filtros-categorias a.ativo');
                const categoria = categoriaLink ? (categoriaLink.getAttribute('href').match(/categoria=([^&]+)/)?.[1] || 'todos') : 'todos';
                fetchTransacoes(mes, ano, categoria, busca);
            });

            function fetchTransacoes(mes, ano, categoria, busca) {
                const params = new URLSearchParams({
                    mes: mes,
                    ano: ano
                });
                if (categoria) params.set('categoria', categoria);
                if (busca) params.set('busca', busca);
                const url = 'transacoes_ajax.php?' + params.toString();
                fetch(url, {
                        credentials: 'same-origin'
                    })
                    .then(r => r.json())
                    .then(data => {
                        // atualiza rótulo
                        const monthLabel = document.getElementById('monthLabel');
                        if (monthLabel) monthLabel.textContent = data.monthLabel;
                        // atualiza container da tabela
                        const container = document.getElementById('transactionsTableContainer');
                        if (container) container.innerHTML = data.tableHtml;
                        // atualiza inputs hidden do form
                        const form = document.querySelector('form.filtros-top');
                        if (form) {
                            const inMes = form.querySelector('input[name="mes"]');
                            const inAno = form.querySelector('input[name="ano"]');
                            if (inMes) inMes.value = data.mes;
                            if (inAno) inAno.value = data.ano;
                        }
                        // atualiza prev/next nos botões do seletor
                        const currentMes = parseInt(data.mes, 10);
                        const currentAno = parseInt(data.ano, 10);
                        let prevMes = currentMes - 1;
                        let prevAno = currentAno;
                        if (prevMes < 1) {
                            prevMes = 12;
                            prevAno--;
                        }
                        let nextMes = currentMes + 1;
                        let nextAno = currentAno;
                        if (nextMes > 12) {
                            nextMes = 1;
                            nextAno++;
                        }
                        const anchors = document.querySelectorAll('.month-selector a');
                        if (anchors[0]) {
                            anchors[0].dataset.mes = prevMes;
                            anchors[0].dataset.ano = prevAno;
                            anchors[0].href = `?mes=${prevMes}&ano=${prevAno}`;
                        }
                        if (anchors[1]) {
                            anchors[1].dataset.mes = nextMes;
                            anchors[1].dataset.ano = nextAno;
                            anchors[1].href = `?mes=${nextMes}&ano=${nextAno}`;
                        }
                        // atualiza hrefs das categorias para manter mes/ano
                        document.querySelectorAll('.filtros-categorias a').forEach(a => {
                            try {
                                const href = new URL(a.href, location.origin);
                                href.searchParams.set('mes', data.mes);
                                href.searchParams.set('ano', data.ano);
                                a.href = href.pathname + '?' + href.searchParams.toString();
                            } catch (e) {
                                /* ignore */
                            }
                        });
                        history.replaceState(null, '', `?mes=${data.mes}&ano=${data.ano}`);
                    })
                    .catch(err => console.error('Erro ao carregar transações:', err));
            }
            function toggleStatus(id, currentStatus, button){
    // apenas dois estados: paid <-> pending
    let newStatus = currentStatus === 'paid' ? 'pending' : 'paid';

    fetch('update_status.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({
            id:id,
            status:newStatus
        })
    })
    .then(r=>r.json())
    .then(data=>{

        if(data.success){

            const img = button.querySelector("img");

            if(newStatus === 'paid'){
                img.src = "../imagem/joiaverde.svg";
            } else {
                img.src = "../imagem/joia.svg";
            }

            button.setAttribute("onclick",
                `toggleStatus(${id},'${newStatus}',this)`
            );

            // after toggling status, refresh the list and summary using current filters
            const form = document.querySelector('form.filtros-top');
            const mes = form ? form.querySelector('input[name="mes"]').value : null;
            const ano = form ? form.querySelector('input[name="ano"]').value : null;
            const busca = form ? (form.querySelector('input[name="busca"]')?.value || '') : '';
            const categoriaLink = document.querySelector('.filtros-categorias a.ativo');
            const categoria = categoriaLink ? (categoriaLink.getAttribute('href').match(/categoria=([^&]+)/)?.[1] || 'todos') : 'todos';
            if (mes && ano) {
                fetchTransacoes(mes, ano, categoria, busca);
            }
        }

    });

}
        </script>
        
</body>

</html>