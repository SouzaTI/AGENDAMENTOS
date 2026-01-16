<?php
/**
 * registrar_conferencia.php
 *
 * Endpoint para registrar a conferência de recebimento de um agendamento.
 *
 * FUNCIONALIDADE:
 * - Recebe dados do formulário via POST (id do agendamento, paletes, volumes, observações, nome do conferente).
 * - Valida os dados recebidos.
 * - Busca a senha do agendamento.
 * - Insere os dados na tabela 'conferencias_recebimento', incluindo a senha e data/hora da conferência.
 * - Atualiza o status do agendamento para "Recebido".
 * - Calcula o tempo entre a chegada da NF e a conferência, salvando esse tempo no campo 'tempo' do agendamento.
 * - Retorna resposta JSON indicando sucesso ou erro.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Utiliza PDO para consultas e inserções seguras no banco de dados.
 * - Aceita zero como valor válido para paletes/volumes.
 * - Calcula o tempo decorrido entre chegada_nf e conferência no formato HH:MM.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Espera que as tabelas 'agendamentos' e 'conferencias_recebimento' estejam corretamente configuradas.
 */

require 'db.php';
session_start();

header('Content-Type: application/json');

$tipoUsuario = $_SESSION['tipoUsuario'] ?? '';
if (!in_array($tipoUsuario, ['recebimento', 'supervisor'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Recebe os dados do formulário
$agendamento_id = $_POST['agendamento_id'] ?? null;
$paletes_recebidos = $_POST['paletes_recebidos'] ?? null;
$volumes_recebidos = $_POST['volumes_recebidos'] ?? null;
$observacoes = $_POST['observacoes'] ?? null;
$nome_conferente = $_POST['nome_conferente'] ?? null;

// Validação correta: aceita zero, mas não vazio ou nulo
if (
    $agendamento_id === null || $agendamento_id === '' ||
    $paletes_recebidos === null || $paletes_recebidos === '' ||
    $volumes_recebidos === null || $volumes_recebidos === '' ||
    empty($nome_conferente)
) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit;
}

// Buscar a senha do agendamento
$stmtSenha = $pdo->prepare("SELECT senha, chegada_nf FROM agendamentos WHERE id = :id");
$stmtSenha->execute([':id' => $agendamento_id]);
$row = $stmtSenha->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Agendamento não encontrado.']);
    exit;
}

$senha = $row['senha'];
$chegada_nf = $row['chegada_nf'];

// Insere na tabela de conferências, incluindo a senha
$sql = "INSERT INTO conferencias_recebimento 
            (agendamento_id, senha, paletes_recebidos, volumes_recebidos, observacoes, nome_conferente, data_conferencia)
        VALUES 
            (:agendamento_id, :senha, :paletes_recebidos, :volumes_recebidos, :observacoes, :nome_conferente, NOW())";
$stmt = $pdo->prepare($sql);
$ok = $stmt->execute([
    ':agendamento_id' => $agendamento_id,
    ':senha' => $senha,
    ':paletes_recebidos' => $paletes_recebidos,
    ':volumes_recebidos' => $volumes_recebidos,
    ':observacoes' => $observacoes,
    ':nome_conferente' => $nome_conferente
]);

if ($ok) {
    // Atualiza o status do agendamento para "Recebido"
    $stmt2 = $pdo->prepare("UPDATE agendamentos SET status = 'Recebido' WHERE id = :id");
    $stmt2->execute([':id' => $agendamento_id]);

    // Calcula o tempo entre chegada_nf e conferência, se chegada_nf existir
    if ($chegada_nf) {
        $data_conferencia = date('Y-m-d H:i:s');
        $inicio = strtotime($chegada_nf);
        $fim = strtotime($data_conferencia);

        if ($inicio && $fim && $fim > $inicio) {
            $diff = $fim - $inicio;
            $horas = floor($diff / 3600);
            $minutos = floor(($diff % 3600) / 60);
            $tempo = sprintf('%02d:%02d', $horas, $minutos);

            // Salva no banco
            $stmt = $pdo->prepare("UPDATE agendamentos SET tempo = ? WHERE id = ?");
            $stmt->execute([$tempo, $agendamento_id]);
        }
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco.']);
}

file_put_contents('debug_conferencia_post.txt', print_r($_POST, true), FILE_APPEND);