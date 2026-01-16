<?php
/**
 * verificar_disponibilidade.php
 *
 * Endpoint para consulta da disponibilidade de agendamento de caminhões por tipo em uma data específica.
 *
 * FUNCIONALIDADE:
 * - Recebe uma data via parâmetro GET.
 * - Busca no banco de dados o total de agendamentos já realizados para cada tipo de caminhão nessa data.
 * - Busca os limites máximos permitidos para cada tipo de caminhão.
 * - Calcula a disponibilidade restante para cada tipo de caminhão (limite - agendados).
 * - Retorna um JSON com a disponibilidade de cada tipo de caminhão para a data informada.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Se a data não for informada, retorna um erro em JSON.
 * - Utiliza as tabelas 'agendamentos' (para contar os agendamentos) e 'limites_agendamentos' (para obter os limites).
 * - O resultado é um array associativo: [tipo_caminhao => disponibilidade].
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Espera que as tabelas 'agendamentos' e 'limites_agendamentos' estejam corretamente configuradas.
 */

require 'db.php';

// Obter a data da requisição
$data = $_GET['data'] ?? null;

if (!$data) {
    echo json_encode(['disponivel' => false, 'mensagem' => 'Data não fornecida']);
    exit();
}

function minutosPorTipo($tipo, $tipoCarga = '') {
    $map = [
        'carreta' => 120,
        'CARRETA' => 120,
        'toco' => 30,
        'TOCO' => 30,
        'truck' => 60,
        'TRUCK' => 60,
        'utilitario' => 30,
        'UTILITARIO' => 30,
        'utilitários' => 30,
        'UTILITÁRIOS' => 30,
        'utilitarios' => 30,
        'UTILITARIOS' => 30
    ];
    // Remove acentos para garantir
    $tipoNormalizado = preg_replace('/[ÁÀÂÃ]/u', 'A', $tipo);
    $tipoNormalizado = preg_replace('/[ÉÈÊ]/u', 'E', $tipoNormalizado);
    $tipoNormalizado = preg_replace('/[ÍÌÎ]/u', 'I', $tipoNormalizado);
    $tipoNormalizado = preg_replace('/[ÓÒÔÕ]/u', 'O', $tipoNormalizado);
    $tipoNormalizado = preg_replace('/[ÚÙÛ]/u', 'U', $tipoNormalizado);

    // Se for carreta e batida, retorna 300 minutos
    if (
        (strtolower($tipo) === 'carreta' || strtoupper($tipo) === 'CARRETA' || $tipoNormalizado === 'CARRETA')
        && strtolower($tipoCarga) === 'batida'
    ) {
        return 300;
    }
    return $map[$tipo] ?? $map[$tipoNormalizado] ?? 0;
}

$limiteDia = 18 * 60;
$sql = "SELECT tipo_caminhao, tipo_carga, COUNT(*) as total FROM agendamentos WHERE data_agendamento = :data GROUP BY tipo_caminhao, tipo_carga";
$stmt = $pdo->prepare($sql);
$stmt->execute([':data' => $data]);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalMinutos = 0;
foreach ($agendamentos as $ag) {
    $totalMinutos += minutosPorTipo($ag['tipo_caminhao'], $ag['tipo_carga']) * $ag['total'];
}
$minutosRestantes = $limiteDia - $totalMinutos;

$retorno = [
    'minutosRestantes' => $minutosRestantes,
    'minutosAgendados' => $totalMinutos,
    'carreta' => ($minutosRestantes >= 120),
    'toco' => ($minutosRestantes >= 30),
    'truck' => ($minutosRestantes >= 60),
    'utilitario' => ($minutosRestantes >= 30)
];
// Retornar a resposta no formato esperado pelo frontend
header('Content-Type: application/json');
echo json_encode($retorno);
?>

