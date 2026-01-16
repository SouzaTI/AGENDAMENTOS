<?php
/**
 * visao-recepcao.php
 *
 * Página de visualização da recepção de motoristas agendados para recebimento.
 * 
 * FUNCIONALIDADE:
 * - Exibe uma tabela com os motoristas agendados para o dia selecionado (ou o dia atual por padrão).
 * - Mostra informações como data, nome do motorista, tipo de recebimento, status, local e senha.
 * - Destaca visualmente os motoristas conforme o status: disponíveis, recebendo ou já recebidos.
 * - Realiza polling para detectar chamadas de senha e exibe um overlay de destaque quando um motorista é chamado.
 * - Atualiza automaticamente a página a cada 10 segundos, exceto quando há uma chamada ativa.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Busca os agendamentos do banco de dados filtrando pela data e status relevantes.
 * - Separa os agendamentos em três listas: disponíveis, recebendo e conferidos (recebidos).
 * - Renderiza a tabela HTML agrupando por status, com estilos visuais distintos.
 * - Usa JavaScript para:
 *   - Atualizar a página periodicamente.
 *   - Fazer polling a cada 2 segundos para verificar se há uma chamada de senha.
 *   - Exibir um overlay animado com os dados do motorista chamado, interrompendo o reload automático durante a exibição.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Requer o arquivo verificar_chamada.php para o polling de chamadas.
 * - Utiliza arquivos de imagem para background e header.
 */

require 'db.php';

$filtro_data = $_GET['filtro_data'] ?? date('Y-m-d');

$sql = "SELECT data_agendamento, nome_motorista, tipo_recebimento, status, local_recebimento, senha, placa
        FROM agendamentos
        WHERE data_agendamento = :filtro_data
          AND status IN ('Agendado', 'Liberado', 'Em Analise', 'Recebendo', 'Recebido', 'Recusado')
        ORDER BY FIELD(tipo_recebimento, 'Porte Pequeno', 'Porte Médio', 'Porte Grande'), senha ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':filtro_data' => $filtro_data]);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$senhas_disponiveis = [];
$senhas_recebendo = [];
$senhas_conferidas = [];

