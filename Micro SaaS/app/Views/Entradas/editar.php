<h2>Editar Entrada</h2>

<form method="POST">
    <input 
        type="text" 
        name="descricao" 
        value="<?= htmlspecialchars($entrada['description']) ?>"
        required
    >

    <input 
        type="number" 
        step="0.01" 
        name="valor"
        value="<?= $entrada['amount'] ?>"
        required
    >

    <input 
        type="date" 
        name="data"
        value="<?= $entrada['date'] ?>"
        required
    >

    <button>Salvar Alterações</button>
</form>

<br>
<a href="entradas.php">Voltar</a>
