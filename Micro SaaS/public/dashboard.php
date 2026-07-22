<?php
session_start();
require_once '../config/database.php';
require_once 'sessao.php';

$company_id = $_SESSION['company_id'];
$user_id    = $_SESSION['user_id'];

// ===============================
// BUSCAR NOME DO USUÁRIO
// ===============================
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

// Corrigir limites
if ($mes < 1) { $mes = 12; $ano--; }
if ($mes > 12) { $mes = 1; $ano++; }

$data_inicio = "$ano-$mes-01";
$data_fim = date("Y-m-t", strtotime($data_inicio));

// ===============================
// TOTAIS FILTRADOS POR MÊS
// ===============================

// valores de entrada/saída sem considerar status
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN type = 'entrada' THEN amount ELSE 0 END) as total_entrada,
        SUM(CASE WHEN type = 'saida' THEN amount ELSE 0 END) as total_saida
    FROM transactions
    WHERE company_id = ?
    AND date BETWEEN ? AND ?
");

$stmt->execute([$company_id, $data_inicio, $data_fim]);
$totais = $stmt->fetch();

$income  = $totais['total_entrada'] ?? 0;
$expense = $totais['total_saida'] ?? 0;

// saldo apenas com lançamentos pagos
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN type = 'entrada' THEN amount ELSE 0 END) as paid_entrada,
        SUM(CASE WHEN type = 'saida' THEN amount ELSE 0 END) as paid_saida
    FROM transactions
    WHERE company_id = ?
      AND status = 'paid'
      AND date BETWEEN ? AND ?
");
$stmt->execute([$company_id, $data_inicio, $data_fim]);
$paid = $stmt->fetch();
$balance = ($paid['paid_entrada'] ?? 0) - ($paid['paid_saida'] ?? 0);


// ===============================
// HISTÓRICO FILTRADO
// ===============================

$where = "t.company_id = ? AND t.date BETWEEN ? AND ?";
$params = [$company_id, $data_inicio, $data_fim];

if (!empty($filtro_busca)) {

    $where .= " AND t.description LIKE ?";
    $params[] = "%$filtro_busca%";

}

