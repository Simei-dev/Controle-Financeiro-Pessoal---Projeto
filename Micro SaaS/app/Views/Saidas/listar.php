<h2>Saídas</h2>

<a href="saidas.php?acao=criar">+ Novo Gasto</a>
</br></br>
<table border="1" cellpadding="8">
    <tr>
        <th>Descrição</th>
        <th>Valor</th>
        <th>Data</th>
    </tr>

    <?php foreach ($saidas as $saida): ?>
        <tr>
            <td><?= htmlspecialchars($saida['description']) ?></td>
            <td>R$ <?= number_format($saida['amount'], 2, ',', '.') ?></td>
            <td><?= date('d/m/Y', strtotime($saida['date'])) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

</br></br>
<a href="dashboard.php"> Inicio</a> </br></br>