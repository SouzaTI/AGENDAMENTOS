<?php
/**
 * confirmar_chegada.php
 * Atualiza o status do agendamento para "Chegada NF" usando a lógica de senha do atualizar_status.php
 */

header('Content-Type: application/json');

// Recebe os dados enviados via JSON
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID do agendamento não informado.']);
    exit;
}

// Monta o payload para atualizar_status.php
$data = [
    'id' => $id,
    'status' => 'Chegada NF'
];

// Faz uma requisição interna para atualizar_status.php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://192.168.0.63:8080/recebimento/atualizar_status.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

if (strpos($response, '<!DOCTYPE') !== false) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: resposta inesperada do servidor.', 'debug' => $response]);
    exit;
}

echo $response;