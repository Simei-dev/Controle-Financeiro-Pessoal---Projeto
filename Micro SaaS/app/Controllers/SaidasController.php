<?php
require_once __DIR__ . '/../Models/Saida.php';

class SaidasController {
    private $saidaModel;

    public function __construct($pdo) {
        $this->saidaModel = new Saida($pdo);
    }

    public function listar() {
        $company_id = $_SESSION['company_id'];
        return $this->saidaModel->listar($company_id);
    }

    public function criar($dados) {
        $company_id = $_SESSION['company_id'];

        return $this->saidaModel->criar(
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
        SELECT * FROM expenses
        WHERE id = ? AND company_id = ?
    ");

    $stmt->execute([$id, $company_id]);

    $saida = $stmt->fetch();

    require '../app/Views/Saidas/editar.php';
}

public function update($id)
{
    session_start();
    require '../config/database.php';

    $company_id = $_SESSION['company_id'];

    $stmt = $pdo->prepare("
        UPDATE expenses
        SET description=?, amount=?, date=?
        WHERE id=? AND company_id=?
    ");

    $stmt->execute([
        $_POST['descricao'],
        $_POST['valor'],
        $_POST['data'],
        $id,
        $company_id
    ]);

    header("Location: saidas.php");
    exit;
}

public function delete($id)
{
    session_start();
    require '../config/database.php';

    $company_id = $_SESSION['company_id'];

    $stmt = $pdo->prepare("
        DELETE FROM expenses
        WHERE id=? AND company_id=?
    ");

    $stmt->execute([$id, $company_id]);

    header("Location: saidas.php");
    exit;
}

}
