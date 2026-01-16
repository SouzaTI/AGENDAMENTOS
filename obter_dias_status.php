<?php
/**
 * obter_dias_status.php
 *
 * Endpoint para consultar o status de agendamento de cada dia do mês.
 *
 * FUNCIONALIDADE:
 * - Para cada dia do mês, verifica quantos agendamentos existem para cada tipo de caminhão.
 * - Compara a quantidade de agendamentos com os limites definidos para cada tipo.
 * - Classifica o dia como:
 *   - 'total' (totalmente agendado para pelo menos um tipo),
 *   - 'parcial' (há algum agendamento, mas não atingiu o limite de nenhum tipo),
 *   - 'disponivel' (nenhum agendamento para o dia).
 * - Retorna um array JSON com o status de cada dia.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Utiliza PDO para consultar o banco de dados.
 * - Os limites de cada tipo de caminhão estão definidos no próprio script.
 * - O resultado pode ser utilizado para exibir um calendário de disponibilidade.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Espera que a tabela 'agendamentos' possua o campo 'tipo_caminhao' e 'data_agendamento'.
 */

require 'db.php';

// Função para verificar o status de cada dia
function verificarStatusDia($data, $pdo) {
    // Limites de agendamentos por tipo de caminhão
    $limites = [
        'carreta' => 2,
        'truck' => 3,
        'toco' => 1,
        'utilitários' => 2 // Novo tipo adicionado
    ];

    // Contar os agendamentos para o tipo de caminhão no dia específico
    $sql = "SELECT tipo_caminhao FROM agendamentos WHERE data_agendamento = :dataAgendamento";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':dataAgendamento' => $data]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $statusDia = [
        'carreta' => 0,
        'truck' => 0,
        'toco' => 0,
        'utilitários' => 0 // Novo tipo adicionado
    ];

    // Contabilizando os tipos de caminhão
    foreach ($agendamentos as $agendamento) {
        if (isset($statusDia[$agendamento['tipo_caminhao']])) {
            $statusDia[$agendamento['tipo_caminhao']]++;
        }
    }

    // Determina o status do dia
    if (
        $statusDia['carreta'] >= $limites['carreta'] ||
        $statusDia['truck'] >= $limites['truck'] ||
        $statusDia['toco'] >= $limites['toco'] ||
        $statusDia['utilitários'] >= $limites['utilitários']
    ) {
        return 'total';  // Totalmente agendado
    } elseif (
        $statusDia['carreta'] > 0 ||
        $statusDia['truck'] > 0 ||
        $statusDia['toco'] > 0 ||
        $statusDia['utilitários'] > 0
    ) {
        return 'parcial';  // Parcialmente agendado
    } else {
        return 'disponivel';  // Disponível para agendamento
    }
}

// Criar um array de dias com seus respectivos status
$diasStatus = [];
for ($i = 1; $i <= 31; $i++) {  // Exemplo para os dias do mês (ajuste conforme necessário)
    $dataDia = "2025-05-" . str_pad($i, 2, "0", STR_PAD_LEFT); // Formato de data (ano-mês-dia)
    $status = verificarStatusDia($dataDia, $pdo);
    $diasStatus[] = [
        'data' => $dataDia,
        'status' => $status
    ];
}

// Retornar o status dos dias como JSON
echo json_encode($diasStatus);
?>