foreach ($agendamentos as $agendamento) {
    if ($agendamento['status'] == 'Recebendo') {
        $senhas_recebendo[] = $agendamento;
    } elseif ($agendamento['status'] == 'Recebido') {
        $senhas_conferidas[] = $agendamento;
    } else {
        $senhas_disponiveis[] = $agendamento;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recepção - Motoristas</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
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
            position: relative; /* Adicione esta linha */
            max-width: 95vw;
            width: 95vw;
            min-width: 600px;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            text-align: center;
            margin: 0 auto;
            margin-top: 0;
            padding-top: 0;
        }
        h2 { text-align: center; color: #254c90; margin-bottom: 20px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px 4px;
            text-align: center;
            font-size: 18px;
            white-space: nowrap;
        }
        th {
            background-color: #0052a5;
            color: white;
        }
        .status-liberado { background: #17a2b8; color: #fff; font-weight: bold; }
        .status-recebendo { background: #ffc107; color: #000; font-weight: bold; }
        .status-recebido { background: #dc3545; color: #fff; font-weight: bold; }
        .status-em-analise { background: #ff9800; color: #fff; font-weight: bold; }
        .status-areceber { background: #28a745; color: #fff; font-weight: bold; }
        .senha-chamada {
            animation: pisca 1s infinite alternate;
            background: #28a745 !important;
            color: #fff !important;
            font-size: 1.3em;
            font-weight: bold;
        }
        .senha-normal {
            background: #eee !important;
            color: #333 !important;
            font-size: 1.1em;
            font-weight: normal;
        }
        .senha-apagada {
            background: #ccc !important;
            color: #888 !important;
            font-size: 1em;
            font-weight: normal;
            text-decoration: line-through;
            opacity: 0.6;
        }
        .linha-conferida {
            opacity: 0.5;
        }
        @keyframes pisca {
            from { box-shadow: 0 0 10px #28a745; }
            to   { box-shadow: 0 0 30px #28a745; }
        }
        /* Header igual ao visao-recebimento */
        .header-agendamentos {
            display: flex;
            align-items: center;
            justify-content: left;
            background: url('img/header.png') no-repeat center center;
            background-size: contain;
            background-repeat: no-repeat;
            min-height: 100px;
            border-radius: 8px 8px 0 0;
            padding: 18px 24px;
            margin: -40px -20px -10px -20px;
            box-shadow: 0 2px 8px rgba(37,76,144,0.08);
        }
        .header-agendamentos .header-logo {
            height: 48px;
            margin-right: 24px;
        }
        .header-agendamentos h2 {
            color: #fff;
            margin: 0;
            font-size: 2rem;
            letter-spacing: 2px;
            font-weight: 700;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.12);
            flex: 1;
            text-align: left;
        }
        @media (max-width: 1200px) {
    .header-agendamentos {
        margin: 0 -10px -10px -10px; /* Zera a margem superior */
        min-height: 70px;
        padding: 10px 10px;
        background-size: contain;
    }
    .header-agendamentos h2 {
        font-size: 1.3rem;
    }
    .header-agendamentos .header-logo {
        height: 36px;
        margin-right: 12px;
    }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header-agendamentos">
        <h2>Recepção - Motoristas</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Nome do Motorista</th>
                <th>Tipo de Recebimento</th>
                <th>Status</th>
                <th>Local de Recebimento</th>
                <th>Senha de Espera</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($senhas_disponiveis as $agendamento): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?></td>
                <td><?= htmlspecialchars($agendamento['nome_motorista']) ?></td>
                <td><?= htmlspecialchars($agendamento['tipo_recebimento']) ?></td>
                <td class="<?=
                    $agendamento['status'] == 'Recebendo' ? 'status-recebendo' :
                    ($agendamento['status'] == 'Recebido' ? 'status-recebido' :
                    ($agendamento['status'] == 'Em Analise' ? 'status-em-analise' :
                    ($agendamento['status'] == 'Liberado' ? 'status-liberado' :
                    ($agendamento['status'] == 'Recusado' ? 'status-recusado' : 'status-areceber'))))
                ?>">
                    <?= htmlspecialchars($agendamento['status']) ?>
                </td>
                <td><?= htmlspecialchars($agendamento['local_recebimento'] ?? '-') ?></td>
                <td class="senha-chamada">
                    <?= htmlspecialchars($agendamento['senha']) ?>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php foreach ($senhas_recebendo as $agendamento): ?>
            <tr class="linha-recebendo">
                <td><?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?></td>
                <td><?= htmlspecialchars($agendamento['nome_motorista']) ?></td>
                <td><?= htmlspecialchars($agendamento['tipo_recebimento']) ?></td>
                <td class="<?=
                    $agendamento['status'] == 'Recebendo' ? 'status-recebendo' :
                    ($agendamento['status'] == 'Recebido' ? 'status-recebido' :
                    ($agendamento['status'] == 'Em Analise' ? 'status-em-analise' :
                    ($agendamento['status'] == 'Liberado' ? 'status-liberado' : 'status-areceber')))
                ?>">
                    <?= htmlspecialchars($agendamento['status']) ?>
                </td>
                <td><?= htmlspecialchars($agendamento['local_recebimento'] ?? '-') ?></td>
                <td class="senha-normal">
                    <?= htmlspecialchars($agendamento['senha']) ?>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php foreach ($senhas_conferidas as $agendamento): ?>
            <tr class="linha-conferida">
                <td><?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?></td>
                <td><?= htmlspecialchars($agendamento['nome_motorista']) ?></td>
                <td><?= htmlspecialchars($agendamento['tipo_recebimento']) ?></td>
                <td class="<?=
                    $agendamento['status'] == 'Recebendo' ? 'status-recebendo' :
                    ($agendamento['status'] == 'Recebido' ? 'status-recebido' :
                    ($agendamento['status'] == 'Em Analise' ? 'status-em-analise' :
                    ($agendamento['status'] == 'Liberado' ? 'status-liberado' : 'status-areceber')))
                ?>">
                    <?= htmlspecialchars($agendamento['status']) ?>
                </td>
                <td><?= htmlspecialchars($agendamento['local_recebimento'] ?? '-') ?></td>
                <td class="senha-apagada">
                    <?= htmlspecialchars($agendamento['senha']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div id="chamada-overlay-root"></div>
<script>
let reloadAtivo = true;

// Função de reload automático a cada 10 segundos
function reloadAuto() {
    if (reloadAtivo) {
        location.reload(true);
    }
}
let reloadInterval = setInterval(reloadAuto, 10000);

// Polling para verificar chamada
setInterval(() => {
    fetch('verificar_chamada.php')
        .then(r => r.json())
        .then (data => {
            if (data.chamar) {
                // Pausa o reload automático
                reloadAtivo = false;
                clearInterval(reloadInterval);
                exibirOverlayChamada(data.senha, data.motorista, data.placa, data.local);
            }
        });
}, 2000);

function exibirOverlayChamada(senha, motorista, placa, local) {
    let overlayHTML = `
        <div id="chamada-overlay" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(37,76,144,0.97); color:#fff; z-index:9999; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:0; margin:0;">
            <div style="font-size:5vw; font-weight:bold; text-align:center; margin-bottom:2vw; line-height:1.15;">
                <span style="font-size:7vw; color:#ff6600; display:block; margin-bottom:2vw;">SENHA: ${senha}</span>
                <span style="font-size:4vw;">MOTORISTA:</span> <span style="color:#fff; font-size:4vw;">${motorista}</span><br>
                <span style="font-size:4vw;">PLACA:</span> <span style="color:#fff; font-size:4vw;">${placa}</span><br><br>
                <span style="color:#fff; font-size:4vw;">POR FAVOR, DIRIJA-SE AO LOCAL</span><br>
                <span style="color:#ff6600; font-size:7vw; display:block; margin-top:2vw;">${local}</span>
            </div>
        </div>
    `;
    // Primeira exibição (5 segundos)
    document.getElementById('chamada-overlay-root').innerHTML = overlayHTML;
    setTimeout(() => {
        // Esconde por 0.5s
        document.getElementById('chamada-overlay-root').innerHTML = '';
        setTimeout(() => {
            // Segunda exibição (5 segundos)
            document.getElementById('chamada-overlay-root').innerHTML = overlayHTML;
            setTimeout(() => {
                document.getElementById('chamada-overlay-root').innerHTML = '';
                // Libera o reload automático após o overlay piscar duas vezes
                reloadAtivo = true;
                reloadInterval = setInterval(reloadAuto, 10000);
            }, 7000);
        }, 500);
    }, 7000);
}
</script>
</body>
</html>