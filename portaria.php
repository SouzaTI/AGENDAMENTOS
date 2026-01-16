<?php
/**
 * portaria.php
 *
 * Página de controle de acesso para a equipe da portaria.
 *
 * FUNCIONALIDADE:
 * - Visualização dos agendamentos do dia atual
 * - Verificação de motoristas e dados do transporte
 * - Confirmação da chegada do motorista (mudança para status "Chegada NF")
 * - Edição de informações básicas como motorista, placa, etc.
 * - Verificação das notas fiscais relacionadas ao agendamento
 * 
 * Esta é a interface de entrada do sistema quando o motorista chega fisicamente.
 */

session_start();
require 'db.php';

// Verifica se o usuário está logado
$usuario = $_SESSION['usuario'] ?? null;
$tipoUsuario = $_SESSION['tipoUsuario'] ?? null;

if (!$usuario || !in_array($tipoUsuario, ['portaria', 'supervisor', 'admin'])) {
    header("Location: login.php");
    exit();
}

// Obtém a data atual para filtro inicial
$dataAtual = date('Y-m-d');
$dataFiltro = $_GET['data'] ?? $dataAtual;

// Busca os agendamentos do dia selecionado
$sql = "SELECT * FROM agendamentos WHERE data_agendamento = :dataFiltro ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['dataFiltro' => $dataFiltro]);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca notas fiscais relacionadas aos agendamentos
$notasFiscais = [];
$idsAgendamentos = array_column($agendamentos, 'id');

