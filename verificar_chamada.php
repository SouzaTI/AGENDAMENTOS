<?php
/**
 * verificar_chamada.php
 *
 * Endpoint para verificar se há uma chamada de motorista pendente.
 *
 * FUNCIONALIDADE:
 * - Consulta a tabela 'chamadas' para buscar a chamada mais recente.
 * - Retorna os dados do motorista chamado (senha, nome, placa, local) em formato JSON.
 * - Após retornar a chamada, remove o registro da tabela para evitar chamadas duplicadas.
 * - Se não houver chamada, retorna JSON indicando que não há chamada pendente.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Faz JOIN entre 'chamadas' e 'agendamentos' para obter os dados completos do motorista.
 * - Ordena por data/hora da chamada (chamada_em) e limita ao mais recente.
 * - Utilizado por páginas que fazem polling para exibir o overlay de chamada ao motorista.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Espera que as tabelas 'chamadas' e 'agendamentos' estejam corretamente configuradas.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require 'db.php';
$stmt = $pdo->query("SELECT c.agendamento_id, a.senha, a.nome_motorista, a.placa, a.local_recebimento
    FROM chamadas c
    JOIN agendamentos a ON a.id = c.agendamento_id
    ORDER BY c.chamada_em DESC LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    // Limpa a chamada após exibir
    $pdo->prepare("DELETE FROM chamadas WHERE agendamento_id = ?")->execute([$row['agendamento_id']]);
    echo json_encode([
        'chamar' => true,
        'senha' => $row['senha'],
        'motorista' => $row['nome_motorista'],
        'placa' => $row['placa'],
        'local' => $row['local_recebimento']
    ]);
} else {
    echo json_encode(['chamar' => false]);
}