<?php
/**
 * atualizar_local_recebimento.php
 *
 * Endpoint para atualizar o local de recebimento de um agendamento.
 *
 * FUNCIONALIDADE:
 * - Recebe via JSON o ID do agendamento e o novo local de recebimento.
 * - Atualiza o campo 'local_recebimento' na tabela 'agendamentos' para o ID informado.
 * - Retorna resposta JSON indicando sucesso.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Utiliza PDO para atualização segura no banco de dados.
 * - Espera receber os dados via corpo da requisição (php://input).
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Espera que a tabela 'agendamentos' possua o campo 'local_recebimento'.
 */

require 'db.php';
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'];
$local = $data['local'];
$stmt = $pdo->prepare("UPDATE agendamentos SET local_recebimento = :local WHERE id = :id");
$stmt->execute([':local' => $local, ':id' => $id]);
echo json_encode(['success'=>true]);