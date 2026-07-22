<h2>Editar Gasto</h2>

<form method="POST">

    <input 
        type="text"
        name="descricao"
        value="<?= htmlspecialchars($saida['description']) ?>"
        required
    >

    <input 
        type="number"
        step="0.01"
        name="valor"
        value="<?= $saida['amount'] ?>"
        required
    >

    <input 
        type="date"
        name="data"
        value="<?= $saida['date'] ?>"
        required
    >

    <button>Salvar Alterações</button>

</form>

<br>
<a href="saidas.php">Voltar</a>
