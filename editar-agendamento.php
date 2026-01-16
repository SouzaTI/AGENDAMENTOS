<?php
/**
 * editar-agendamento.php
 *
 * Endpoint para editar ou excluir um agendamento existente.
 *
 * FUNCIONALIDADE:
 * - Recebe dados via POST para atualizar ou excluir um agendamento.
 * - Se o parâmetro "excluir" for enviado, remove o agendamento e suas conferências associadas.
 * - Se não for exclusão, atualiza os dados do agendamento conforme os campos recebidos.
 * - Antes de atualizar, verifica se já existe outro agendamento com a mesma senha para o mesmo dia (evita duplicidade).
 * - Retorna resposta JSON indicando sucesso ou erro.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Utiliza PDO para consultas e operações seguras no banco de dados.
 * - Exclui primeiro as conferências relacionadas antes de remover o agendamento.
 * - Em caso de atualização, faz validação de unicidade da senha por data.
 * - Retorna mensagens de erro apropriadas em caso de falha.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Espera que as tabelas 'agendamentos' e 'conferencias_recebimento' estejam corretamente configuradas.
 */

header('Content-Type: application/json');
require 'db.php';

$id = $_POST['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID não informado']);
    exit;
}

// Log do ID que será excluído
error_log('ID para excluir: ' . $id);

// Se o parâmetro "excluir" estiver definido, executa a exclusão
if (isset($_POST['excluir']) && $_POST['excluir'] == "true") {
    // Exclui conferências relacionadas primeiro
    $stmtConf = $pdo->prepare("DELETE FROM conferencias_recebimento WHERE agendamento_id = :id");
    $stmtConf->execute([':id' => $id]);

    // Agora exclui o agendamento
    $stmt = $pdo->prepare("DELETE FROM agendamentos WHERE id = :id");
    if ($stmt->execute([':id' => $id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir']);
    }
    exit;
}

// Caso contrário, atualiza o agendamento
$tipo_caminhao = $_POST['tipo_caminhao'];
$tipo_carga = $_POST['tipo_carga'];
$tipo_mercadoria = $_POST['tipo_mercadoria'];
$fornecedor = $_POST['fornecedor'];
$data_agendamento = $_POST['data_agendamento']; // novo campo de data
$quantidade_paletes = $_POST['quantidade_paletes'];
$quantidade_volumes = $_POST['quantidade_volumes'];
$placa = $_POST['placa'];
$comprador = $_POST['comprador'];
$nome_motorista = $_POST['nome_motorista'];
$cpf_motorista = $_POST['cpf_motorista'];
$numero_contato = $_POST['numero_contato'];
$tipo_recebimento = $_POST['tipo_recebimento'];
$novaSenha = $_POST['senha'];

// Verifica se já existe outro agendamento com a mesma senha para o mesmo dia
$stmt = $pdo->prepare("SELECT id FROM agendamentos WHERE senha = :senha AND data_agendamento = :data AND id != :id");
$stmt->execute([
    ':senha' => $novaSenha,
    ':data' => $data_agendamento,
    ':id' => $id
]);
$duplicado = $stmt->fetch();

if ($duplicado) {
    // Já existe outro agendamento com essa senha para o mesmo dia
    echo json_encode(['success' => false, 'message' => 'Já existe outro agendamento com essa senha para este dia.']);
    exit;
}

// Se não houver duplicidade, atualiza normalmente
$sql = "UPDATE agendamentos SET 
    tipo_caminhao = :tipo_caminhao,
    tipo_carga = :tipo_carga,
    tipo_mercadoria = :tipo_mercadoria,
    fornecedor = :fornecedor,
    data_agendamento = :data_agendamento,
    quantidade_paletes = :quantidade_paletes,
    quantidade_volumes = :quantidade_volumes,
    placa = :placa,
    comprador = :comprador,
    nome_motorista = :nome_motorista,
    cpf_motorista = :cpf_motorista,
    numero_contato = :numero_contato,
    tipo_recebimento = :tipo_recebimento,
    senha = :senha
    WHERE id = :id";
$stmt = $pdo->prepare($sql);
if ($stmt->execute([
    ':tipo_caminhao' => $tipo_caminhao,
    ':tipo_carga' => $tipo_carga,
    ':tipo_mercadoria' => $tipo_mercadoria,
    ':fornecedor' => $fornecedor,
    ':data_agendamento' => $data_agendamento,
    ':quantidade_paletes' => $quantidade_paletes,
    ':quantidade_volumes' => $quantidade_volumes,
    ':placa' => $placa,
    ':comprador' => $comprador,
    ':nome_motorista' => $nome_motorista,
    ':cpf_motorista' => $cpf_motorista,
    ':numero_contato' => $numero_contato,
    ':tipo_recebimento' => $tipo_recebimento,
    ':senha' => $novaSenha,
    ':id' => $id
])) {
    echo json_encode(['success' => true, 'message' => 'Agendamento atualizado']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar']);
}