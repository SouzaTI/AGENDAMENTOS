<?php
/**
 * editar_portaria.php
 * 
 * Permite que portaria, supervisor e admin editem qualquer campo do agendamento.
 */

require 'db.php';
session_start();

header('Content-Type: application/json');

$tipoUsuario = $_SESSION['tipoUsuario'] ?? '';
if (!in_array($tipoUsuario, ['portaria', 'supervisor', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

// Se for apenas busca de dados (AJAX), retorna os dados do agendamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && count($_POST) === 1) {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE id = ?");
    $stmt->execute([$id]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($agendamento) {
        echo json_encode($agendamento);
    } else {
        echo json_encode(['success' => false, 'message' => 'Agendamento não encontrado.']);
    }
    exit;
}

// Se for edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID do agendamento não informado']);
        exit;
    }

    // Busca os campos da tabela agendamentos
    $stmt = $pdo->query("DESCRIBE agendamentos");
    $colunas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Remove o campo 'id' da lista de atualização
    $colunas = array_diff($colunas, ['id']);

    $params = [':id' => $id];
    $sql_parts = [];

    foreach ($colunas as $campo) {
        if (isset($_POST[$campo])) {
            $sql_parts[] = "$campo = :$campo";
            $params[":$campo"] = $_POST[$campo];
        }
    }

    if (empty($sql_parts)) {
        echo json_encode(['success' => false, 'message' => 'Nenhum dado para atualizar']);
        exit;
    }

    $sql = "UPDATE agendamentos SET " . implode(', ', $sql_parts) . " WHERE id = :id";

    try {
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao atualizar o registro']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>