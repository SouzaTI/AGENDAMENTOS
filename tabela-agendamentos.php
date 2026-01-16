<?php
require 'db.php';

// Recebe a data do filtro (formato dd/mm/yyyy) ou usa o dia atual
$dataFiltro = isset($_GET['data']) && $_GET['data'] ? $_GET['data'] : date('d/m/Y');
$partes = explode('/', $dataFiltro);
if (count($partes) === 3) {
    $dataSQL = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
} else {
    $dataSQL = date('Y-m-d');
}

$sql = "SELECT * FROM agendamentos WHERE data_agendamento = :data";
$stmt = $pdo->prepare($sql);
$stmt->execute([':data' => $dataSQL]);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca observações de conferências
$conferencias = [];
$stmt = $pdo->query("SELECT agendamento_id, observacoes FROM conferencias_recebimento WHERE observacoes IS NOT NULL AND observacoes != ''");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $conf) {
    $conferencias[$conf['agendamento_id']] = $conf['observacoes'];
}
?>

<?php foreach ($agendamentos as $agendamento): ?>
<tr class="table-row-wrapper" style="position: relative;">
    <td><?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?></td>
    <td><?= htmlspecialchars($agendamento['tipo_caminhao']) ?></td>
    <td><?= htmlspecialchars($agendamento['tipo_carga']) ?></td>
    <td><?= htmlspecialchars($agendamento['tipo_mercadoria']) ?></td>
    <td>
        <?php if (
            $agendamento['status'] == 'Recebido' &&
            !empty($conferencias[$agendamento['id']])
        ): ?>
            <span title="Possui observação na conferência" class="obs-icone">&#9888;</span>
        <?php endif; ?>
        <?= htmlspecialchars($agendamento['fornecedor']) ?>
    </td>
    <td><?= htmlspecialchars($agendamento['quantidade_paletes']) ?></td>
    <td><?= htmlspecialchars($agendamento['quantidade_volumes']) ?></td>
    <td><?= htmlspecialchars($agendamento['placa']) ?></td>
    <td class="status-receber">
        <select class="status-select" data-id="<?= $agendamento['id'] ?>" onchange="atualizarStatus(this, <?= $agendamento['id'] ?>)">
            <option value="" <?= empty($agendamento['status']) ? 'selected' : '' ?>>Selecione o status</option>
            <option value="Chegada NF" <?= $agendamento['status'] == 'Chegada NF' ? 'selected' : '' ?>>Chegada NF</option>
            <option value="Liberado" <?= $agendamento['status'] == 'Liberado' ? 'selected' : '' ?>>Liberado</option>
            <option value="Em Analise" <?= $agendamento['status'] == 'Em Analise' ? 'selected' : '' ?>>Em Analise</option>
            <option value="Recebendo" <?= $agendamento['status'] == 'Recebendo' ? 'selected' : '' ?>>Recebendo</option>
            <option value="Recebido" <?= $agendamento['status'] == 'Recebido' ? 'selected' : '' ?>>Recebido</option>
            <option value="Recusado" <?= $agendamento['status'] == 'Recusado' ? 'selected' : '' ?>>Recusado</option>
        </select>
    </td>
    <td><?= htmlspecialchars($agendamento['comprador']) ?></td>
    <td><?= htmlspecialchars($agendamento['nome_motorista']) ?></td>
    <td><?= htmlspecialchars($agendamento['cpf_motorista']) ?></td>
    <td><?= htmlspecialchars($agendamento['nome_responsavel']) ?></td>
    <td><?= htmlspecialchars($agendamento['numero_contato']) ?></td>
    <td style="position:relative;">
        <?= htmlspecialchars($agendamento['tipo_recebimento']) ?>
        <button class="edit-btn" onclick="editarAgendamento(this, <?= $agendamento['id'] ?>)">Editar</button>
    </td>
</tr>
<?php endforeach; ?>