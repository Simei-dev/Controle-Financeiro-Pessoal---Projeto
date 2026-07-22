<?php

class Entrada {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function listar($company_id) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM incomes WHERE company_id = ? ORDER BY date DESC"
        );
        $stmt->execute([$company_id]);
        return $stmt->fetchAll();
    }

    public function criar($company_id, $descricao, $valor, $data) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO incomes (company_id, description, amount, date)
             VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$company_id, $descricao, $valor, $data]);
    }
}
