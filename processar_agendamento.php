<?php
/**
 * processar_agendamento.php
 * Script responsável por processar e registrar um novo agendamento de recebimento.
 */

session_start();
require 'db.php'; // Conexão com o banco de dados

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Log dos dados recebidos via POST para depuração
file_put_contents('debug_post.txt', print_r($_POST, true));

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

function usuarioPodeUltrapassarLimite() {
    return isset($_SESSION['tipoUsuario']) && in_array($_SESSION['tipoUsuario'], ['portaria', 'supervisor']);
}

function verificarLimiteHoras($data, $tipoCaminhao, $tipoCarga, $placa, $nomeResponsavel, $pdo) {
    $minutosNovo = minutosPorTipo($tipoCaminhao, $tipoCarga);
    $limiteDia = 18 * 60;

    $sql = "SELECT tipo_caminhao, tipo_carga, COUNT(*) as total FROM agendamentos WHERE data_agendamento = :data GROUP BY tipo_caminhao, tipo_carga";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':data' => $data]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalMinutos = 0;
    $contadorBatida = 0;

    foreach ($agendamentos as $ag) {
        $totalMinutos += minutosPorTipo($ag['tipo_caminhao'], $ag['tipo_carga']) * $ag['total'];
        
        // 2. Se a carga no banco for 'batida', somamos ao contador
        if (strtolower($ag['tipo_carga']) === 'batida') {
            $contadorBatida += $ag['total'];
        }
    }

    if (strtolower($tipoCarga) === 'batida' && $contadorBatida >= 2) {
        return "Limite de cargas tipo BATIDA atingido para este dia (máximo 2).";
    }

    // Trava: placa já agendada no mesmo dia
    $sqlPlaca = "SELECT COUNT(*) FROM agendamentos WHERE data_agendamento = :data AND placa = :placa";
    $stmtPlaca = $pdo->prepare($sqlPlaca);
    $stmtPlaca->execute([':data' => $data, ':placa' => $placa]);
    if ($stmtPlaca->fetchColumn() > 0) {
        return "Já existe um agendamento para esta placa neste dia.";
    }

    // Trava: usuário público não pode agendar duplicado (nome + placa)
    if (isset($_POST['origem']) && $_POST['origem'] === 'publica') {
        $sqlDup = "SELECT COUNT(*) FROM agendamentos WHERE data_agendamento = :data AND placa = :placa AND nome_responsavel = :nome";
        $stmtDup = $pdo->prepare($sqlDup);
        $stmtDup->execute([':data' => $data, ':placa' => $placa, ':nome' => $nomeResponsavel]);
        if ($stmtDup->fetchColumn() > 0) {
            return "Você já fez um agendamento com este nome e placa neste dia.";
        }
    }

    if (($totalMinutos + $minutosNovo) > $limiteDia && !usuarioPodeUltrapassarLimite()) {
        return "Limite diário de 18 horas atingido para agendamento.";
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // Sempre retorna JSON

    // Obtém os dados do formulário
    $dataAgendamento = $_POST['dataAgendamento'] ?? '';
    $tipoCaminhao = $_POST['tipoCaminhao'] ?? '';
    $tipoCarga = $_POST['tipoCarga'] ?? '';
    $tipoMercadoria = $_POST['tipoMercadoria'] ?? '';
    $fornecedor = $_POST['fornecedor'] ?? '';
    $nomeResponsavel = $_POST['nome_responsavel'] ?? '';
    $quantidadePaletes = $_POST['quantidadePaletes'] ?? '';
    $quantidadeVolumes = $_POST['quantidadeVolumes'] ?? '';
    $placa = $_POST['placa'] ?? '';
    $nomeMotorista = $_POST['nomeMotorista'] ?? '';
    $cpfMotorista = $_POST['cpfMotorista'] ?? '';
    $numeroContato = $_POST['numeroContato'] ?? '';
    $tipoRecebimento = $_POST['tipoRecebimento'] ?? '';
    $comprador = $_POST['comprador'] ?? '';
    $origem = $_POST['origem'] ?? '';
    $status = 'PENDENTE';
    $forcarAgendamento = $_POST['forcarAgendamento'] ?? false;

    // Dados do motorista interno autorizado para múltiplos agendamentos no mesmo dia
    $motoristaInterno = [
        'nome' => 'RONALDO SANTOS DA SILVA',
        'cpf' => '53516464801', // sem pontos e traço
        'placa' => 'TMB6I80'
    ];

    date_default_timezone_set('America/Sao_Paulo'); // ajuste para seu fuso
    $hoje = date('Y-m-d');
    $horaAtual = date('H:i');

    if ($origem === 'publica' && $dataAgendamento === $hoje && $horaAtual >= '11:00') {
        echo json_encode(['success' => false, 'message' => 'Agendamentos para hoje só podem ser feitos até as 11:00 nesta página.']);
        exit();
    }

    // Normaliza os dados para evitar duplicidade por diferença de caixa ou espaços
    $placa = strtoupper(trim($placa));
    $nomeMotorista = strtoupper(trim($nomeMotorista));
    $cpfMotorista = preg_replace('/\D/', '', $cpfMotorista); // remove tudo que não for número

    // Normaliza os dados do motorista interno para comparação
    $nomeMotoristaInterno = strtoupper(trim($motoristaInterno['nome']));
    $placaMotoristaInterno = strtoupper(trim($motoristaInterno['placa']));
    $cpfMotoristaInterno = preg_replace('/\D/', '', $motoristaInterno['cpf']);

    $isMotoristaInterno = (
        $nomeMotorista === $nomeMotoristaInterno &&
        $placa === $placaMotoristaInterno &&
        $cpfMotorista === $cpfMotoristaInterno
    );

    // --- VERIFICAÇÃO DE DUPLICIDADE (placa, nome do motorista e CPF na mesma data) ---
    if (!$isMotoristaInterno) {
        $sqlVerifica = "SELECT COUNT(*) FROM agendamentos WHERE 
            data_agendamento = :dataAgendamento AND
            UPPER(TRIM(placa)) = :placa AND
            UPPER(TRIM(nome_motorista)) = :nomeMotorista AND
            REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cpf_motorista,'-',''),' ',''),'.',''),'/',''),',',''),';',''),':',''),'?',''),'!',''),'_','') = :cpfMotorista
        ";
        $stmtVerifica = $pdo->prepare($sqlVerifica);
        $stmtVerifica->execute([
            ':dataAgendamento' => $dataAgendamento,
            ':placa' => $placa,
            ':nomeMotorista' => $nomeMotorista,
            ':cpfMotorista' => $cpfMotorista
        ]);
        $duplicado = $stmtVerifica->fetchColumn();

        if ($duplicado > 0) {
            echo json_encode(['success' => false, 'message' => 'Agendamento já existente. Mude as informações do motorista para prosseguir.']);
            exit();
        }
    }

    // Validação do campo comprador (só exige se não for da portaria)
    if ($origem !== 'portaria' && empty($comprador)) {
        echo json_encode(['success' => false, 'message' => 'O campo comprador é obrigatório.']);
        exit();
    }    

    // Verifica limite de horas
    $resultadoLimiteHoras = verificarLimiteHoras($dataAgendamento, $tipoCaminhao, $tipoCarga, $placa, $nomeResponsavel, $pdo);

    if ($resultadoLimiteHoras !== true) {
        echo json_encode([
            'success' => false, 
            'message' => $resultadoLimiteHoras // Aqui vai a frase: "Limite de cargas tipo BATIDA..."
        ]);
        exit(); // VITAL: Isso impede que o código continue e chegue no INSERT ou no "Sucesso" final
    }

    // Bloqueio de duplicidade total (todos os campos principais)
    $sqlDup = "SELECT COUNT(*) FROM agendamentos WHERE 
        data_agendamento = :dataAgendamento AND
        tipo_caminhao = :tipoCaminhao AND
        tipo_carga = :tipoCarga AND
        tipo_mercadoria = :tipoMercadoria AND
        fornecedor = :fornecedor AND
        nome_responsavel = :nomeResponsavel AND
        quantidade_paletes = :quantidadePaletes AND
        quantidade_volumes = :quantidadeVolumes AND
        placa = :placa AND
        nome_motorista = :nomeMotorista AND
        cpf_motorista = :cpfMotorista AND
        numero_contato = :numeroContato AND
        tipo_recebimento = :tipoRecebimento AND
        comprador = :comprador
    ";
    $stmtDup = $pdo->prepare($sqlDup);
    $stmtDup->execute([
        ':dataAgendamento' => $dataAgendamento,
        ':tipoCaminhao' => $tipoCaminhao,
        ':tipoCarga' => $tipoCarga,
        ':tipoMercadoria' => $tipoMercadoria,
        ':fornecedor' => $fornecedor,
        ':nomeResponsavel' => $nomeResponsavel,
        ':quantidadePaletes' => $quantidadePaletes,
        ':quantidadeVolumes' => $quantidadeVolumes,
        ':placa' => $placa,
        ':nomeMotorista' => $nomeMotorista,
        ':cpfMotorista' => $cpfMotorista,
        ':numeroContato' => $numeroContato,
        ':tipoRecebimento' => $tipoRecebimento,
        ':comprador' => $comprador
    ]);
    if ($stmtDup->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Já existe um agendamento com todos esses dados para esta data.']);
        exit();
    }

    // Insere o agendamento no banco de dados
    $sql = "INSERT INTO agendamentos (
        data_agendamento, tipo_caminhao, tipo_carga, tipo_mercadoria, fornecedor, nome_responsavel,
        quantidade_paletes, quantidade_volumes, placa, nome_motorista, cpf_motorista, numero_contato, tipo_recebimento, comprador, status
    ) VALUES (
        :dataAgendamento, :tipoCaminhao, :tipoCarga, :tipoMercadoria, :fornecedor, :nomeResponsavel,
        :quantidadePaletes, :quantidadeVolumes, :placa, :nomeMotorista, :cpfMotorista, :numeroContato, :tipoRecebimento, :comprador, :status
    )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':dataAgendamento' => $dataAgendamento,
        ':tipoCaminhao' => $tipoCaminhao,
        ':tipoCarga' => $tipoCarga,
        ':tipoMercadoria' => $tipoMercadoria,
        ':fornecedor' => $fornecedor,
        ':nomeResponsavel' => $nomeResponsavel,
        ':quantidadePaletes' => $quantidadePaletes,
        ':quantidadeVolumes' => $quantidadeVolumes,
        ':placa' => $placa,
        ':nomeMotorista' => $nomeMotorista,
        ':cpfMotorista' => $cpfMotorista,
        ':numeroContato' => $numeroContato,
        ':tipoRecebimento' => $tipoRecebimento,
        ':comprador' => $comprador,
        ':status' => $status
    ]);

    $agendamento_id = $pdo->lastInsertId();

    // Processar notas fiscais
    if (isset($_POST['notasFiscaisJSON']) && !empty($_POST['notasFiscaisJSON'])) {
        $notasFiscais = json_decode($_POST['notasFiscaisJSON'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($notasFiscais) && !empty($notasFiscais)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO notas_fiscais (agendamento_id, numero_nota) VALUES (?, ?)");
                foreach ($notasFiscais as $nota) {
                    if (!empty(trim($nota))) {
                        $stmt->execute([$agendamento_id, trim($nota)]);
                    }
                }
            } catch (PDOException $e) {
                error_log('Erro ao inserir notas fiscais: ' . $e->getMessage());
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Agendamento realizado com sucesso!']);
    exit();
}
?>
