<h2>A Receber</h2>

<a href="receber.php?acao=criar">+ Novo a Receber</a>
<br><br>

<table border="1" cellpadding="8">
    <tr>
        <th>Cliente</th>
        <th>Descrição</th>
        <th>Valor</th>
        <th>Vencimento</th>
        <th>Status</th>
        <th>Ação</th>
    </tr>

    <?php foreach ($receber as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['client'] ?? '-') ?></td>
            <td><?= htmlspecialchars($item['description'] ?? '-') ?></td>
            <td>R$ <?= number_format($item['amount'], 2, ',', '.') ?></td>
            <td><?= date('d/m/Y', strtotime($item['due_date'])) ?></td>
            <td><?= $item['status'] === 'paid' ? 'Pago' : 'Pendente' ?></td>
            <td>
                <?php if ($item['status'] === 'pending'): ?>
                    <a href="receber.php?acao=pagar&id=<?= $item['id'] ?>">
                        Marcar como pago
                    </a>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<br><br>
<a href="dashboard.php">Início</a>
