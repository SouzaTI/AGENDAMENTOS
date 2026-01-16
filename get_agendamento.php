<?php
/**
 * get_agendamento.php
 * 
 * Retorna os detalhes de um agendamento específico.
 */

require 'db.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['error' => 'ID não informado']);
    exit;
}

try {
    $sql = "SELECT * FROM agendamentos WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($agendamento) {
        echo json_encode($agendamento);
    } else {
        echo json_encode(['error' => 'Agendamento não encontrado']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro no banco de dados']);
}