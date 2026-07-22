<?php
require_once __DIR__ . '/../Models/ContaReceber.php';

class ReceberController {
    private $model;

    public function __construct($pdo) {
        $this->model = new ContaReceber($pdo);
    }

    public function listar() {
        $company_id = $_SESSION['company_id'];
        return $this->model->listar($company_id);
    }

    public function criar($dados) {
        $company_id = $_SESSION['company_id'];

        return $this->model->criar(
            $company_id,
            $dados['cliente'],
            $dados['descricao'],
            $dados['valor'],
            $dados['data']
        );
    }

    public function marcarComoPago($id) {
        return $this->model->marcarComoPago($id);
    }

    public function edit($id)
{
    session_start();
    require '../config/database.php';

    $company_id = $_SESSION['company_id'];

    $stmt = $pdo->prepare("
        SELECT * FROM accounts_receivable
        WHERE id=? AND company_id=?
    ");

    $stmt->execute([$id,$company_id]);

    $receber = $stmt->fetch();

    require '../app/Views/receber/editar.php';
}

public function update($id)
{
    session_start();
    require '../config/database.php';

    $company_id = $_SESSION['company_id'];

    $stmt = $pdo->prepare("
        UPDATE accounts_receivable
        SET client=?, description=?, amount=?, date=?
        WHERE id=? AND company_id=?
    ");

    $stmt->execute([
        $_POST['cliente'],
        $_POST['descricao'],
        $_POST['valor'],
        $_POST['data'],
        $id,
        $company_id
    ]);

    header("Location: receber.php");
    exit;
}

public function delete($id)
{
    session_start();
    require '../config/database.php';

    $company_id = $_SESSION['company_id'];

    $stmt = $pdo->prepare("
        DELETE FROM accounts_receivable
        WHERE id=? AND company_id=?
    ");

    $stmt->execute([$id,$company_id]);

    header("Location: receber.php");
    exit;
}

}
