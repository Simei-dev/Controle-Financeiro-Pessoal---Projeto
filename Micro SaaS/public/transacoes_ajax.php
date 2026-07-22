<?php
session_start();
require_once '../config/database.php';
require_once 'sessao.php';

$company_id = $_SESSION['company_id'];

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');
$filtro_categoria = $_GET['categoria'] ?? 'todos';
$filtro_busca = $_GET['busca'] ?? '';

if ($mes < 1) { $mes = 12; $ano--; }
if ($mes > 12) { $mes = 1; $ano++; }

$data_inicio = sprintf('%04d-%02d-01', $ano, $mes);
$data_fim = date('Y-m-t', strtotime($data_inicio));

// Totals (not strictly necessary here but kept for parity)
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

$stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN type = 'entrada' THEN amount ELSE 0 END) as total_entrada,
        SUM(CASE WHEN type = 'saida' THEN amount ELSE 0 END) as total_saida
    FROM transactions
    WHERE $where_totais");
$stmt->execute($params_totais);
$totais = $stmt->fetch();

$income  = $totais['total_entrada'] ?? 0;
$expense = $totais['total_saida'] ?? 0;

// compute the dashboard-style current balance (paid only)
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

// Build where for listing
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

$stmt = $pdo->prepare("SELECT 
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
    LEFT JOIN categories c ON c.id = t.category_id
    WHERE $where
    ORDER BY t.date DESC, t.created_at DESC
    LIMIT 50");

$stmt->execute($params);
$movimentacoes = $stmt->fetchAll();

$tableHtml = '';
$tableHtml .= '<h2 class="section-title">Todas as Transações</h2>';

if (count($movimentacoes) === 0) {
    $tableHtml .= '<div class="empty-state"><div class="empty-icon">📄</div><h3>Nenhuma transação ainda</h3><p>Comece adicionando sua primeira movimentação</p></div>';
} else {
    $tableHtml .= '<div class="transaction-list fade-in">';
    $currentDay = null;
    foreach ($movimentacoes as $mov) {
        $timestamp = strtotime($mov['date']);
        $day = date('d', $timestamp);
        if ($day !== $currentDay) {
            if ($currentDay !== null) {
                $tableHtml .= '</div></div>'; // close previous day-transactions and transaction-day
            }
            $currentDay = $day;
            $tableHtml .= '<div class="transaction-day">';
            $tableHtml .= '<div class="day-number">' . $currentDay . '</div>';
            $tableHtml .= '<div class="day-transactions">';
        }

        $tipo = strtolower($mov['type']);
        $isIncome = ($tipo === 'entrada');
        $status = trim(strtolower($mov['status'] ?? ''));
        $classeExtra = '';
        $textoStatus = '';
        if ($status === 'pending') { $classeExtra = 'status-pendente'; $textoStatus = 'Pendente'; }
        elseif ($status === 'overdue') { $classeExtra = 'status-atrasado'; $textoStatus = 'Atrasado'; }

        $categoria_cor = htmlspecialchars($mov['categoria_cor'] ?? '#6b7280');
        $categoria_nome = htmlspecialchars($mov['categoria_nome'] ?? 'Sem categoria');
        $descricao = htmlspecialchars($mov['description'], ENT_QUOTES);
        $meses_curto = [1=>'Jan',2=>'Fev',3=>'Mar',4=>'Abr',5=>'Mai',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Set',10=>'Out',11=>'Nov',12=>'Dez'];
        $mes_nome = $meses_curto[(int)date('n', $timestamp)];
        $ano_mov = date('Y', $timestamp);
        $amount = number_format($mov['amount'],2,',','.');
        $sign = $mov['type'] === 'entrada' ? '+' : ($mov['type'] === 'saida' ? '-' : '');
        $amountClass = $mov['type'] === 'entrada' ? 'positive' : ($mov['type'] === 'saida' ? 'negative' : 'receber');

        $tableHtml .= '<div class="transaction-card ' . $classeExtra . '" onclick="showTransactionModal({'
            . 'id: ' . (int)$mov['id'] . ', '
            . 'type: \"' . $mov['type'] . '\", '
            . 'description: \"' . $descricao . '\", '
            . 'amount: \"' . $mov['amount'] . '\", '
            . 'date: \"' . $mov['date'] . '\", '
            . 'status: \"' . $status . '\", '
            . 'category: \"' . htmlspecialchars($mov['categoria_nome'] ?? '', ENT_QUOTES) . '\"'
            . '})">';
        $tableHtml .= '<div class="transaction-left">';
        $tableHtml .= '<div class="transaction-top">';
        $tableHtml .= '<span class="badge" style="background: ' . $categoria_cor . '20; color: ' . $categoria_cor . '; border: 1px solid ' . $categoria_cor . '40;">' . $categoria_nome . '</span>';
        if ($textoStatus) { $tableHtml .= '<span class="status-badge ' . $classeExtra . '">' . $textoStatus . '</span>'; }
        $tableHtml .= '</div>';
        $tableHtml .= '<div class="description">' . $descricao . '</div>';
        $tableHtml .= '</div>';
        $tableHtml .= '<div class="transaction-right">';
        $tableHtml .= '<div class="amount ' . $amountClass . '">' . $sign . ' R$ ' . $amount . '</div>';
        $tableHtml .= '<div class="actions">';
        // status toggle button
        $tableHtml .= '<button class="btn-icon status" onclick="toggleStatus(' . (int)$mov['id'] . ',\'' . $status . '\',this)">'
            . '<img src="../imagem/' . ($status === 'paid' ? 'joiaverde.svg' : 'joia.svg') . '" class="icon-svg" />'
            . '</button>';
        $tableHtml .= '<button class="btn-icon edit" onclick="openEditTransaction({id: ' . (int)$mov['id'] . ', type: \"' . $mov['type'] . '\", descricao: \"' . $descricao . '\", valor: \"' . $mov['amount'] . '\", data: \"' . $mov['date'] . '\", category_id: \"' . htmlspecialchars($mov['category_id'] ?? '', ENT_QUOTES) . '\" })"><img src="../imagem/lapis.svg" alt="Editar" class="icon-svg"></button>';
        $tableHtml .= '<button class="btn-icon delete" onclick="openDeleteModal(' . (int)$mov['id'] . ')"><img src="../imagem/lixeira.svg" alt="Excluir" class="icon-svg1"></button>';
        $tableHtml .= '</div></div></div>';
    }
    if ($currentDay !== null) {
        $tableHtml .= '</div></div>';
    }
    $tableHtml .= '</div>'; // close transaction-list
    // append summary under list
    $tableHtml .= '<div class="transaction-summary" style="margin-top:20px;font-weight:bold;">'
        . 'Saldo atual: R$ ' . number_format($current_balance,2,',','.')
        . '</div>';
}


$meses = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
$nome_mes = $meses[(int)$mes] ?? '';

$response = [
    'monthLabel' => ucfirst($nome_mes) . ' ' . $ano,
    'tableHtml' => $tableHtml,
    'mes' => $mes,
    'ano' => $ano,
    'income' => 'R$ ' . number_format($income,2,',','.'),
    'expense' => 'R$ ' . number_format($expense,2,',','.'),
    'currentBalance' => 'R$ ' . number_format($current_balance,2,',','.')
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;

?>
