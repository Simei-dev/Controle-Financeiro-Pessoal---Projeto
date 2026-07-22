<?php

class ContaReceber {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function listar($company_id) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM receivables 
             WHERE company_id = ? 
             ORDER BY due_date ASC"
        );
        $stmt->execute([$company_id]);
        return $stmt->fetchAll();
    }

    public function criar($company_id, $cliente, $descricao, $valor, $data) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO receivables 
                (company_id, client, description, amount, due_date)
             VALUES (?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $company_id,
            $cliente,
            $descricao,
            $valor,
            $data
        ]);
    }

    public function marcarComoPago($id) {

    // busca a conta a receber
    $stmt = $this->pdo->prepare(
        "SELECT * FROM receivables WHERE id = ?"
    );
    $stmt->execute([$id]);
    $receber = $stmt->fetch();

    if (!$receber || $receber['status'] === 'paid') {
        return false;
    }

    try {
        $this->pdo->beginTransaction();

        // marca como pago
        $stmt = $this->pdo->prepare(
            "UPDATE receivables SET status = 'paid' WHERE id = ?"
        );
        $stmt->execute([$id]);

        // cria entrada automaticamente
        $stmt = $this->pdo->prepare(
            "INSERT INTO incomes 
                (company_id, description, amount, date)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $receber['company_id'],
            'Recebido: ' . ($receber['description'] ?? 'Conta a receber'),
            $receber['amount'],
            date('Y-m-d')
        ]);

        $this->pdo->commit();
        return true;

    } catch (Exception $e) {
        $this->pdo->rollBack();
        return false;
    }
}
}
