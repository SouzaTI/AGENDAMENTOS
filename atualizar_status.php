<?php
/**
 * atualizar_status.php
 *
 * Endpoint para atualizar o status de um agendamento.
 *
 * FUNCIONALIDADE:
 * - Recebe dados via JSON (id do agendamento e novo status).
 * - Atualiza o status do agendamento no banco de dados.
 * - Atualiza campos de data/hora conforme o novo status (ex: data_liberado, data_em_analise, etc).
 * - Se o status for "Chegada NF", atribui a próxima senha disponível e registra o horário de chegada.
 * - Se o status for limpo (''), zera campos de data/hora e senha.
 * - Retorna resposta JSON indicando sucesso ou erro.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Utiliza PDO para consultas e atualizações seguras.
 * - Utiliza session para identificar o usuário que está alterando.
 * - Faz logs para depuração.
 * - Permite expansão para registrar data de recusa, se desejado.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Espera que a tabela 'agendamentos' possua os campos de status e datas.
 */

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Sao_Paulo');
require 'db.php';
session_start();

$user = $_SESSION['usuario'] ?? '';

// Receber os dados enviados via JSON
$data = json_decode(file_get_contents('php://input'), true);

error_log("DEBUG: Dados recebidos: " . print_r($data, true));
$id = $data['id'] ?? null;
$status = isset($data['status']) ? (string)$data['status'] : '';
error_log("DEBUG: ID recebido: " . $id);
error_log("DEBUG: Status recebido: " . $status);

// Validar os dados recebidos
if (!empty($id) && isset($status)) {
    // Recupera o status atual do agendamento
    $stmt = $pdo->prepare("SELECT status FROM agendamentos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $agendamentoStatusAtual = $stmt->fetchColumn();

    // Atualizar o status no banco de dados
    $sql = "UPDATE agendamentos SET status = :status WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    // NÃO inclua senha aqui, a menos que queira alterá-la
    $stmt->execute([':status' => $status, ':id' => $id]);

    // Atualizar o campo de data/hora conforme o status
    if ($status === 'Liberado') {
        $stmt = $pdo->prepare("UPDATE agendamentos SET data_liberado = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } elseif ($status === 'Em Analise') {
        $stmt = $pdo->prepare("UPDATE agendamentos SET data_em_analise = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } elseif ($status === 'Recebendo') {
        $stmt = $pdo->prepare("UPDATE agendamentos SET data_recebendo = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } elseif ($status === 'Recebido') {
        $stmt = $pdo->prepare("UPDATE agendamentos SET data_recebido = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } elseif ($status === 'Recusado') {
        // Se quiser registrar a data de recusa, crie o campo data_recusado no banco
        // $stmt = $pdo->prepare("UPDATE agendamentos SET data_recusado = NOW() WHERE id = :id");
        // $stmt->execute([':id' => $id]);
    }

    // Se voltar para vazio, limpa campos de data/hora e senha
    if ($status === '') {
        $stmt = $pdo->prepare("UPDATE agendamentos SET data_liberado = NULL, data_em_analise = NULL, data_recebendo = NULL, senha = NULL WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    // Descubra a data do agendamento
    $stmt = $pdo->prepare("SELECT data_agendamento FROM agendamentos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $dataAgendamento = $stmt->fetchColumn();

    // Se mudou para "Chegada NF", atribui senha apenas se ainda não tiver e carimba chegada_nf
    if (mb_strtolower($status) === 'chegada nf') {
        // Verifica se o agendamento já tem senha
        $stmt = $pdo->prepare("SELECT senha, data_agendamento FROM agendamentos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $senhaAtual = $row['senha'];
        $dataAgendamento = $row['data_agendamento'];

        if ($senhaAtual === null) {
            // Descubra a próxima senha disponível para o dia
            $stmt = $pdo->prepare("SELECT MAX(senha) FROM agendamentos WHERE data_agendamento = :data AND senha IS NOT NULL");
            $stmt->execute([':data' => $dataAgendamento]);
            $proximaSenha = (int)$stmt->fetchColumn() + 1;

            // Atribui a próxima senha disponível
            $stmt = $pdo->prepare("UPDATE agendamentos SET senha = :senha WHERE id = :id");
            $stmt->execute([':senha' => $proximaSenha, ':id' => $id]);
            error_log("DEBUG: Senha $proximaSenha atribuída ao agendamento $id");
        }

        // Carimba a hora de chegada_nf
        $stmt = $pdo->prepare("UPDATE agendamentos SET chegada_nf = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } 

    error_log("DEBUG: Atualizando status para: [" . $status . "] no id: " . $id);

    echo json_encode(['success' => true]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
}
