<h2>Novo Gasto</h2>

<form method="POST">
    <input type="text" name="descricao" placeholder="Descrição" required>
    <input type="number" step="0.01" name="valor" placeholder="Valor" required>
    <input type="date" name="data" id="data" required>
    <script>
const hoje = new Date();
const offset = hoje.getTimezoneOffset();
hoje.setMinutes(hoje.getMinutes() - offset);
document.getElementById('data').value = hoje.toISOString().split('T')[0];
</script>

    <button>Salvar Gasto</button>
</form>

<a href="saidas.php">Voltar</a>

