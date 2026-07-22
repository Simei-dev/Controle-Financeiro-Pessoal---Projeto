<?php

class Company
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function criar($userId, $nome)
    {
        $sql = "INSERT INTO companies (user_id, name) VALUES (:user_id, :name)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':name' => $nome
        ]);

        return $this->db->lastInsertId();
    }

    public function buscarPorUsuario($userId)
    {
        $sql = "SELECT * FROM companies WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
