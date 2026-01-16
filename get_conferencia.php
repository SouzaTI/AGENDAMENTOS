<?php
/**
 * get_conferencia.php
 *
 * Endpoint para buscar os dados de conferência de um agendamento específico.
 *
 * FUNCIONALIDADE:
 * - Recebe o ID do agendamento via parâmetro GET.
 * - Consulta a tabela 'conferencias_recebimento' para buscar os dados da conferência correspondente.
 * - Retorna os dados encontrados em formato JSON.
 * - Caso não encontre, retorna um array vazio em JSON.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Utiliza PDO para consulta segura ao banco de dados.
 * - Define o cabeçalho da resposta como JSON.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Espera que a tabela 'conferencias_recebimento' possua o campo 'agendamento_id'.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'db.php';

header('Content-Type: application/json');

$id_agendamento = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM conferencias_recebimento WHERE agendamento_id = ?");
$stmt->execute([$id_agendamento]);
$conferencia = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($conferencia ?: []);