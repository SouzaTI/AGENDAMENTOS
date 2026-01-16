<?php
/**
 * get_notas_fiscais.php
 * 
 * Retorna as notas fiscais de um agendamento específico.
 */

require 'db.php';
header('Content-Type: application/json');

$agendamento_id = $_GET['agendamento_id'] ?? 0;

if (!$agendamento_id) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT * FROM notas_fiscais WHERE agendamento_id = :agendamento_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['agendamento_id' => $agendamento_id]);
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($notas);
} catch (PDOException $e) {
    echo json_encode([]);
}