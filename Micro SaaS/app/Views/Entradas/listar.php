<h2>Entradas</h2>

<a href="entradas.php?acao=criar">+ Nova Entrada</a>
</br></br>
<table border="1" cellpadding="8">
    <tr>
        <th>Descrição</th>
        <th>Valor</th>
        <th>Data</th>
    </tr>

    <?php foreach ($entradas as $entrada): ?>
        <tr>
            <td><?= htmlspecialchars($entrada['description']) ?></td>
            <td>R$ <?= number_format($entrada['amount'], 2, ',', '.') ?></td>
            <td><?= date('d/m/Y', strtotime($entrada['date'])) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
</br></br>
<a href="dashboard.php"> Inicio</a> </br></br>