if (!empty($idsAgendamentos)) {
    $placeholders = str_repeat('?,', count($idsAgendamentos) - 1) . '?';
    $sql = "SELECT agendamento_id, numero_nota FROM notas_fiscais WHERE agendamento_id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($idsAgendamentos);
    
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $nota) {
        $notasFiscais[$nota['agendamento_id']][] = $nota['numero_nota'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título da página -->
    <title>PORTARIA - CONTROLE DE CHEGADA</title>

    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background: url('./img/background.png') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            color: #254c90;
        }

        .container {
            max-width: 95vw;
            width: 95vw;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            text-align: center;
            margin: 0 auto;
            padding-top: 0;
        }
        
        .header-portaria {
            display: flex;
            align-items: center;
            justify-content: left;
            background: url('img/header.png') no-repeat center center;
            background-size: contain;
            background-repeat: no-repeat;
            min-height: 100px;
            border-radius: 8px 8px 0 0;
            padding: 18px 24px;
            margin: -20px -20px -10px -20px;
            box-shadow: 0 2px 8px rgba(37,76,144,0.08);
        }

        .header-portaria h2 {
            color: #fff;
            margin: 0;
            font-size: 2rem;
            letter-spacing: 2px;
            font-weight: 700;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.12);
        }

        .logo-topo {
            max-width: 220px;
            width: 100%;
            height: auto;
            display: block;
            margin: 24px auto 20px;
        }
        
        .data-selector {
            margin-top: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .data-selector input[type="date"] {
            padding: 10px;
            border: 1px solid #dbe4f3;
            border-radius: 5px;
            margin-right: 10px;
        }
        
        .data-selector button {
            background-color: #254c90;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
        }
        
        .btn-hoje {
            background-color: #28a745 !important;
            margin-left: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: auto;
        }
        
        table th, table td {
            border: 1px solid #ddd;
            padding: 6px 4px;
            text-align: center;
            font-size: 16px;
            white-space: nowrap;
            color: #000;
        }
        
        table th {
            background-color: #0052a5;
            color: white;
        }
        
        .actions-cell {
            display: flex;
            justify-content: center;
            gap: 5px;
        }
        
        .btn-chegada {
            background-color: #17a2b8;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
        }
        
        .btn-editar {
            background-color: #ffc107;
            color: black;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
        }
        
        .btn-detalhes {
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
        }
        
        .btn-voltar {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 1px 1px 5px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease;
            margin-bottom: 20px;
        }
        
        /* Modal de edição */
        #modalEditar, #modalDetalhes {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.4);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: #fff;
            padding: 32px 28px 24px 28px;
            border-radius: 12px;
            min-width: 340px;
            max-width: 95vw;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            position: relative;
            animation: modalFadeIn 0.2s;
        }
        
        @keyframes modalFadeIn {
            from { transform: translateY(-30px); opacity: 0; }
            to   { transform: translateY(0); opacity: 1; }
        }
        
        .modal-content h3 {
            margin-top: 0;
            color: #254c90;
            font-size: 1.4rem;
            margin-bottom: 18px;
            text-align: center;
            letter-spacing: 1px;
        }
        
        .modal-content form label {
            display: block;
            margin-bottom: 10px;
            color: #254c90;
            font-weight: 500;
            font-size: 1rem;
        }
        
        .modal-content input, .modal-content select {
            width: 100%;
            padding: 7px 10px;
            border: 1px solid #bfc9da;
            border-radius: 5px;
            font-size: 1rem;
            margin-top: 3px;
            margin-bottom: 8px;
        }
        
        .modal-content input:focus {
            border-color: #254c90;
            outline: none;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
        }
        
        .modal-actions button[type="submit"] {
            background: #254c90;
            color: #fff;
            border: none;
            padding: 8px 22px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
        }
        
        .modal-actions button[type="button"] {
            background: #e0e4ea;
            color: #254c90;
            border: none;
            padding: 8px 18px;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        
        .close-modal {
            position: absolute;
            top: 12px;
            right: 18px;
            font-size: 1.3rem;
            color: #254c90;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        
        .status-chegada-nf { background-color: #17a2b8; color: #fff; }
        .status-liberado { background-color: #46da68; color: #fff; }
        .status-recebendo { background-color: #ff9800; color: #000; }
        .status-recebido { background-color: #138d30; color: #fff; }
        .status-em-analise { background-color: #ffc107; color: #000; }
        .status-recusado { background-color: #dc3545; color: #fff; }
        .status-pendente { background-color: #f0f0f0; color: #254c90; }
        
        .notas-fiscais {
            border: 1px solid #dbe4f3;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }
        
        .notas-fiscais h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #254c90;
        }
        
        .notas-fiscais ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        
        .notas-fiscais li {
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        
        /* Botões de navegação para o calendário */
        .nav-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .nav-button {
            background-color: #254c90;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: bold;
        }

        /* Modal para criar novo agendamento */
        #modalNovoAgendamento {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content-novo {
            background: #fff;
            padding: 32px 28px 24px 28px;
            border-radius: 12px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
            position: relative;
            animation: modalFadeIn 0.2s;
        }

        .modal-content-novo h3 {
            margin-top: 0;
            color: #254c90;
            font-size: 1.4rem;
            margin-bottom: 18px;
            text-align: center;
            letter-spacing: 1px;
        }

        .modal-content-novo label {
            display: block;
            margin-bottom: 10px;
            color: #254c90;
            font-weight: 500;
            font-size: 1rem;
        }

        .modal-content-novo input,
        .modal-content-novo select {
            width: 100%;
            padding: 7px 10px;
            border: 1px solid #bfc9da;
            border-radius: 5px;
            font-size: 1rem;
            margin-top: 3px;
            margin-bottom: 8px;
        }

        .modal-content-novo input:focus {
            border-color: #254c90;
            outline: none;
        }

        .modal-actions-novo {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
        }

        .modal-actions-novo button[type="submit"] {
            background: #254c90;
            color: #fff;
            border: none;
            padding: 8px 22px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
        }

        .modal-actions-novo button[type="button"] {
            background: #e0e4ea;
            color: #254c90;
            border: none;
            padding: 8px 18px;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        #modalPainelTempo {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.45);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        #modalPainelTempo.active {
            display: flex !important;
        }
        #modalPainelTempo > div {
            background: #fff;
            border-radius: 18px;
            min-width: 340px;
            max-width: 95vw;
            padding: 36px 32px 28px 32px;
            box-shadow: 0 10px 32px rgba(0,0,0,0.18);
            position: relative;
            text-align: center;
        }
        .flatpickr-calendar {
            z-index: 10001 !important;
        }
    </style>
</head>
<body>
    <div style="width: 100%; max-width: 95vw; display: flex; justify-content: flex-end; gap: 12px; margin: 30px auto 0 auto;">
    <?php if (isset($_SESSION['tipoUsuario']) && $_SESSION['tipoUsuario'] === 'supervisor'): ?>
        <button onclick="window.location.href='pagina-principal.php'" style="padding:10px 18px; background:#ff9800; color:#fff; border:none; border-radius:8px; font-weight:bold; cursor:pointer; box-shadow: 1px 1px 5px rgba(0,0,0,0.12); font-size: 1rem;">
            Voltar para Tela Principal
        </button>
    <?php endif; ?>
    <button onclick="document.getElementById('modalPerfil').style.display='flex'" style="padding: 10px 18px; background-color: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; box-shadow: 1px 1px 5px rgba(0,0,0,0.2); font-size: 1rem;">
        Perfil
    </button>
</div>

    <!-- Modal de Perfil -->
    <div id="modalPerfil" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:2000;">
        <div style="background:#fff; padding:30px; border-radius:10px; width:320px; margin:auto; position:relative;">
            <span style="position:absolute; top:10px; right:15px; cursor:pointer; font-size:1.5rem;" onclick="document.getElementById('modalPerfil').style.display='none'">&times;</span>
            <h3 style="margin-top:0; color:#254c90;">Meu Perfil</h3>
            <form method="POST" action="trocar_senha.php" style="margin-bottom:18px;">
                <label for="nova_senha" style="font-weight:500;">Nova senha:</label>
                <input type="password" name="nova_senha" id="nova_senha" placeholder="Nova senha" required style="width:100%;padding:8px;margin:10px 0 18px 0;border-radius:6px;border:1px solid #ccc;">
                <button type="submit" style="width:100%;background:#254c90;color:#fff;padding:10px 0;border:none;border-radius:6px;font-weight:bold;cursor:pointer;">Salvar Senha</button>
            </form>
            <form action="logout.php" method="POST">
                <button type="submit" style="width:100%;background:#d9534f;color:#fff;padding:10px 0;border:none;border-radius:6px;font-weight:bold;cursor:pointer;">Sair</button>
            </form>
        </div>
    </div>

    <!-- Botão para abrir o painel de tempo -->
        <div style="width:100%; text-align:center; margin-bottom:10px;">
          <button id="btnPainelTempo" style="
            padding: 12px 28px;
            background: #28a745;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(40,167,69,0.08);
            transition: background 0.2s;
          ">
            Painel de Tempo do Dia
          </button>
        </div>
        
    <!-- Modal do Painel de Tempo -->
    <div id="modalPainelTempo" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.45); z-index:9999; align-items:center; justify-content:center;">
      <div style="background:#fff; border-radius:18px; min-width:340px; max-width:95vw; padding:36px 32px 28px 32px; box-shadow:0 10px 32px rgba(0,0,0,0.18); position:relative; text-align:center;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
          <button id="btnPainelPrev" title="Dia anterior" style="background:none; border:none; font-size:2rem; color:#254c90; cursor:pointer; margin-right:8px;">&#60;</button>
          <span id="painelTempoData" style="font-size:1.08em; font-weight:bold; color:#254c90; min-width:110px; display:inline-block; border-bottom:1px dashed #254c90;" title="Navegue pelos dias usando as setas"></span>
          <button id="btnPainelFechar" title="Fechar" style="background:none; border:none; font-size:1.5rem; color:#254c90; cursor:pointer;">&times;</button>
          <button id="btnPainelNext" title="Próximo dia" style="background:none; border:none; font-size:2rem; color:#254c90; cursor:pointer; margin-left:8px;">&#62;</button>
        </div>
        <hr style="border:none; border-top:1px solid #e0e6f0; margin:0 0 14px 0;">
        <div style="margin-bottom:6px; font-size:1.08em; color:#254c90; font-weight:bold;">
          Tempo Usado
        </div>
        <div id="painelTempoDigital" style="font-size:2.1em; font-family:'Courier New', monospace; margin-bottom:6px; letter-spacing:1px; color:#254c90;"></div>
        <div style="font-size:1em; color:#254c90; margin-bottom:12px;">
          Limite Diário: <span id="painelTempoLimite" style="font-weight:bold;">18:00</span>
        </div>
        <div id="painelTempoBar" style="height:13px; width:88%; margin:0 auto 18px auto; background:#e9ecef; border-radius:7px; overflow:hidden;">
          <div id="painelTempoBarFill" style="height:100%; width:0; background:#28a745; transition:width 0.4s;"></div>
        </div>
        <div id="painelTempoStatus" style="font-size:1.15em; font-weight:bold; margin-top:0;"></div>
      </div>
    </div>

    <div class="container">
        <!-- Cabeçalho da página -->
        <div class="header-portaria">
            <h2>CONTROLE DE CHEGADA - PORTARIA</h2>
        </div>
        
        <!-- Filtro de data -->
        <div class="data-selector">
            <form action="" method="GET">
                <input type="date" name="data" value="<?= htmlspecialchars($dataFiltro) ?>" required>
                <button type="submit">FILTRAR</button>
                <button type="button" class="btn-hoje" onclick="window.location.href='portaria.php'">HOJE</button>
            </form>
            
            <!-- Novo botão para criar agendamento -->
            <button 
                type="button" 
                onclick="abrirModalNovoAgendamento()" 
                style="
                    background-color: #28a745;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    padding: 10px 15px;
                    margin-left: 10px;
                    cursor: pointer;
                    font-weight: bold;
                ">
                NOVO AGENDAMENTO
            </button>
        </div>
        
        <div style="overflow-x:auto;">
            <table>
                <!-- Cabeçalhos da tabela -->
                <thead>
                    <tr>
                        <th>FORNECEDOR</th>
                        <th>MOTORISTA</th>
                        <th>PLACA</th>
                        <th>TIPO CAMINHÃO</th>
                        <th>NOTAS FISCAIS</th>
                        <th>STATUS</th>
                        <th>AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Linha de tabela vazia -->
                    <?php if (empty($agendamentos)): ?>
                        <tr>
                            <td colspan="8">NENHUM AGENDAMENTO ENCONTRADO PARA ESTA DATA.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($agendamentos as $agendamento): 
    $status = strtoupper($agendamento['status'] ?? 'PENDENTE');
    $statusClass = 'status-pendente';
    if ($status === 'CHEGADA NF') $statusClass = 'status-chegada-nf';
    elseif ($status === 'LIBERADO') $statusClass = 'status-liberado';
    elseif ($status === 'RECEBENDO') $statusClass = 'status-recebendo';
    elseif ($status === 'RECEBIDO') $statusClass = 'status-recebido';
    elseif ($status === 'EM ANALISE') $statusClass = 'status-em-analise';
    elseif ($status === 'RECUSADO') $statusClass = 'status-recusado';
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
    <td class="<?= $statusClass ?>">
        <?= $status ?>
    </td>
    <td class="actions-cell">
        <?php if ($status !== 'CHEGADA NF'): ?>
            <button class="btn-chegada" onclick="confirmarChegada(<?= $agendamento['id'] ?>)">CONFIRMAR CHEGADA</button>
        <?php endif; ?>
        <button class="btn-editar" onclick="abrirModalEditar(<?= $agendamento['id'] ?>)">EDITAR</button>
        <button class="btn-detalhes" onclick="abrirModalDetalhes(<?= $agendamento['id'] ?>)">DETALHES</button>
    </td>
</tr>
<?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>
    
    <!-- Modal para editar motorista e outros dados -->
    <div id="modalEditar">
        <div class="modal-content" style="width:90%; max-width:900px; overflow-y:auto;">
            <button class="close-modal" onclick="fecharModalEditar()">&times;</button>
            <h3>EDITAR AGENDAMENTO</h3>
            <form id="formEditarPortaria" autocomplete="off">
                <input type="hidden" name="id" id="edit_id">
                <div style="display:flex; flex-wrap:wrap; gap:20px;">
                    <!-- Coluna Esquerda -->
                    <div style="flex:1; min-width:300px;">
                        <h4 style="color:#254c90; margin-bottom:15px;">DADOS DO AGENDAMENTO</h4>
                        
                        <label>DATA DE ENTREGA</label>
                        <input type="date" name="data_agendamento" id="edit_data_agendamento" required>
                        
                        <label>FORNECEDOR</label>
                        <input type="text" name="fornecedor" id="edit_fornecedor" required readonly>
                        
                        <label>TIPO DE CARGA</label>
                        <select name="tipo_carga" id="edit_tipo_carga" required disabled>
                            <option value="">SELECIONE</option>
                            <option value="Batida">BATIDA</option>
                            <option value="Paletizada">PALETIZADA</option>
                        </select>
                        
                        <label>TIPO DE MERCADORIA</label>
                        <input type="text" name="tipo_mercadoria" id="edit_tipo_mercadoria" required readonly>
                        
                        <label>TIPO DE RECEBIMENTO</label>
                        <select name="tipo_recebimento" id="edit_tipo_recebimento" required disabled>
                            <option value="">SELECIONE</option>
                            <option value="Porte Pequeno">PORTE PEQUENO</option>
                            <option value="Porte Médio">PORTE MÉDIO</option>
                            <option value="Porte Grande">PORTE GRANDE</option>
                        </select>
                    </div>
                    
                    <!-- Coluna Direita -->
                    <div style="flex:1; min-width:300px;">
                        <h4 style="color:#254c90; margin-bottom:15px;">DADOS DO TRANSPORTE</h4>
                        
                        <label>NOME DO MOTORISTA</label>
                        <input type="text" name="nome_motorista" id="edit_nome_motorista" required>
                        
                        <label>CPF DO MOTORISTA</label>
                        <input type="text" name="cpf_motorista" id="edit_cpf_motorista" required>
                        
                        <label>NÚMERO DE CONTATO</label>
                        <input type="tel" name="numero_contato" id="edit_numero_contato">
                        
                        <label>PLACA DO VEÍCULO</label>
                        <input type="text" name="placa" id="edit_placa" required>
                        
                        <label>TIPO DE CAMINHÃO</label>
                        <select name="tipo_caminhao" id="edit_tipo_caminhao" required disabled>
                            <option value="">SELECIONE</option>
                            <option value="utilitarios">UTILITÁRIOS</option>
                            <option value="truck">TRUCK</option>
                            <option value="toco">TOCO</option>
                            <option value="carreta">CARRETA</option>
                        </select>
                        
                        <div style="display:flex; gap:15px;">
                            <div style="flex:1;">
                                <label>QTD PALETES</label>
                                <input type="number" name="quantidade_paletes" id="edit_quantidade_paletes" min="0" required readonly>
                            </div>
                            <div style="flex:1;">
                                <label>QTD VOLUMES</label>
                                <input type="number" name="quantidade_volumes" id="edit_quantidade_volumes" min="0" required readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="submit">ATUALIZAR INFORMAÇÕES</button>
                    <button type="button" onclick="fecharModalEditar()">CANCELAR</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para detalhes do agendamento -->
    <div id="modalDetalhes">
        <div class="modal-content">
            <button class="close-modal" onclick="fecharModalDetalhes()">&times;</button>
            <!-- Modal de detalhes -->
            <h3>DETALHES DO AGENDAMENTO</h3>
            <div id="detalhesAgendamento"></div>
            
            <div class="notas-fiscais" id="listaNotasFiscais">
                <h4>Notas Fiscais Relacionadas</h4>
                <ul>
                    <!-- Itens serão preenchidos via JavaScript -->
                </ul>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="fecharModalDetalhes()">FECHAR</button>
            </div>
        </div>
    </div>
    
    <!-- Modal para criar novo agendamento -->
    <div id="modalNovoAgendamento" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000;">
        <div class="modal-content" style="width:90%; max-width:900px; max-height:90vh; overflow-y:auto; background:#f8fafd; border-radius:15px; padding:30px; box-shadow:0 10px 35px rgba(0,0,0,0.25);">
            <button class="close-modal" onclick="fecharModalNovoAgendamento()" style="position:absolute; top:15px; right:20px; font-size:24px; background:none; border:none; color:#254c90; cursor:pointer;">&times;</button>
            
            <h3 style="color:#254c90; font-size:24px; text-align:center; margin-bottom:25px; font-weight:700;">NOVO AGENDAMENTO</h3>
            
            <form id="formNovoAgendamento" action="processar_agendamento.php" method="POST">
                <input type="hidden" name="origem" value="portaria">
                <input type="hidden" name="nome_responsavel" value="<?= htmlspecialchars($usuario) ?>">
                
                <div style="display:flex; flex-wrap:wrap; gap:30px;">
                    <!-- Coluna Esquerda -->
                    <div style="flex:1; min-width:300px; background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                        <h4 style="color:#254c90; margin-bottom:20px; border-bottom:2px solid #eaeef5; padding-bottom:10px; font-size:18px;">DADOS DO AGENDAMENTO</h4>
                        
                        <div style="margin-bottom:22px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600; color:#2c3e50;">DATA DE ENTREGA:</label>
                            <input type="date" name="dataAgendamento" required min="<?= date('Y-m-d') ?>" style="width:100%; padding:10px 12px; border:1px solid #d0d7e5; border-radius:6px; font-size:15px;">
                        </div>
                        
                        <div style="margin-bottom:22px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600; color:#2c3e50;">FORNECEDOR:</label>
                            <input type="text" name="fornecedor" required style="width:100%; padding:10px 12px; border:1px solid #d0d7e5; border-radius:6px; font-size:15px;">
                        </div>
                        
                        <div style="margin-bottom:22px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600; color:#2c3e50;">TIPO DE CARGA:</label>
                            <select name="tipoCarga" required style="width:100%; padding:10px 12px; border:1px solid #d0d7e5; border-radius:6px; font-size:15px; background-color:#fff;">
                                <option value="">SELECIONE</option>
                                <option value="Batida">BATIDA</option>
                                <option value="Paletizada">PALETIZADA</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom:22px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600; color:#2c3e50;">TIPO DE MERCADORIA:</label>
                            <input type="text" name="tipoMercadoria" required style="width:100%; padding:10px 12px; border:1px solid #d0d7e5; border-radius:6px; font-size:15px;">
                        </div>
                        
                        <div style="margin-bottom:22px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600; color:#2c3e50;">TIPO DE RECEBIMENTO:</label>
                            <select name="tipoRecebimento" required style="width:100%; padding:10px 12px; border:1px solid #d0d7e5; border-radius:6px; font-size:15px; background-color:#fff;">
                                <option value="">SELECIONE</option>
                                <option value="Porte Pequeno">PORTE PEQUENO</option>
                                <option value="Porte Médio">PORTE MÉDIO</option>
                                <option value="Porte Grande">PORTE GRANDE</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom:22px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600; color:#2c3e50;">QTD NOTAS FISCAIS:</label>
                            <input type="number" name="quantidadeNotas" min="1" required id="qtdNotasPortaria" style="width:100%; padding:10px 12px; border:1px solid #d0d7e5; border-radius:6px; font-size:15px;">
                        </div>
                    </div>
                    
                    <!-- Coluna Direita -->
                    <div style="flex:1; min-width:300px; background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                        <h4 style="color:#254c90; margin-bottom:20px; border-bottom:2px solid #eaeef5; padding-bottom:10px; font-size:18px;">DADOS DO TRANSPORTE</h4>
                        
                        <div style="margin-bottom:22px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600; color:#2c3e50;">NOME DO MOTORISTA:</label>
                            <input type="text" name="nomeMotorista" required style="width:100%; padding:10px 12px; border:1px solid #d0d7e5; border-radius:6px; font-size:15px;">
                        </div>
                        
                        <div style="margin-bottom:22px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600; color:#2c3e50;">CPF DO MOTORISTA:</label>
                            <input type="text" name="cpfMotorista" required pattern="\d{11}" maxlength="11" style="width:100%; padding:10px 12px; border:1px solid #d0d7e5; border-radius:6px; font-size:15px;">
                        </div>
                        
                        <div style="margin-bottom:22px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600; color:#2c3e50;">NÚMERO DE CONTATO:</label>
                            <input type="tel" name="numeroContato" required style="width:100%; padding:10px 12px; border:1px solid #d0d7e5; border-radius:6px; font-size:15px;">
                        </div>
                        
                        <div style="margin-bottom:22px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600; color:#2c3e50;">TIPO DO CAMINHÃO:</label>
                            <select name="tipoCaminhao" required style="width:100%; padding:10px 12px; border:1px solid #d0d7e5; border-radius:6px; font-size:15px; background-color:#fff;">
                                <option value="">SELECIONE</option>
                                <option value="utilitarios">UTILITÁRIOS</option>
                                <option value="truck">TRUCK</option>
                                <option value="toco">TOCO</option>
                                <option value="carreta">CARRETA</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom:22px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600; color:#2c3e50;">PLACA:</label>
                            <input type="text" name="placa" required pattern="[A-Z]{3}[0-9][A-Z0-9][0-9]{2}" oninput="this.value = this.value.toUpperCase()" style="width:100%; padding:10px 12px; border:1px solid #d0d7e5; border-radius:6px; font-size:15px;">
                        </div>
                        
                        <div style="display:flex; gap:15px;">
                            <div style="flex:1; margin-bottom:22px;">
                                <label style="display:block; margin-bottom:8px; font-weight:600; color:#2c3e50;">QTD PALETES:</label>
                                <input type="number" name="quantidadePaletes" min="0" required style="width:100%; padding:10px 12px; border:1px solid #d0d7e5; border-radius:6px; font-size:15px;">
                            </div>
                            
                            <div style="flex:1; margin-bottom:22px;">
                                <label style="display:block; margin-bottom:8px; font-weight:600; color:#2c3e50;">QTD VOLUMES:</label>
                                <input type="number" name="quantidadeVolumes" min="0" required style="width:100%; padding:10px 12px; border:1px solid #d0d7e5; border-radius:6px; font-size:15px;">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Container para notas fiscais - ocupa toda a largura -->
                <div id="notasContainer" style="margin-top:30px; width:100%; background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.08);"></div>
                
                <div style="text-align:right; margin-top:30px;">
                    <button type="button" onclick="fecharModalNovoAgendamento()" style="background:#e0e4ea; color:#254c90; border:none; padding:12px 24px; border-radius:8px; margin-right:15px; font-weight:bold; font-size:15px; cursor:pointer; transition:all 0.2s ease;">CANCELAR</button>
                    <button type="submit" style="background:#254c90; color:#fff; border:none; padding:12px 28px; border-radius:8px; font-weight:bold; font-size:15px; cursor:pointer; transition:all 0.2s ease; box-shadow:0 4px 6px rgba(37,76,144,0.15);">CONFIRMAR AGENDAMENTO</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
let diasDoMesPainel = [];
let dataAtualPainel = null;
let dadosMesPainel = null;

// Funções utilitárias
function formatarDataBR(dataISO) {
  const [ano, mes, dia] = dataISO.split('-');
  return `${dia}/${mes}/${ano}`;
}
function formatarDigital(minutos) {
  let h = Math.floor(minutos/60);
  let m = minutos%60;
  return `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}`;
}

// Painel de Tempo do Dia
document.getElementById('btnPainelTempo').onclick = function() {
  let dataBase = document.querySelector('input[type="date"][name="data"]').value || new Date().toISOString().slice(0,10);
  let [ano, mes] = dataBase.split('-');
  fetch(`verificar_dias.php?month=${mes}&year=${ano}`)
    .then (r => r.json())
    .then(res => {
      diasDoMesPainel = Object.keys(res.minutosPorDia).sort();
      dadosMesPainel = res;
      if (!diasDoMesPainel.includes(dataBase)) dataBase = diasDoMesPainel[0];
      dataAtualPainel = dataBase;
      abrirPainelTempoModal(dataBase);
    });
};

function abrirPainelTempoModal(data) {
  dataAtualPainel = data;
  document.getElementById('painelTempoData').textContent = formatarDataBR(data);

  let minutosAgendados = dadosMesPainel.minutosPorDia[data] || 0;
  let limite = dadosMesPainel.limiteMinutosDia || 1080;
  let status = '';
  let cor = '';
  let icone = '';

  // Barra de progresso
  let perc = Math.min(100, Math.round((minutosAgendados/limite)*100));
  let bar = document.getElementById('painelTempoBarFill');
  bar.style.width = perc + "%";
  if (minutosAgendados >= limite) bar.style.background = "#dc3545";
  else if (limite - minutosAgendados < 120) bar.style.background = "#fd7e14";
  else bar.style.background = "#28a745";

  // Status visual
  const tempoAvisoProximoCheio = 120; // minutos (2 horas)
  if (minutosAgendados >= limite) {
    status = 'Cheio';
    cor = '#dc3545';
    icone = '⏰';
  } else if ((limite - minutosAgendados) < tempoAvisoProximoCheio) {
    status = 'Parcial';
    cor = '#fd7e14';
    icone = '⚠️';
  } else {
    status = 'Livre';
    cor = '#28a745';
    icone = '🟢';
  }

  // Mostra só o tempo usado
  document.getElementById('painelTempoDigital').innerHTML = formatarDigital(minutosAgendados);

  document.getElementById('painelTempoStatus').innerHTML = `<span style="color:${cor}; font-size:1.3em; display:flex; align-items:center; justify-content:center; gap:8px;">
    <span style="font-size:1.6em;">${icone}</span> <span>${status}</span>
  </span>`;

  document.getElementById('modalPainelTempo').classList.add('active');

  // Navegação entre dias
  document.getElementById('btnPainelPrev').onclick = function() {
    let idx = diasDoMesPainel.indexOf(dataAtualPainel);
    if (idx > 0) abrirPainelTempoModal(diasDoMesPainel[idx-1]);
  };
  document.getElementById('btnPainelNext').onclick = function() {
    let idx = diasDoMesPainel.indexOf(dataAtualPainel);
    if (idx < diasDoMesPainel.length-1) abrirPainelTempoModal(diasDoMesPainel[idx+1]);
  };

  document.getElementById('btnPainelFechar').onclick = function() {
    document.getElementById('modalPainelTempo').classList.remove('active');
  };
}

// Função para confirmar chegada
function confirmarChegada(id) {
  if (confirm('Confirmar chegada deste agendamento?')) {
    fetch('confirmar_chegada.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({id: id})
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert('Chegada confirmada!');
        location.reload();
      } else {
        alert(res.message || 'Erro ao confirmar chegada.');
      }
    })
    .catch(() => alert('Erro de comunicação com o servidor.'));
  }
}

// Função para abrir modal de edição
function abrirModalEditar(id) {
  fetch('editar_portaria.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'id=' + encodeURIComponent(id)
  })
  .then(r => r.json())
  .then(res => {
    if (res.success === false && res.message) {
      alert(res.message);
      return;
    }
    // Preenche os campos do modal com os dados recebidos
    const ag = res;
    document.getElementById('edit_id').value = ag.id;
    document.getElementById('edit_data_agendamento').value = ag.data_agendamento;
    document.getElementById('edit_fornecedor').value = ag.fornecedor;
    document.getElementById('edit_tipo_carga').value = ag.tipo_carga;
    document.getElementById('edit_tipo_mercadoria').value = ag.tipo_mercadoria;
    document.getElementById('edit_tipo_recebimento').value = ag.tipo_recebimento;
    document.getElementById('edit_nome_motorista').value = ag.nome_motorista;
    document.getElementById('edit_cpf_motorista').value = ag.cpf_motorista;
    document.getElementById('edit_numero_contato').value = ag.numero_contato;
    document.getElementById('edit_placa').value = ag.placa;
    document.getElementById('edit_tipo_caminhao').value = ag.tipo_caminhao;
    document.getElementById('edit_quantidade_paletes').value = ag.quantidade_paletes;
    document.getElementById('edit_quantidade_volumes').value = ag.quantidade_volumes;
    document.getElementById('modalEditar').style.display = 'flex';
  })
  .catch(() => alert('Erro ao buscar dados do agendamento.'));
}

// Envio do formulário de edição
document.getElementById('formEditarPortaria').onsubmit = function(e) {
  e.preventDefault();
  const form = e.target;
  const dados = new FormData(form);
  fetch('editar_portaria.php', {
    method: 'POST',
    body: dados
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      alert('Agendamento atualizado!');
      location.reload();
    } else {
      alert(res.message || 'Erro ao atualizar.');
    }
  })
  .catch(() => alert('Erro ao atualizar.'));
};

// Fechar modal editar
function fecharModalEditar() {
  document.getElementById('modalEditar').style.display = 'none';
}

// Função para abrir modal de detalhes
function abrirModalDetalhes(id) {
  // Busca os dados do agendamento
  fetch('editar_portaria.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'id=' + encodeURIComponent(id)
  })
  .then(r => r.json())
  .then(ag => {
    let html = `
      <b>Data:</b> ${ag.data_agendamento}<br>
      <b>Fornecedor:</b> ${ag.fornecedor}<br>
      <b>Tipo de Carga:</b> ${ag.tipo_carga}<br>
      <b>Tipo de Mercadoria:</b> ${ag.tipo_mercadoria}<br>
      <b>Tipo de Recebimento:</b> ${ag.tipo_recebimento}<br>
      <b>Motorista:</b> ${ag.nome_motorista}<br>
      <b>CPF Motorista:</b> ${ag.cpf_motorista}<br>
      <b>Contato:</b> ${ag.numero_contato}<br>
      <b>Placa:</b> ${ag.placa}<br>
      <b>Tipo Caminhão:</b> ${ag.tipo_caminhao}<br>
      <b>Qtd Paletes:</b> ${ag.quantidade_paletes}<br>
      <b>Qtd Volumes:</b> ${ag.quantidade_volumes}<br>
    `;
    document.getElementById('detalhesAgendamento').innerHTML = html;

    // Busca notas fiscais
    fetch('get_notas_fiscais.php?agendamento_id=' + encodeURIComponent(id))
      .then(r => r.json())
      .then(res => {
        let ul = document.querySelector('#listaNotasFiscais ul');
        ul.innerHTML = '';
        if (res.length) {
          res.forEach(nota => {
            let li = document.createElement('li');
            li.textContent = nota.numero_nota;
            ul.appendChild(li);
          });
        } else {
          let li = document.createElement('li');
          li.textContent = 'Nenhuma nota fiscal cadastrada.';
          ul.appendChild(li);
        }
      });
    document.getElementById('modalDetalhes').style.display = 'flex';
  })
  .catch(() => alert('Erro ao buscar detalhes.'));
}

// Fechar modal detalhes
function fecharModalDetalhes() {
  document.getElementById('modalDetalhes').style.display = 'none';
}

// Fechar modal novo agendamento
function fecharModalNovoAgendamento() {
  document.getElementById('modalNovoAgendamento').style.display = 'none';
}

// Abrir modal novo agendamento
function abrirModalNovoAgendamento() {
  document.getElementById('modalNovoAgendamento').style.display = 'flex';
}

// Gera campos de notas fiscais conforme a quantidade digitada
document.getElementById('qtdNotasPortaria').addEventListener('input', function() {
    let qtd = parseInt(this.value) || 0;
    let container = document.getElementById('notasContainer');
    container.innerHTML = '';
    if (qtd > 0) {
        let html = '<label style="font-weight:600; color:#2c3e50;">Notas Fiscais:</label>';
        for (let i = 1; i <= qtd; i++) {
            html += `<input type="text" name="notasFiscais[]" placeholder="Nota Fiscal #${i}" required style="width:100%; padding:10px 12px; border:1px solid #d0d7e5; border-radius:6px; font-size:15px; margin-bottom:10px;">`;
        }
        container.innerHTML = html;
    }
});

// Ao enviar o formulário, transforma os campos em um JSON para o backend
document.getElementById('formNovoAgendamento').addEventListener('submit', function(e) {
    e.preventDefault(); // Impede o envio padrão do formulário

    let notas = Array.from(document.querySelectorAll('input[name="notasFiscais[]"]')).map(i => i.value.trim()).filter(Boolean);
    let inputJson = document.createElement('input');
    inputJson.type = 'hidden';
    inputJson.name = 'notasFiscaisJSON';
    inputJson.value = JSON.stringify(notas);
    this.appendChild(inputJson);

    let form = this;
    let formData = new FormData(form);

    fetch('processar_agendamento.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('Agendamento realizado com sucesso!');
            fecharModalNovoAgendamento();
            form.reset();
            document.getElementById('notasContainer').innerHTML = '';
            location.reload(); // Recarrega a página para mostrar o novo agendamento
        } else {
            alert(res.message || 'Erro ao realizar agendamento.');
        }
    })
    .catch(() => alert('Erro de comunicação com o servidor.'));
});

// Recarrega a página a cada 15 minutos para manter a sessão ativa
setInterval(function() {
    location.reload();
}, 900000); // 900.000 ms = 15 minutos

</script>
</body>
</html>