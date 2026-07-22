<h2>Editar a Receber</h2>

<form method="POST">

    <input 
        type="text"
        name="cliente"
        value="<?= htmlspecialchars($receber['client']) ?>"
    >

    <input 
        type="text"
        name="descricao"
        value="<?= htmlspecialchars($receber['description']) ?>"
        required
    >

    <input 
        type="number"
        step="0.01"
        name="valor"
        value="<?= $receber['amount'] ?>"
        required
    >

    <input 
        type="date"
        name="data"
        value="<?= $receber['date'] ?>"
        required
    >

    <button>Salvar Alterações</button>

</form>

<br>
<a href="receber.php">Voltar</a>