$stmt = $pdo->prepare("
    SELECT 
        t.id,
        t.description,
        t.amount,
        t.date,
        t.type,
        t.created_at,

        c.id as category_id,
        c.name as categoria_nome,
        c.color as categoria_cor

    FROM transactions t

    LEFT JOIN categories c
        ON c.id = t.category_id

    WHERE $where

    ORDER BY t.created_at DESC
    LIMIT 50
");

$stmt->execute($params);

$movimentacoes = $stmt->fetchAll();

// montar pendentes futuros
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT 
        t.id,
        t.description,
        t.amount,
        t.date,
        t.type,
        t.status,
        c.name as categoria_nome,
        c.color as categoria_cor
    FROM transactions t
    LEFT JOIN categories c
        ON c.id = t.category_id
    WHERE t.company_id = ?
      AND t.status = 'pending'
      AND t.date BETWEEN ? AND ?
    ORDER BY t.date ASC, t.created_at ASC");
$stmt->execute([$company_id, $data_inicio, $data_fim]);
$pendentes = $stmt->fetchAll();

$payables = array_filter($pendentes, function($x){ return ($x['type'] ?? '') === 'saida'; });
$receivables = array_filter($pendentes, function($x){ return ($x['type'] ?? '') === 'entrada'; });

// helper: categorize items by due date relative to today
function groupByDueDate(array $items, string $today) : array {
    $groups = ['overdue'=>[], 'today'=>[], 'upcoming'=>[]];
    foreach ($items as $mov) {
        $due = $mov['date'] ?? '';
        if ($due === '') continue;
        if ($due < $today) {
            $groups['overdue'][] = $mov;
        } elseif ($due === $today) {
            $groups['today'][] = $mov;
        } else {
            $groups['upcoming'][] = $mov;
        }
    }
    return $groups;
}

// helper: build HTML list for a set of transactions
function buildListHtml(array $items, string $color) : string {
    $html = '<ul class="pending-list" style="list-style:none;padding:0;margin:0;">';
    foreach ($items as $mov) {
        $desc = htmlspecialchars($mov['description']);
        $date = date('d/m/Y', strtotime($mov['date']));
        $cat = htmlspecialchars($mov['categoria_nome'] ?? 'Sem categoria');
        $amount = number_format($mov['amount'],2,',','.');
        $status = $mov['status'] ?? 'pending';
        $icon = $status === 'paid' ? 'joiaverde.svg' : 'joia.svg';
        $html .= "<li class=\"pending-item\">";
        $html .= "<div class=\"pending-desc\">";
        $html .= "<div class=\"name\">{$desc}</div>";
        $html .= "<div class=\"meta\">{$date}</div>";
        $html .= "</div>";
        $html .= "<div style=\"display:flex;align-items:center;gap:8px;\">";
        $html .= "<div class=\"amount\" style=\"color:{$color}\">R$ {$amount}</div>";
        $html .= "<button class=\"btn-icon status\" onclick=\"toggleStatus({$mov['id']},'{$status}',this)\">";
        $html .= "<img src=\"../imagem/{$icon}\" class=\"icon-svg\" />";
        $html .= "</button>";
        $html .= "</div>";
        $html .= "</li>";
    }
    $html .= '</ul>';
    return $html;
}

// helper: render the card given groups
function renderPendingCard(string $title, array $groups, string $color, string $emptyMsg) : string {
    $html = '<div class="card"><h3>' . $title . '</h3>';
    $hasSpecial = !empty($groups['overdue']) || !empty($groups['today']);
    if (!$hasSpecial) {
        if (empty($groups['upcoming'])) {
            $html .= '<p style="opacity:0.6;">' . $emptyMsg . '</p>';
        } else {
            $html .= buildListHtml($groups['upcoming'], $color);
        }
    } else {
        // show categories in order
        if (!empty($groups['overdue'])) {
            $html .= '<h4 class="alert-label">Atrasadas</h4>' . buildListHtml($groups['overdue'], $color);
        }
        if (!empty($groups['today'])) {
            $html .= '<h4 class="alert-label">Vence hoje</h4>' . buildListHtml($groups['today'], $color);
        }
        if (!empty($groups['upcoming'])) {
            $html .= '<h4 class="alert-label grey">Próximas</h4>' . buildListHtml($groups['upcoming'], $color);
        }
    }
    $html .= '</div>';
    return $html;
}

$payGroups = groupByDueDate($payables, $today);
$receiveGroups = groupByDueDate($receivables, $today);

$pendingPayHtml = renderPendingCard('Contas a pagar', $payGroups, '#F87171', 'Você não possui contas a pagar pendentes.');
$pendingReceiveHtml = renderPendingCard('Contas a receber', $receiveGroups, 'var(--income-color)', 'Você não possui contas a receber pendentes.');
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

html, body {
    height: 100%;
}

body {
    font-family: Arial, sans-serif;
    min-height: 100vh; /* ocupa no mínimo a tela inteira */
    background: var(--bg-gradient);
    color: var(--text-color);
    background-attachment: fixed; /* deixa o gradiente contínuo */
    margin: 0;
    padding: 30px;
}

/* 🔥 MAIS LARGO */
.container{
    max-width:1200px;   /* antes 1000px */
    margin:auto;
}

/* ========================= */
/* SELETOR DE MÊS */
/* ========================= */

.month-selector{
    background: var(--card-bg);
    box-shadow: var(--shadow-soft);
    padding:18px 25px;
    border-radius:16px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:40px;
}

.month-selector a{
    display:flex;
    align-items:center;
    justify-content:center;
    padding:14px 22px;
    text-decoration:none;
    font-size:30px;
    font-weight:bold;
    color: var(--text-secondary);
    position:relative;
    transition:0.2s ease;
}

.month-selector a:hover{
    transform:scale(1.08);
}

.month-selector a::after{
    content:"";
    position:absolute;
    inset:-12px;
}

.month-selector span{
    font-size:22px;
    font-weight:600;
}
/* ========================= */
/* CARDS */
/* ========================= */

/* top row with three summary cards */
.top-cards{
    display:grid;
    grid-template-columns: repeat(3,1fr);
    gap:30px;
    margin-bottom:40px;
}

.widget-grid{
    display:grid;
    grid-template-columns: repeat(2,1fr);
    gap:30px;
    margin:40px 0;
}

/* responsive adjustments: stack columns on small viewports */
@media (max-width: 800px) {
    .top-cards { grid-template-columns: 1fr !important; }
    .widget-grid { grid-template-columns: 1fr !important; }
}


/* 🔥 CARDS MAIORES */
.card{
    padding:24px 24px 24px 36px; /* extra left space inside each card */
    border-radius:18px;
    background: var(--card-bg);
    box-shadow: var(--shadow-soft);
    transition:0.25s ease;
}

/* pending list adjustments for clean look */
.pending-list li {
    padding:18px 0;
    border-bottom:1px solid #F3F4F6;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.pending-list li:last-child { border-bottom:none; }
.pending-list .pending-desc .name { font-weight:600; color:#374151; }
.pending-list .pending-desc .meta { font-size:12px; color:#9CA3AF; }
.pending-list .amount { color:#F87171; font-weight:600; }

/* categories as small tags */
.card h4.alert-label {
    font-size:13px;
    font-weight:500;
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-block;
    opacity: 1;
}
.card h4.alert-label.grey { font-size:13px; }

/* override earlier color definitions */
.card h4.alert-label { background: rgba(255,0,0,0.1); color: #c00; }
.card h4.alert-label.grey { background: rgba(0,0,0,0.05); color: #333; }

/* dark-mode category colors already handled earlier */

.card:hover{
    /* hover effect removed for cleaner look */
    transform:none;
}

/* SALDO */
.card.balance{
    background: var(--balance-gradient);
    color:white;
    box-shadow: var(--shadow-balance);
}

.card.income{
    border:2px solid var(--income-border);
    box-shadow: var(--income-glow);
}

.card.expense{
    border:2px solid var(--expense-border);
    box-shadow: var(--expense-glow);
}

.card h3{
    margin:0 0 8px 0; /* espaço extra abaixo do título */
    font-size:17px; /* maior para títulos de cards */
    opacity:0.7;
    letter-spacing:0.5px;
}
.card h4{
    margin:16px 0 6px 0;
    font-size:14px;
    opacity:0.6;
    letter-spacing:0.4px;
}
/* alert-style label for overdue/today headings */
.card h4.alert-label {
    background: rgba(255,0,0,0.1);
    color: #c00; /* strong red */
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-block;
    opacity: 1;
}

/* pending-item border adapts to dark mode */
body.dark-mode .card ul li {
    border-bottom-color: rgba(255,255,255,0.1) !important;
}
.card h4.alert-label.grey {
    background: rgba(0,0,0,0.05);
    color: #333;
}

/* dark-mode overrides for labels */
body.dark-mode .card h4.alert-label {
    background: rgba(255,0,0,0.2);
    color: #f88;
}
body.dark-mode .card h4.alert-label.grey {
    background: rgba(255,255,255,0.1);
    color: #ccc;
}

.card .value{
    font-size:34px;
    font-weight:bold;
    margin-top:14px;
}

.card .value1{
    font-size:34px;
    font-weight:bold;
    margin-top:14px;
    color: var(--income-color);
}

.card .value2{
    font-size:34px;
    font-weight:bold;
    margin-top:14px;
    color: var(--expense-color);
}

/* ========================= */
/* HEADER SUPERIOR */
/* ========================= */

.top-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:40px;
    flex-wrap:wrap;
    gap:20px;
}

.top-left h1{
    margin:0;
    font-size:28px;
}

.top-left p{
    margin:6px 0 0 0;
    color: var(--text-muted);
    font-size:15px;
}

.top-right{
    display:flex;
    gap:12px;
    align-items:center;
}

.btn{
    padding:12px 20px;
    border-radius:10px;
    text-decoration:none;
    color:white;
    font-weight:600;
    font-size:14px;
    transition:0.2s ease;
}
/* BOTÃO EXPORTAR — mesmo tamanho, branco com contorno */

.btn-white{

    background: var(--btn-white-bg);

    background: var(--btn-white-bg);

    color: var(--text-color);

    border: 1.5px solid var(--btn-white-border);

    padding: 10px 20px;

    border-radius: 10px;

    font-weight: bold;

    font-size: 14px;

    cursor: pointer;

    transition: 0.2s ease;

    display: inline-flex;
    align-items: center;
    justify-content: center;

}

.btn-white:hover{

    background: var(--btn-white-hover);

    transform: translateY(-1px);

}

.btn-blue{
    background: var(--btn-blue-gradient);
}

.btn-blue:hover{
    transform:translateY(-2px);
    box-shadow: var(--btn-blue-shadow);
}

/* status toggle button styling reused from transacoes page */
.btn-icon { background:none; border:none; cursor:pointer; padding:4px; display:flex; align-items:center; justify-content:center; }
.btn-icon.status img { width:20px; height:20px; }
.btn-icon.status img[src*="joiaverde.svg"] { opacity:0.8; filter:none; }
.btn-icon.status img {
    filter: var(--filtericon);
    opacity:0.5;
    width:16px;
    height:16px;
}

/* hide status button normally, reveal on hover of list item */
.pending-item .btn-icon.status {
    opacity:0;
    transition:opacity 0.2s ease;
}
.pending-item:hover .btn-icon.status {
    opacity:1;
}

.btn-icon.status img[src*="joiaverde.svg"] { opacity:0.8; filter:none; }

.btn-icon.status img[src*="joia.svg"] {
    transform: rotate(180deg) scaleX(-1) translate(1px,-1px);
    opacity: 0.5; /* less prominent */
}
.btn-icon.status { margin: 0 8px; }
.btn-icon.status:hover { background: rgba(5,201,77,0.1); }

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
.nt-overlay{

    position: fixed;

    inset: 0;

    display: none;

    align-items: center;
    justify-content: center;

    background: rgba(0,0,0,0.5);

    z-index: 999999999;

}

.nt-overlay.active{
    display:flex;
}
</style>

</head>
<body>
<?php include 'header.php'; ?>
<div class="container fade-in">
<br><br><br><br>
<div class="top-bar fade-in">

    <div class="top-left fade-in">
        <h1>Painel</h1>
        <p>
            Bem-vindo(a) de volta,
           <strong><?= htmlspecialchars($nome_usuario) ?></strong>
        </p>
    </div>

    <div class="top-right fade-in ">
        <a href="export.php?mes=<?= $mes ?>&ano=<?= $ano ?>" class="btn btn-white">
    Exportar
</a>
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

<!-- resumo do mês: saldo, entradas e saídas em três colunas -->
<div class="top-cards fade-in">

    <div class="card balance fade-in">
        <h3>Saldo do Mês</h3>
        <div class="value">
            R$ <?= number_format($balance, 2, ',', '.') ?>
        </div>
        <!-- lista removida do dashboard por desejo do usuário -->
    </div>

    <div class="card income fade-in">
        <h3>Entradas</h3>
        <div class="value1">
            R$ <?= number_format($income, 2, ',', '.') ?>
        </div>
    </div>

    <div class="card expense fade-in">
        <h3>Saídas</h3>
        <div class="value2">
            R$ <?= number_format($expense, 2, ',', '.') ?>
        </div>
    </div>

</div>

<!-- widgets secundários em grade de duas colunas -->
<div class="widget-grid fade-in">

    <!-- SEÇÃO DE PRÓXIMAS TRANSAÇÕES PENDENTES -->
    <div id="pending-pay-container" class="fade-in">
        <?= $pendingPayHtml ?>
    </div>

    <div id="pending-receive-container" class="fade-in">
        <?= $pendingReceiveHtml ?>
    </div>

</div>
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

function abrirModal(){

    document.getElementById("ntOverlay")
        .classList.add("active");

}

function fecharModal(){

    document.getElementById("ntOverlay")
        .classList.remove("active");

}

</script>
<script>
// Intercepta cliques no seletor de mês e atualiza via AJAX
document.addEventListener('click', function(e){
    const a = e.target.closest('.month-selector a');
    if (!a) return;
    e.preventDefault();
    const mes = a.dataset.mes;
    const ano = a.dataset.ano;
    if (!mes || !ano) return;
    fetchDashboard(mes, ano);
});

function fetchDashboard(mes, ano){
    const url = `dashboard_ajax.php?mes=${mes}&ano=${ano}`;
    fetch(url, { credentials: 'same-origin' })
        .then(res => res.json())
        .then(data => {
            // Atualiza rótulo do mês
            const monthLabel = document.getElementById('monthLabel');
            if (monthLabel) monthLabel.textContent = data.monthLabel;

            // Atualiza cards
            const bal = document.querySelector('.card.balance .value');
            const inc = document.querySelector('.card.income .value1');
            const exp = document.querySelector('.card.expense .value2');
            if (bal) bal.textContent = data.balance;
            if (inc) inc.textContent = data.income;
            if (exp) exp.textContent = data.expense;

            // atualizar seções de pendentes separadas
            const payContainer = document.getElementById('pending-pay-container');
            const receiveContainer = document.getElementById('pending-receive-container');
            if (payContainer && typeof data.pendingPayHtml !== 'undefined') {
                payContainer.innerHTML = data.pendingPayHtml;
            }
            if (receiveContainer && typeof data.pendingReceiveHtml !== 'undefined') {
                receiveContainer.innerHTML = data.pendingReceiveHtml;
            }

            // Recalcula prev/next nos botões do seletor
            const currentMes = parseInt(data.mes,10);
            const currentAno = parseInt(data.ano,10);
            let prevMes = currentMes - 1; let prevAno = currentAno;
            if (prevMes < 1) { prevMes = 12; prevAno--; }
            let nextMes = currentMes + 1; let nextAno = currentAno;
            if (nextMes > 12) { nextMes = 1; nextAno++; }
            const anchors = document.querySelectorAll('.month-selector a');
            if (anchors[0]) { anchors[0].dataset.mes = prevMes; anchors[0].dataset.ano = prevAno; anchors[0].href = `?mes=${prevMes}&ano=${prevAno}`; }
            if (anchors[1]) { anchors[1].dataset.mes = nextMes; anchors[1].dataset.ano = nextAno; anchors[1].href = `?mes=${nextMes}&ano=${nextAno}`; }

            // Atualiza URL sem recarregar
            history.replaceState(null, '', `?mes=${currentMes}&ano=${currentAno}`);
        })
        .catch(err => console.error('Erro ao atualizar dashboard:', err));
}

// function to toggle paid/pending status from dashboard lists
function toggleStatus(id, currentStatus, button) {
    const newStatus = currentStatus === 'paid' ? 'pending' : 'paid';
    fetch('update_status.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ id: id, status: newStatus })
    })
    .then(r=>r.json())
    .then(data=>{
        if (data.success) {
            // refresh dashboard to remove item
            const params = new URLSearchParams(window.location.search);
            const mes = params.get('mes') || <?= $mes ?>;
            const ano = params.get('ano') || <?= $ano ?>;
            fetchDashboard(mes, ano);
        }
    })
    .catch(err => console.error('Erro ao atualizar status:', err));
}
</script>
</body>
</html>