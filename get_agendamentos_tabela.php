<?php
session_start();
require 'db.php';

$dataFiltro = $_GET['data'] ?? date('Y-m-d');

$sql = "SELECT * FROM agendamentos WHERE data_agendamento = :dataFiltro ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['dataFiltro' => $dataFiltro]);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca notas fiscais relacionadas
$notasFiscais = [];
$idsAgendamentos = array_column($agendamentos, 'id');
if (!empty($idsAgendamentos)) {
    $placeholders = str_repeat('?,', count($idsAgendamentos) - 1) . '?';
    $sqlNotas = "SELECT agendamento_id, numero_nota FROM notas_fiscais WHERE agendamento_id IN ($placeholders)";
    $stmtNotas = $pdo->prepare($sqlNotas);
    $stmtNotas->execute($idsAgendamentos);
    foreach ($stmtNotas->fetchAll(PDO::FETCH_ASSOC) as $nota) {
        $notasFiscais[$nota['agendamento_id']][] = $nota['numero_nota'];
    }
}

if (empty($agendamentos)) {
    echo '<tr><td colspan="8">NENHUM AGENDAMENTO ENCONTRADO PARA ESTA DATA.</td></tr>';
} else {
    foreach ($agendamentos as $agendamento) {
        ?>
        <tr>
            <td><?= htmlspecialchars(strtoupper($agendamento['fornecedor'])) ?></td>
            <td><?= htmlspecialchars(strtoupper($agendamento['nome_motorista'])) ?></td>
            <td><?= htmlspecialchars(strtoupper($agendamento['placa'])) ?></td>
            <td><?= htmlspecialchars(strtoupper($agendamento['tipo_caminhao'])) ?></td>
            <td>
                <?php if (!empty($notasFiscais[$agendamento['id']])): ?>
                    <?= count($notasFiscais[$agendamento['id']]) ?> NOTAS
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td class="<?= $agendamento['status'] === 'Chegada NF' ? 'status-chegada-nf' : '' ?>">
                <?= htmlspecialchars(strtoupper($agendamento['status'] ?? 'PENDENTE')) ?>
            </td>
            <td class="actions-cell">
                <?php if ($agendamento['status'] !== 'Chegada NF'): ?>
                    <button class="btn-chegada" onclick="confirmarChegada(<?= $agendamento['id'] ?>)">CONFIRMAR CHEGADA</button>
                <?php endif; ?>
                <button class="btn-editar" onclick="abrirModalEditar(<?= $agendamento['id'] ?>)">EDITAR</button>
                <button class="btn-detalhes" onclick="abrirModalDetalhes(<?= $agendamento['id'] ?>)">DETALHES</button>
            </td>
        </tr>
        <?php
    }
}
?>