<?php
/**
 * painel-senhas.php
 *
 * Página de exibição do painel de senhas e status dos agendamentos do recebimento.
 *
 * FUNCIONALIDADE:
 * - Exibe uma tabela com todos os agendamentos do dia selecionado (ou do dia atual por padrão).
 * - Mostra informações como data, fornecedor, placa, motorista, senha, status, horários e tempo de recebimento.
 * - Permite filtrar visualmente por colunas (data, fornecedor, placa, motorista).
 * - Permite alterar o status do agendamento diretamente na tabela (exceto para "Recebendo" e "Recebido").
 * - Atualiza automaticamente a página a cada 5 segundos, exceto quando o usuário está interagindo com campos editáveis ou modais.
 * - Possui modal para registrar conferência de recebimento.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Busca os agendamentos do banco de dados filtrando pela data e status relevantes.
 * - Busca os horários de conferência para calcular o tempo de recebimento.
 * - Renderiza a tabela HTML com filtros dinâmicos por coluna.
 * - Usa JavaScript para:
 *   - Atualizar status via AJAX.
 *   - Abrir modal de conferência e registrar dados via AJAX.
 *   - Filtrar linhas da tabela por coluna.
 *   - Controlar o reload automático da página.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Requer scripts PHP auxiliares: atualizar_status.php, registrar_conferencia.php, atualizar_local_recebimento.php, chamar_motorista.php.
 * - Utiliza arquivos de imagem para background e header.
 */

session_start();
require 'db.php';

$filtro_data = $_GET['filtro_data'] ?? date('Y-m-d');

$sql = "SELECT id, data_agendamento, tipo_caminhao, tipo_carga, tipo_mercadoria, fornecedor, placa, status, nome_motorista, tipo_recebimento, senha, data_liberado, data_em_analise, data_recebendo, tempo, chegada_nf
        FROM agendamentos 
        WHERE status IN ('Chegada NF', 'Liberado', 'Em Analise', 'Recebendo', 'Recebido')
          AND data_agendamento = :filtro_data
        ORDER BY FIELD(tipo_recebimento, 'Porte Pequeno', 'Porte Médio', 'Porte Grande'), senha ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':filtro_data' => $filtro_data]);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ids = array_column($agendamentos, 'id');
