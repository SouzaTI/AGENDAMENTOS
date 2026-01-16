<?php
/**
 * verificar_dias.php
 *
 * Endpoint para consulta dos dias disponíveis, bloqueados, parcialmente e totalmente agendados em um mês.
 *
 * FUNCIONALIDADE:
 * - Recebe mês e ano via parâmetros GET (ou usa o mês/ano atual por padrão).
 * - Busca no banco de dados:
 *   - Dias bloqueados manualmente.
 *   - Limites de agendamento por tipo de caminhão.
 *   - Dias com agendamentos realizados e suas quantidades.
 *   - Se há agendamentos em meses anteriores.
 * - Classifica os dias do mês em:
 *   - Bloqueados (manual, finais de semana, dias passados).
 *   - Parcialmente agendados (ainda há vagas para algum tipo de caminhão).
 *   - Totalmente agendados (todos os limites atingidos).
 *   - Disponíveis (não bloqueados e com vagas).
 * - Retorna um JSON com arrays de dias para cada categoria e se há agendamentos em meses anteriores.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Considera sábados, domingos e dias anteriores ao atual como bloqueados.
 * - Usa as tabelas 'agendamentos', 'dias_bloqueados' e 'limites_agendamentos'.
 * - O resultado é usado para exibir um calendário de agendamento.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Espera que as tabelas estejam corretamente configuradas.
 */

require 'db.php';

// Parâmetros de mês e ano
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$startDate = "$year-$month-01";
$endDate = date("Y-m-t", strtotime($startDate));

// Função para minutos por tipo
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

// Dias bloqueados
$sqlBloqueados = "SELECT data FROM dias_bloqueados WHERE data BETWEEN :startDate AND :endDate";
$stmtBloqueados = $pdo->prepare($sqlBloqueados);
$stmtBloqueados->execute(['startDate' => $startDate, 'endDate' => $endDate]);
$diasBloqueados = $stmtBloqueados->fetchAll(PDO::FETCH_COLUMN);



// Agendamentos do mês
$sqlReservados = "SELECT data_agendamento, tipo_caminhao, tipo_carga, COUNT(*) as total_reservas 
                  FROM agendamentos 
                  WHERE data_agendamento BETWEEN :startDate AND :endDate 
                  GROUP BY data_agendamento, tipo_caminhao, tipo_carga";
$stmtReservados = $pdo->prepare($sqlReservados);
$stmtReservados->execute(['startDate' => $startDate, 'endDate' => $endDate]);
$diasReservados = $stmtReservados->fetchAll(PDO::FETCH_ASSOC);

// Soma minutos agendados por dia
$minutosPorDia = [];
foreach ($diasReservados as $dia) {
    $dataDia = $dia['data_agendamento'];
    $tipoCaminhao = $dia['tipo_caminhao'];
    $tipoCarga = $dia['tipo_carga'] ?? '';
    $totalReservas = (int)$dia['total_reservas'];
    $minutos = minutosPorTipo($tipoCaminhao, $tipoCarga) * $totalReservas;
    if (!isset($minutosPorDia[$dataDia])) $minutosPorDia[$dataDia] = 0;
    $minutosPorDia[$dataDia] += $minutos;
}

// Classificação dos dias
$totalmenteAgendados = [];
$parcialmenteAgendados = [];
foreach ($minutosPorDia as $dataDia => $minutos) {
    $minRestante = $limiteDia - $minutos;
    if ($minRestante < 30) { // Não cabe mais nenhum caminhão
        $totalmenteAgendados[] = $dataDia;
    } else if ($minutos > 0) {
        $parcialmenteAgendados[] = $dataDia;
    }
}

// Todos os dias do mês
$todosOsDias = [];
$periodo = new DatePeriod(
    new DateTime($startDate),
    new DateInterval('P1D'),
    (new DateTime($endDate))->modify('+1 day')
);

$dataAtual = new DateTime();
$dataAtual->setTime(0, 0, 0);

foreach ($periodo as $dia) {
    $todosOsDias[] = $dia->format('Y-m-d');
    // Bloquear sábados e domingos
    if ($dia->format('w') == 0 || $dia->format('w') == 6) {
        $diasBloqueados[] = $dia->format('Y-m-d');
    }
    // Bloquear dias anteriores ao atual
    if ($dia < $dataAtual) {
        $diasBloqueados[] = $dia->format('Y-m-d');
    }
}

$diasBloqueados = array_values(array_unique($diasBloqueados));
$diasDisponiveis = array_values(array_diff($todosOsDias, $diasBloqueados));

// Retornar também os minutos agendados por dia para o painel
header('Content-Type: application/json');
echo json_encode([
    "bloqueados" => $diasBloqueados,
    "parcialmenteAgendados" => $parcialmenteAgendados,
    "totalmenteAgendados" => $totalmenteAgendados,
    "disponiveis" => $diasDisponiveis,
    "minutosPorDia" => $minutosPorDia, // Para o painel de tempo
    "limiteMinutosDia" => $limiteDia
]);
?>
