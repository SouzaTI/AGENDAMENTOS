<?php
require 'db.php';
$data = json_decode(file_get_contents('php://input'), true)['data'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE data_agendamento = :data");
$stmt->execute([':data' => $data]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));