$datasConferencia = [];
if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $sqlConf = "SELECT agendamento_id, data_conferencia FROM conferencias_recebimento WHERE agendamento_id IN ($in)";
    $stmtConf = $pdo->prepare($sqlConf);
    $stmtConf->execute($ids);
    foreach ($stmtConf->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $datasConferencia[$row['agendamento_id']] = $row['data_conferencia'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recebimento</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <style>
        html, body {
    margin: 0;
    padding: 0;
}

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
            text-transform: uppercase;
        }

        .container {
            position: relative; /* Adicione esta linha */
            max-width: 95vw;
            width: 95vw;
            min-width: 900px;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            text-align: center;
            margin: 0 auto;
            margin-top: 70px;
            padding-top: 0;
        }

        h2 { text-align: center; color: #254c90; margin-bottom: 20px; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: auto; /* Deixe o navegador ajustar as colunas */
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 6px 4px;
            text-align: center;
            font-size: 16px;      /* Aumenta a fonte*/
            white-space: nowrap;
            color: #000;          /* Fonte preta */
        }

     

/* 1ª coluna: Data */
th:nth-child(1), td:nth-child(1) {
    width: 20px;
    min-width: 20px;
    max-width: 25px;
}

/* 2ª coluna: Fornecedor */
th:nth-child(2), td:nth-child(2) {
    width: 100px;
    min-width: 70px;
    max-width: 150px;
}

/* 3ª coluna: Placa */
th:nth-child(3), td:nth-child(3) {
    width: 30px;
    min-width: 30px;
    max-width: 20px;
}

/* 4ª coluna: Nome Motorista */
th:nth-child(4), td:nth-child(4) {
    width: 80px;
    min-width: 70px;
    max-width: 100px;
}

/* 5ª coluna: Senha */
th:nth-child(5), td:nth-child(5) {
    width: 25px;
    min-width: 25px;
    max-width: 25px;
}

/* 6ª coluna: Status */
th:nth-child(6), td:nth-child(6) {
    width: 40px;
    min-width: 55px;
    max-width: 45px;
}

/* 7ª coluna: Chegada NF */
th:nth-child(7), td:nth-child(7) {
    width: 40px;
    min-width: 30px;
    max-width: 60px;
}

/* 8ª coluna: Início */
th:nth-child(8), td:nth-child(8) {
    width: 20px;
    min-width: 30px;
    max-width: 40px;
}

/* 9ª coluna: HR Fim */
th:nth-child(9), td:nth-child(9) {
    width: 20px;
    min-width: 30px;
    max-width: 40px;
}

/* 10ª coluna: Tempo */
th:nth-child(10), td:nth-child(10) {
    width: 20px;
    min-width: 30px;
    max-width: 40px;
}
        table th {
            background-color: #0052a5;
            color: white;
        }

        /* Se quiser, defina só larguras mínimas para algumas colunas */
        table th, table td {
            min-width: 80px;
        }


        .status-select {
            padding: 5px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            width: 100%;
            background: transparent;
            text-transform: uppercase; /* Adicione esta linha */
        }

        .status-liberado { background-color: rgb(70, 218, 104); color: #fff; }
        .status-recebendo { background-color: #ff9800; color: #000; }
        .status-recebido { background-color:rgb(19, 141, 48); color: #fff; }
        .status-em-analise { background-color: #ffc107; color: #fff; }
        .status-chegada-nf { background-color: #17a2b8; color: #fff; }

        /* Estilos para o modal */
        #modalConferencia {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.4);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            min-width: 300px;
        }
        .status-recebido select:disabled {
            color: #fff !important;
            background:rgb(19, 141, 48) !important;
            opacity: 1; /* Remove o efeito "apagado" */
        }

        .header-agendamentos {
            display: flex;
            align-items: center;
            justify-content: left;
            background: url('img/header.png') no-repeat center center;
            background-size: contain;      /* Mostra a imagem inteira */
            background-repeat: no-repeat;
            min-height: 100px;              /* Ajuste a altura conforme necessário */
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
            text-align: left; /*alinha o texto à esquerda*/
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

        .filtro-icone {
            display: none;
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 16px;
            color: #fff;
            opacity: 0.7;
            z-index: 2;
        }
        th:hover .filtro-icone {
            display: inline;
        }
        .filtro-input {
            display: none;
            position: absolute;
            right: 6px;
            top: 120%;
            z-index: 10;
            padding: 3px 8px;
            border-radius: 5px;
            border: 1px solid #bfc9da;
            font-size: 0.95rem;
            background: #fff;
            color: #254c90;
            min-width: 120px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        th.filtro-ativo .filtro-input {
            display: block;
        }

        .filtro-input {
            display: none;
            width: 100%;
            padding: 6px;
            margin-top: 4px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background: #f9f9f9;
        }

        .filtro-input.ativo {
            display: block;
        }
        th {
    position: relative;
}
        /* Senha em destaque igual ao visao-recebimento.php */
.senha-destaque {
    background: rgb(238, 234, 186);   /* Amarelo claro */
    color: rgb(255, 115, 0);          /* Laranja forte */
    font-weight: bold;
    font-size: 1.3em;
    letter-spacing: 1px;
    border-radius: 0;
    text-align: center;
    box-shadow: none;
}
    </style>
    <script>
    function atualizarStatus(select, agendamentoId) {
        const cell = select.parentElement;
        cell.className = '';
        aplicarEstiloStatus(cell, select.value);

        fetch('atualizar_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: agendamentoId, status: select.value })
        })
        .then(response => response.json())
        .then (data => {
            if (!data.success) {
                alert('Erro ao atualizar o status.');
            }
        })
        .catch(error => {
            alert('Erro na conexão com o servidor.');
            console.error(error);
        });
    }

    function aplicarEstiloStatus(cell, status) {
        cell.className = '';
        if (status === 'Recebendo') cell.classList.add('status-recebendo');
        else if (status === 'Recebido') cell.classList.add('status-recebido');
        else if (status === 'Liberado') cell.classList.add('status-liberado');
        else if (status === 'Em Analise') cell.classList.add('status-em-analise');
        else if (status === 'Chegada NF') cell.classList.add('status-chegada-nf');
    }

    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".status-select").forEach(select => {
            const cell = select.parentElement;
            aplicarEstiloStatus(cell, select.value);
        });
    });

    function abrirModal(agendamentoId) {
        pausarReload();
        document.getElementById('agendamento_id').value = agendamentoId;
        document.getElementById('modalConferencia').style.display = 'flex';
    }

    function fecharModal() {
        retomarReload();
        document.getElementById('modalConferencia').style.display = 'none';
    }

    document.addEventListener("DOMContentLoaded", function() {
        var form = document.getElementById('formConferencia');
        if (form) {
            form.onsubmit = function(e) {
                e.preventDefault();
                fetch('registrar_conferencia.php', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(r => r.json())
                .then (data => {
                    if(data.success) {
                        alert('Conferência registrada!');
                        fecharModal();
                        location.reload();
                    } else {
                        alert(data.message || 'Erro ao registrar.');
                    }
                });
            };
        }
    });

    function abrirFiltroColuna(event, col) {
        const span = event.currentTarget;
        const select = span.nextElementSibling;

        // Fecha outros filtros abertos
        document.querySelectorAll('.filtro-input').forEach(s => {
            if (s !== select) s.classList.remove('ativo');
        });

        // Alterna a visibilidade do filtro da coluna clicada
        select.classList.toggle('ativo');
    }

    function filtrarColunaSelect(select) {
        const coluna = select.getAttribute('data-col');
        const valor = select.value.toLowerCase();

        document.querySelectorAll('table tbody tr').forEach(linha => {
            const celula = linha.querySelector(`td:nth-child(${parseInt(coluna) + 1})`);
            if (celula) {
                const textoCelula = celula.textContent.toLowerCase();
                linha.style.display = textoCelula.includes(valor) ? '' : 'none';
            }
        });
    }

    function atualizarLocalRecebimento(id, local) {
        fetch('atualizar_local_recebimento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, local: local })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Erro ao atualizar o local de recebimento.');
            }
        })
        .catch(error => {
            alert('Erro na conexão com o servidor.');
            console.error(error);
        });
    }

let reloadAtivo = true;

function pausarReload() {
    reloadAtivo = false;
}

function retomarReload() {
    reloadAtivo = true;
}

// Intervalo de reload
setInterval(function() {
    if (reloadAtivo) location.reload();
}, 5000);

document.addEventListener("DOMContentLoaded", function() {
    // Pausa reload ao focar nos selects de status ou local
    document.querySelectorAll('.status-select, [onchange^="atualizarLocalRecebimento"]').forEach(function(select) {
        select.addEventListener('focus', pausarReload);
        select.addEventListener('blur', retomarReload);
    });
});

    function chamarSenhaServidor(id) {
        fetch('chamar_motorista.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
    }
    </script>
</head>
<body>

<?php if (isset($_SESSION['tipoUsuario']) && $_SESSION['tipoUsuario'] === 'supervisor'): ?>
<div style="position: absolute; top: 20px; right: 20px; display: flex; gap: 12px; z-index: 10;">
    <button onclick="window.location.href='pagina-principal.php'" style="padding:10px 18px; background:#ff9800; color:#fff; border:none; border-radius:8px; font-weight:bold; cursor:pointer; box-shadow: 1px 1px 5px rgba(0,0,0,0.12); font-size: 1rem;">
        Voltar para Tela Principal
    </button>
    <button onclick="document.getElementById('modalPerfil').style.display='flex'" style="padding: 10px 18px; background-color: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; box-shadow: 1px 1px 5px rgba(0,0,0,0.2); font-size: 1rem;">
        Perfil
    </button>
</div>
<?php endif; ?>

<div class="container">
    <div class="header-agendamentos">
        <h2>Recebimento - Senha de Espera</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>
                    Data
                    <span class="filtro-icone" onclick="abrirFiltroColuna(event, 0)">&#128269;</span>
                    <select class="filtro-input" data-col="0" onchange="filtrarColunaSelect(this)">
                        <option value="">Todos</option>
                        <?php
                        $datas = array_unique(array_map(function($a) {
                            return date('d/m/Y', strtotime($a['data_agendamento']));
                        }, $agendamentos));
                        foreach ($datas as $data) {
                            echo '<option value="'.htmlspecialchars($data).'">'.htmlspecialchars($data).'</option>';
                        }
                        ?>
                    </select>
                </th>               
            
                <th>
                    Fornecedor
                    <span class="filtro-icone" onclick="abrirFiltroColuna(event, 4)">&#128269;</span>
                    <select class="filtro-input" data-col="4" onchange="filtrarColunaSelect(this)">
                        <option value="">Todos</option>
                        <?php
                        $fornecedores = array_unique(array_column($agendamentos, 'fornecedor'));
                        foreach ($fornecedores as $fornecedor) {
                            echo '<option value="'.htmlspecialchars($fornecedor).'">'.htmlspecialchars($fornecedor).'</option>';
                        }
                        ?>
                    </select>
                </th>
                <th>
                    Placa
                    <span class="filtro-icone" onclick="abrirFiltroColuna(event, 5)">&#128269;</span>
                    <select class="filtro-input" data-col="5" onchange="filtrarColunaSelect(this)">
                        <option value="">Todos</option>
                        <?php
                        $placas = array_unique(array_column($agendamentos, 'placa'));
                        foreach ($placas as $placa) {
                            echo '<option value="'.htmlspecialchars($placa).'">'.htmlspecialchars($placa).'</option>';
                        }
                        ?>
                    </select>
                </th>
                <th>
                    Nome Motorista
                    <span class="filtro-icone" onclick="abrirFiltroColuna(event, 6)">&#128269;</span>
                    <select class="filtro-input" data-col="6" onchange="filtrarColunaSelect(this)">
                        <option value="">Todos</option>
                        <?php
                        $motoristas = array_unique(array_column($agendamentos, 'nome_motorista'));
                        foreach ($motoristas as $motorista) {
                            echo '<option value="'.htmlspecialchars($motorista).'">'.htmlspecialchars($motorista).'</option>';
                        }
                        ?>
                    </select>
                </th>
                <th>Senha</th>
                <th>Status</th>
                <th>Chegada NF</th>
                <th>HR Inicio</th>
                <th>HR Fim</th>
                <th>Tempo</th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($agendamentos as $agendamento): ?>
    <tr>
        <td><?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?></td>      
        <td><?= htmlspecialchars($agendamento['fornecedor']) ?></td>
        <td><?= htmlspecialchars($agendamento['placa']) ?></td>
        <td><?= htmlspecialchars($agendamento['nome_motorista']) ?></td>
        <td class="senha-destaque"><?= htmlspecialchars($agendamento['senha']) ?></td>
        <td class="<?=
            $agendamento['status'] == 'Recebendo' ? 'status-recebendo' :
            ($agendamento['status'] == 'Recebido' ? 'status-recebido' :
            ($agendamento['status'] == 'Em Analise' ? 'status-em-analise' :
            ($agendamento['status'] == 'Chegada NF' ? 'status-chegada-nf' : 'status-liberado')))
        ?>">
            <select class="status-select" onchange="atualizarStatus(this, <?= $agendamento['id'] ?>)" 
                <?= ($agendamento['status'] == 'Recebendo' || $agendamento['status'] == 'Recebido') ? 'disabled' : '' ?>>
                <option value="Liberado" <?= $agendamento['status'] == 'Liberado' ? 'selected' : '' ?> disabled>Liberado</option>
                <option value="Em Analise" <?= $agendamento['status'] == 'Em Analise' ? 'selected' : '' ?> disabled>Em Analise</option>
                <option value="Recebendo" <?= $agendamento['status'] == 'Recebendo' ? 'selected' : '' ?>>Recebendo</option>
                <option value="Recebido" <?= $agendamento['status'] == 'Recebido' ? 'selected' : '' ?> disabled>Recebido</option>
                <option value="Chegada NF" <?= $agendamento['status'] == 'Chegada NF' ? 'selected' : '' ?> disabled>Chegada NF</option>
            </select>
        </td>        
        <td>
            <?php
            echo !empty($agendamento['chegada_nf']) ? date('H:i', strtotime($agendamento['chegada_nf'])) : '-';
            ?>
        </td>        
        <td>
            <?php
            if (!empty($agendamento['data_liberado'])) {
                echo date('H:i', strtotime($agendamento['data_liberado']));
            } else {
                echo '-';
            }
            ?>
        </td>  
        <td>
            <?php
            $dataConf = $datasConferencia[$agendamento['id']] ?? '';
            echo $dataConf ? date('H:i', strtotime($dataConf)) : '-';
            ?>
        </td>
        <td>
            <?php
            $inicio = !empty($agendamento['data_liberado']) ? strtotime($agendamento['data_liberado']) : null;
            $fim = isset($datasConferencia[$agendamento['id']]) && $datasConferencia[$agendamento['id']] ? strtotime($datasConferencia[$agendamento['id']]) : null;
            if ($inicio && $fim && $fim > $inicio) {
                $diff = $fim - $inicio;
                $horas = floor($diff / 3600);
                $minutos = floor(($diff % 3600) / 60);
                printf('%02d:%02d', $horas, $minutos);
            } else {
                echo '-';
            }
            ?>
        </td>
    </tr>
<?php endforeach; ?>
</tbody>
    </table>
</div>

<!-- Modal e formulário aqui -->
<div id="modalConferencia" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; z-index:2000;">
  <div class="modal-content" style="position:relative; min-width:340px; max-width:95vw; padding:0; border-radius:12px; box-shadow:0 2px 8px rgba(37,76,144,0.10);">
    <div style="background: linear-gradient(90deg, #0052a5 60%, #17a2b8 100%); border-radius:12px 12px 0 0; padding:18px 24px 12px 24px; display:flex; align-items:center; justify-content:space-between;">
      <h3 style="color:#fff; margin:0; font-size:1.3rem; font-weight:700; letter-spacing:1px;">Registrar Conferência</h3>
      <button type="button" onclick="fecharModal()" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer; margin-left:12px;">&times;</button>
    </div>
    <form id="formConferencia" style="padding:24px 24px 16px 24px;">
      <input type="hidden" name="agendamento_id" id="agendamento_id">
      <label style="font-weight:500; color:#254c90;">Quantidade de Paletes Recebida:</label>
      <input type="number" name="paletes_recebidos" required style="width:100%; padding:7px 10px; border-radius:6px; border:1px solid #bfc9da; font-size:1rem; margin-bottom:10px; background:#f4f6f9; color:#254c90;">
      <label style="font-weight:500; color:#254c90;">Quantidade de Volumes Recebida:</label>
      <input type="number" name="volumes_recebidos" required style="width:100%; padding:7px 10px; border-radius:6px; border:1px solid #bfc9da; font-size:1rem; margin-bottom:10px; background:#f4f6f9; color:#254c90;">
      <label style="font-weight:500; color:#254c90;">Observações:</label>
      <textarea name="observacoes" style="width:100%; padding:7px 10px; border-radius:6px; border:1px solid #bfc9da; font-size:1rem; margin-bottom:10px; background:#f4f6f9; color:#254c90; resize:vertical; min-height:60px;"></textarea>
      <label style="font-weight:500; color:#254c90;">Nome do Conferente:</label>
      <input type="text" name="nome_conferente" required style="width:100%; padding:7px 10px; border-radius:6px; border:1px solid #bfc9da; font-size:1rem; margin-bottom:10px; background:#f4f6f9; color:#254c90;">
      <div style="display:flex; gap:10px; margin-top:10px;">
        <button type="submit" style="flex:1; padding:8px 0; background:#254c90; color:#fff; border:none; border-radius:8px; font-weight:bold; font-size:1rem; cursor:pointer;">Salvar</button>
        <button type="button" onclick="fecharModal()" style="flex:1; padding:8px 0; background:#bfc9da; color:#254c90; border:none; border-radius:8px; font-weight:bold; font-size:1rem; cursor:pointer;">Cancelar</button>
      </div>
    </form>





</html></body></div>  </div>  </div>
</div>

</body>
</html>