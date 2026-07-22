<?php
require_once __DIR__ . '/../Models/Entrada.php';

class EntradasController {
    private $entradaModel;

    public function __construct($pdo) {
        $this->entradaModel = new Entrada($pdo);
    }

    public function listar() {
        $company_id = $_SESSION['company_id'];
        return $this->entradaModel->listar($company_id);
    }

    public function criar($dados) {
        $company_id = $_SESSION['company_id'];

        return $this->entradaModel->criar(
            $company_id,
            $dados['descricao'],
            $dados['valor'],
            $dados['data']
        );
    }

    public function edit($id)
{
    session_start();
    require '../config/database.php';

    $company_id = $_SESSION['company_id'];

    $stmt = $pdo->prepare("
        SELECT * FROM incomes 
        WHERE id = ? AND company_id = ?
    ");

    $stmt->execute([$id, $company_id]);

    $entrada = $stmt->fetch();

    require '../app/Views/Entradas/editar.php';
}

public function update($id)
{
    session_start();
    require '../config/database.php';

    $company_id = $_SESSION['company_id'];

    $descricao = $_POST['descricao'];
    $valor = $_POST['valor'];
    $data = $_POST['data'];

    $stmt = $pdo->prepare("
        UPDATE incomes
        SET description = ?, amount = ?, date = ?
        WHERE id = ? AND company_id = ?
    ");

    $stmt->execute([
        $descricao,
        $valor,
        $data,
        $id,
        $company_id
    ]);

    header("Location: entradas.php");
    exit;
}

public function delete($id)
{
    session_start();
    require '../config/database.php';

    $company_id = $_SESSION['company_id'];

    $stmt = $pdo->prepare("
        DELETE FROM incomes
        WHERE id = ? AND company_id = ?
    ");

    $stmt->execute([$id, $company_id]);

    header("Location: entradas.php");
    exit;
}

}
