<?php
/**
 * chamar_motorista.php
 *
 * Endpoint para registrar a chamada de um motorista para atendimento.
 *
 * FUNCIONALIDADE:
 * - Recebe o ID do agendamento via requisição JSON (POST).
 * - Insere ou atualiza (REPLACE) um registro na tabela 'chamadas' com o ID do agendamento e o horário da chamada.
 * - Retorna um JSON indicando sucesso.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Utiliza PDO para inserção segura no banco de dados.
 * - O uso de REPLACE INTO garante que, se já existir uma chamada para o mesmo agendamento, ela será atualizada.
 * - Utilizado para acionar o painel de chamada de motoristas em tempo real.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Espera que a tabela 'chamadas' possua os campos: agendamento_id, chamada_em.
 */

require 'db.php';
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare("REPLACE INTO chamadas (agendamento_id, chamada_em) VALUES (:id, NOW())");
    $stmt->execute([':id' => $id]);
}
echo json_encode(['success' => true]);