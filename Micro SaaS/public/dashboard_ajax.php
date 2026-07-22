<?php
session_start();
require_once '../config/database.php';
require_once 'sessao.php';

$company_id = $_SESSION['company_id'];

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

if ($mes < 1) { $mes = 12; $ano--; }
if ($mes > 12) { $mes = 1; $ano++; }

$data_inicio = sprintf('%04d-%02d-01', $ano, $mes);
$data_fim = date('Y-m-t', strtotime($data_inicio));

// Totals (all statuses for cards)
$stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN type = 'entrada' THEN amount ELSE 0 END) as total_entrada,
        SUM(CASE WHEN type = 'saida' THEN amount ELSE 0 END) as total_saida
    FROM transactions
    WHERE company_id = ?
    AND date BETWEEN ? AND ?");
$stmt->execute([$company_id, $data_inicio, $data_fim]);
$totais = $stmt->fetch();

$income  = $totais['total_entrada'] ?? 0;
$expense = $totais['total_saida'] ?? 0;

// balance only paid
$stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN type = 'entrada' THEN amount ELSE 0 END) as paid_entrada,
        SUM(CASE WHEN type = 'saida' THEN amount ELSE 0 END) as paid_saida
    FROM transactions
    WHERE company_id = ?
      AND status = 'paid'
      AND date BETWEEN ? AND ?");
$stmt->execute([$company_id, $data_inicio, $data_fim]);
$paid = $stmt->fetch();
$balance = ($paid['paid_entrada'] ?? 0) - ($paid['paid_saida'] ?? 0);

// transactions
$stmt = $pdo->prepare("SELECT 
        t.id,
        t.description,
        t.amount,
        t.date,
        t.type,
        c.name as categoria_nome,
        c.color as categoria_cor
    FROM transactions t
    LEFT JOIN categories c ON c.id = t.category_id
    WHERE t.company_id = ? AND t.date BETWEEN ? AND ?
    ORDER BY t.date DESC, t.created_at DESC
    LIMIT 50");
$stmt->execute([$company_id, $data_inicio, $data_fim]);
$movimentacoes = $stmt->fetchAll();

$meses = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
$nome_mes = $meses[(int)$mes] ?? '';

// próximas transações pendentes (data futura dentro do mês)
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
    LEFT JOIN categories c ON c.id = t.category_id
    WHERE t.company_id = ?
      AND t.status = 'pending'
      AND t.date BETWEEN ? AND ?
    ORDER BY t.date ASC, t.created_at ASC");
$stmt->execute([$company_id, $data_inicio, $data_fim]);
$pendentes = $stmt->fetchAll();

// separar por tipo
$payables = array_filter($pendentes, function($x){ return ($x['type'] ?? '') === 'saida'; });
$receivables = array_filter($pendentes, function($x){ return ($x['type'] ?? '') === 'entrada'; });

// helpers used for both dashboard and ajax
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

function buildListHtml(array $items, string $color) : string {
    $html = '<ul class="pending-list" style="list-style:none;padding:0;margin:0;">';
    foreach ($items as $mov) {
        $desc = htmlspecialchars($mov['description']);
        $date = date('d/m/Y', strtotime($mov['date']));
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

$transactionsHtml = '';
if (count($movimentacoes) === 0) {
    $transactionsHtml .= '<div class="card"><p>Nenhuma movimentação encontrada neste mês.</p></div>';
} else {
    $transactionsHtml .= '<div class="card"><h3>Últimas movimentações</h3><ul class="pending-list" style="list-style:none;padding:0;margin:0;">';
    foreach ($movimentacoes as $mov) {
        $desc = htmlspecialchars($mov['description']);
        $date = date('d/m/Y', strtotime($mov['date']));
        $amount = number_format($mov['amount'],2,',','.');
        $color = $mov['categoria_cor'] ?? '#999';
        $typeColor = $mov['type'] === 'entrada' ? 'var(--income-color)' : 'var(--expense-color)';

        $transactionsHtml .= "<li style=\"padding:12px 0;border-bottom:1px solid rgba(0,0,0,0.05);display:flex;justify-content:space-between;align-items:center;\">";
        $transactionsHtml .= "<div><div style=\"font-weight:600\">{$desc}</div><div style=\"font-size:12px;color:var(--text-muted)\">{$date}</div></div>";
        $transactionsHtml .= "<div style=\"font-weight:700;color:{$typeColor}\">R$ {$amount}</div>";
        $transactionsHtml .= "</li>";
    }
    $transactionsHtml .= '</ul></div>';
}

$pendingPayHtml = renderPendingCard('Contas a pagar', $payGroups, '#F87171', 'Você não possui contas a pagar pendentes.');
$pendingReceiveHtml = renderPendingCard('Contas a receber', $receiveGroups, 'var(--income-color)', 'Você não possui contas a receber pendentes.');
$response = [
    'monthLabel' => ucfirst($nome_mes) . ' ' . $ano,
    'income' => 'R$ ' . number_format($income,2,',','.'),
    'expense' => 'R$ ' . number_format($expense,2,',','.'),
    'balance' => 'R$ ' . number_format($balance,2,',','.'),
    'transactionsHtml' => $transactionsHtml,
    'pendingPayHtml' => $pendingPayHtml,
    'pendingReceiveHtml' => $pendingReceiveHtml,
    'mes' => $mes,
    'ano' => $ano
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);

exit;

?>
