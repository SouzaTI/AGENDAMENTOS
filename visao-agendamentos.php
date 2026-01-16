<?php
/**
 * visao-agendamentos.php
 *
 * Página de gerenciamento e visualização de todos os agendamentos do sistema.
 *
 * FUNCIONALIDADE:
 * - Exibe uma tabela com todos os agendamentos cadastrados, com filtros por coluna (data, caminhão, carga, fornecedor, etc).
 * - Permite editar ou excluir agendamentos via modal.
 * - Permite alterar o status do agendamento diretamente na tabela (AJAX).
 * - Mostra ícone de alerta caso haja observação na conferência de um agendamento recebido.
 * - Permite visualizar detalhes da conferência de cada agendamento em um modal.
 * - Atualiza automaticamente a página a cada 10 segundos, exceto quando há interação do usuário (edição, modais, selects).
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Busca todos os agendamentos do banco de dados.
 * - Busca observações de conferências para exibir alertas na tabela.
 * - Renderiza a tabela HTML com filtros dinâmicos por coluna.
 * - Usa JavaScript para:
 *   - Atualizar status via AJAX.
 *   - Abrir modais de edição e conferência.
 *   - Filtrar linhas da tabela por coluna.
 *   - Controlar o reload automático da página.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Requer scripts PHP auxiliares: editar-agendamento.php, atualizar_status.php, get_conferencia.php.
 * - Utiliza arquivos de imagem para background e header.
 */

require 'db.php';

$hoje = date('Y-m-d');
$sql = "SELECT * FROM agendamentos WHERE data_agendamento = :hoje";
$stmt = $pdo->prepare($sql);
$stmt->execute([':hoje' => $hoje]);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca todas as conferências
$conferencias = [];
$stmt = $pdo->query("SELECT agendamento_id, observacoes FROM conferencias_recebimento WHERE observacoes IS NOT NULL AND observacoes != ''");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $conf) {
    $conferencias[$conf['agendamento_id']] = $conf['observacoes'];
}

$compradorLogado = isset($_SESSION['compradorLogado']) ? $_SESSION['compradorLogado'] : 'Desconhecido';
$compradorLogadoURL = urlencode($compradorLogado);
$linkFornecedor = "http://{$_SERVER['HTTP_HOST']}/pagina-principal.html?comprador={$compradorLogadoURL}";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Agendamentos</title>
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
            text-transform: uppercase;
        }

        h1 {
            font-size: 36px;
            color: #ffffff;
            margin-bottom: 20px;
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
            margin-top: 0;
            padding-top: 0;
        }

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
            font-size: 16px;      /* Aumentado de 15px para 16px */
            white-space: nowrap;
            color: #000;          /* Fonte preta */
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

        .status-chegada-nf { background-color: #17a2b8; color: #fff; }
        .status-liberado { background-color: rgb(70, 218, 104); color: #fff; }
        .status-recebendo { background-color: #ff9800; color: #000; }
        .status-recebido { background-color:rgb(19, 141, 48); color: #fff; }
        .status-em-analise { background-color: #ffc107; color: #fff; }
        .status-recusado { background-color: #dc3545; color: #fff; }

        .container h2 {
    font-size: 28px;        /* tamanho da fonte */
    color: #254c90;         /* cor do texto */
    font-weight: 700;       /* negrito */
    text-align: center;     /* centralizar o texto */
    margin-bottom: 20px;    /* espaçamento abaixo do título */
    text-transform: uppercase; /* deixar o texto em maiúsculas */
    letter-spacing: 2px;    /* espaçamento entre letras */
    font-family: 'Roboto', sans-serif; /* fonte personalizada */
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1); /* sombra leve */
  }

    @media (max-width: 1200px) {
    .container {
        min-width: 900px;
        padding: 5px;
    }
    table {
        min-width: 900px;
    }
}

.edit-btn {
    display: none;
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    padding: 4px 12px;
    background: #254c90;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 13px;
    z-index: 2;
    transition: background 0.2s;
}
tr:hover .edit-btn {
    display: inline-block;
}
.table-row-wrapper {
    position: relative;
}
.edit-btn:hover {
    background: #007bff;
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

.top-bar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    width: 100%;
    margin-bottom: 8px;
}

.btn-voltar {
    position: absolute;
    top: 5px;
    right: 20px;
    z-index: 10;
    padding: 8px 8px;      /* Aumenta o tamanho do botão */
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 12px;     /* Cantos mais arredondados */
    cursor: pointer;
    font-weight: bold;
    box-shadow: 1px 1px 5px rgba(0, 0, 0, 0.2);
    transition: background-color 0.3s ease, transform 0.2s;
    font-size: 1.25rem;      /* Fonte maior */
}

.btn-voltar:hover {
    background-color: #218838;
    transform: scale(1.05);  /* Efeito leve ao passar o mouse */
}

/* Filtro por coluna estilo Excel */
th {
    position: relative;
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
.obs-icone {
    display: block;
    font-size: 18px;
    color: #dc3545;
    margin-bottom: 2px;
}

/* Aumenta a largura mínima da coluna STATUS */
table th:nth-child(9),
table td:nth-child(9) {
    min-width: 130px;   /* Ajuste conforme necessário */
    max-width: 180px;
}

/* Faz o select ocupar toda a largura e não cortar texto */
.status-select {
    width: 100%;
    min-width: 120px;   /* Ajuste conforme necessário */
    white-space: normal;
    text-overflow: unset;
    overflow: visible;
}
    </style>

    <script>
    // Função para atualizar o status e salvar no banco
    let ajaxStatusEmAndamento = false;

function atualizarStatus(select, agendamentoId) {
    console.log('Atualizando status:', agendamentoId, select.value); // Adicione esta linha
    const cell = select.parentElement;
    cell.className = '';
    aplicarEstiloStatus(cell, select.value);

    ajaxStatusEmAndamento = true; // Bloqueia reload
    fetch('atualizar_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: agendamentoId, status: select.value })
    })
    .then(response => response.json())
    .then(data => {
        ajaxStatusEmAndamento = false; // Libera reload
        if (!data.success) {
            alert('Erro ao atualizar o status.');
        }
    })
    .catch(error => {
        ajaxStatusEmAndamento = false;
        alert('Erro na conexão com o servidor.');
        console.error(error);
    });
}

// Função para aplicar a cor do status ao carregar a página
    function aplicarEstiloStatus(cell, status) {
        cell.className = ''; // Remove todas as classes

        if (status === 'Chegada NF') cell.classList.add('status-chegada-nf');
        else if (status === 'Liberado') cell.classList.add('status-liberado');
        else if (status === 'Recebendo') cell.classList.add('status-recebendo');
        else if (status === 'Recebido') cell.classList.add('status-recebido');
        else if (status === 'Em Analise') cell.classList.add('status-em-analise');
        else if (status === 'Recusado') cell.classList.add('status-recusado');
    }

    // Ao carregar a página, aplica os estilos com base nos status existentes
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".status-select").forEach(select => {
            const cell = select.parentElement;
            aplicarEstiloStatus(cell, select.value);
        });

        document.getElementById('formEditarAgendamento').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('editar-agendamento.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.text())
            .then(resp => {
                alert('Agendamento atualizado com sucesso!');
                fecharModalEditar();
                atualizarTabelaAgendamentos();
            })
            .catch(() => alert('Erro ao salvar.'));
        };

        // Adiciona evento de clique nas linhas (exceto status)
        document.querySelectorAll("tbody tr.table-row-wrapper").forEach(tr => {
            tr.addEventListener("click", function(e) {
                // Ignora clique se for na célula do status (índice 8)
                if (e.target.closest('td') && e.target.closest('td').cellIndex === 8) return;
                // Ignora clique no botão editar
                if (e.target.closest('button')) return;
                const id = <?= json_encode(array_column($agendamentos, 'id')) ?>[this.rowIndex-1];
                abrirModalConferencia(id);
            });
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".status-select").forEach(select => {
            select.addEventListener('change', function() {
                atualizarStatus(this, this.dataset.id);
            });
        });
    });

    function editarAgendamento(btn, id) {
    reloadAtivo = false; // Pausa o reload automático
    let tr = btn;
    while (tr && tr.tagName !== 'TR') {
        tr = tr.parentElement;
    }
    if (!tr) return; // segurança

    document.getElementById('edit_id').value = id;
    document.getElementById('edit_tipo_caminhao').value = tr.children[1].innerText.toLowerCase();
    document.getElementById('edit_tipo_carga').value = tr.children[2].innerText;
    document.getElementById('edit_tipo_mercadoria').value = tr.children[3].innerText;
    document.getElementById('edit_fornecedor').value = tr.children[4].innerText;
    document.getElementById('edit_quantidade_paletes').value = tr.children[5].innerText;
    document.getElementById('edit_quantidade_volumes').value = tr.children[6].innerText;
    document.getElementById('edit_placa').value = tr.children[7].innerText;
    document.getElementById('edit_comprador').value = tr.children[9].innerText;
    document.getElementById('edit_nome_motorista').value = tr.children[10].innerText;
    document.getElementById('edit_cpf_motorista').value = tr.children[11].innerText;
    document.getElementById('edit_numero_contato').value = tr.children[13].innerText;
    // Tipo de Recebimento
    const tdTipoRecebimento = tr.children[14];
    let tipoRecebimento = "";
    for (let node of tdTipoRecebimento.childNodes) {
        if (node.nodeType === Node.TEXT_NODE) {
            tipoRecebimento += node.textContent.trim();
        }
    }
    document.getElementById('edit_tipo_recebimento').value = tipoRecebimento;
    document.getElementById('modalEditar').style.display = 'flex';
}

function fecharModalEditar() {
    document.getElementById('modalEditar').style.display = 'none';
    reloadAtivo = true; // Retoma o reload automático
}

// Função para abrir o modal de conferência
function abrirModalConferencia(idAgendamento) {
    reloadAtivo = false; // Pausa reload
    fetch('get_conferencia.php?id=' + idAgendamento)
        .then(r => r.json())
        .then(data => {
            let html = '';
            if (Object.keys(data).length === 0) {
                html = '<p style="color:#dc3545;">Nenhuma conferência encontrada para este agendamento.</p>';
            } else {
                html = `
    <ul>
        <li>paletes recebidos: ${data.paletes_recebidos}</li>
        <li>volumes recebidos: ${data.volumes_recebidos}</li>
        <li>observacoes: ${data.observacoes}</li>
        <li><b>nome conferente:</b> ${data.nome_conferente}</li>
        <li><b>data conferencia:</b> ${data.data_conferencia}</li>
    </ul>
`;
            }
            document.getElementById('conferenciaDados').innerHTML = html;
            document.getElementById('modalConferencia').style.display = 'flex';
        });
}

// Retoma reload ao fechar o modal de conferência
function fecharModalConferencia() {
    document.getElementById('modalConferencia').style.display = 'none';
    reloadAtivo = true; // Retoma reload
}

function excluirAgendamento(id) {
    if (!confirm("Tem certeza que deseja excluir este agendamento?")) return;
    fetch('editar-agendamento.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id) + '&excluir=true'
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert("Agendamento excluído com sucesso!");
                fecharModalEditar();
                atualizarTabelaAgendamentos();
            } else {
                alert("Erro ao excluir: " + (data.message || ''));
            }
        } catch (e) {
            alert("Resposta inesperada do servidor:\n" + text);
        }
    })
    .catch(() => alert("Erro ao conectar com o servidor."));
}

function abrirFiltroColuna(event, col) {
    event.stopPropagation();
    document.querySelectorAll('th').forEach(th => th.classList.remove('filtro-ativo'));
    const th = event.target.closest('th');
    th.classList.add('filtro-ativo');
    const select = th.querySelector('.filtro-input');
    select.style.display = 'block';
    select.focus();
}

document.addEventListener('mousedown', function(e) {
    const filtroAtivo = document.querySelector('th.filtro-ativo');
    if (filtroAtivo) {
        if (filtroAtivo.contains(e.target)) return;
        filtroAtivo.classList.remove('filtro-ativo');
        const select = filtroAtivo.querySelector('.filtro-input');
        if (select) select.style.display = 'none';
    }
});

function filtrarColunaSelect(select) {
    const col = parseInt(select.dataset.col);
    const valor = select.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(tr => {
        const cell = tr.children[col];
        if (!cell) return;
        const texto = cell.innerText.toLowerCase();
        tr.style.display = (!valor || texto === valor) ? '' : 'none';
    });
}

document.addEventListener("DOMContentLoaded", function() {
    // Função para formatar a data de hoje no padrão dd/mm/yyyy
    function dataHojeBR() {
        const hoje = new Date();
        const dia = String(hoje.getDate()).padStart(2, '0');
        const mes = String(hoje.getMonth() + 1).padStart(2, '0');
        const ano = hoje.getFullYear();
        return `${dia}/${mes}/${ano}`;
    }

    // Seleciona o filtro de data (primeiro select com data-col="0")
    const filtroData = document.querySelector('select.filtro-input[data-col="0"]');
    if (filtroData) {
        const hoje = dataHojeBR();
        // Se existir a opção do dia de hoje, seleciona ela
        for (let opt of filtroData.options) {
            if (opt.value === hoje) {
                filtroData.value = hoje;
                // Dispara o filtro
                filtroData.dispatchEvent(new Event('change'));
                break;
            }
        }
    }
});

// Atualização automática do tbody via AJAX, respeitando o filtro de data

let reloadAtivo = true;

// Atualiza só o tbody a cada 10 segundos, sempre respeitando o filtro de data
function atualizarTabelaAgendamentos() {
    if (!reloadAtivo || algumModalAberto()) return;
    // Pega a data selecionada no filtro (formato dd/mm/yyyy)
    let filtroData = document.querySelector('select.filtro-input[data-col="0"]');
    let data = filtroData ? filtroData.value : '';
    // Se não houver filtro, usa o dia atual
    if (!data) {
        const hoje = new Date();
        const dia = String(hoje.getDate()).padStart(2, '0');
        const mes = String(hoje.getMonth() + 1).padStart(2, '0');
        const ano = hoje.getFullYear();
        data = `${dia}/${mes}/${ano}`;
        if (filtroData) filtroData.value = data;
    }
    fetch('tabela-agendamentos.php?data=' + encodeURIComponent(data))
        .then(r => r.text())
        .then(html => {
            document.querySelector('table tbody').innerHTML = html;
            reaplicarEventosTabela();
        });
}

// Reaplica eventos após atualizar o tbody
function reaplicarEventosTabela() {
    // Reaplica eventos de status
    document.querySelectorAll(".status-select").forEach(select => {
        select.addEventListener('change', function() {
            atualizarStatus(this, this.dataset.id);
        });
        const cell = select.parentElement;
        aplicarEstiloStatus(cell, select.value);
    });

    // Reaplica eventos de clique nas linhas (exceto status)
    document.querySelectorAll("tbody tr.table-row-wrapper").forEach(tr => {
        tr.addEventListener("click", function(e) {
            if (e.target.closest('td') && e.target.closest('td').cellIndex === 8) return;
            if (e.target.closest('button')) return;
            const id = this.querySelector('.status-select').dataset.id;
            abrirModalConferencia(id);
        });
    });
}

// Atualiza a tabela a cada 10 segundos
setInterval(atualizarTabelaAgendamentos, 10000);

// Atualiza a tabela ao trocar o filtro de data
document.addEventListener("DOMContentLoaded", function() {
    let filtroData = document.querySelector('select.filtro-input[data-col="0"]');
    if (filtroData) {
        filtroData.addEventListener('change', function() {
            atualizarTabelaAgendamentos();
        });
    }
    reaplicarEventosTabela();
});

// Função para saber se algum modal está aberto
function algumModalAberto() {
    return (
        document.getElementById('modalEditar').style.display === 'flex' ||
        document.getElementById('modalConferencia').style.display === 'flex'
    );
}
    </script>


</head>
<body>
    

    <div class="container">
  <button onclick="window.location.href='pagina-principal.php'" class="btn-voltar">
    Ir para o Calendário
  </button>
  <div class="header-agendamentos">
    <!-- <img src="img/seu-logo.png" alt="Logo" class="header-logo"> -->
    <h2>Lista de Agendamentos</h2>
  </div>
  <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>
                        Data
                        <span class="filtro-icone" onclick="abrirFiltroColuna(event, 0)">&#128269;</span>
                        <select class="filtro-input" data-col="0" onchange="filtrarColunaSelect(this)">
                            <option value="">Todos</option>
                            <?php
                            // Busca todas as datas distintas para o filtro
$datasFiltro = [];
$stmtDatas = $pdo->query("SELECT DISTINCT data_agendamento FROM agendamentos ORDER BY data_agendamento DESC");
foreach ($stmtDatas->fetchAll(PDO::FETCH_COLUMN) as $data) {
    $datasFiltro[] = date('d/m/Y', strtotime($data));
}
                            foreach ($datasFiltro as $data) {
                                echo '<option value="'.htmlspecialchars($data).'">'.htmlspecialchars($data).'</option>';
                            }
                            ?>
                        </select>
                    </th>
                    <th>
                        Tipo do Caminhão
                        <span class="filtro-icone" onclick="abrirFiltroColuna(event, 1)">&#128269;</span>
                        <select class="filtro-input" data-col="1" onchange="filtrarColunaSelect(this)">
                            <option value="">Todos</option>
                            <?php
                            $tipos = array_unique(array_column($agendamentos, 'tipo_caminhao'));
                            foreach ($tipos as $tipo) {
                                echo '<option value="'.htmlspecialchars($tipo).'">'.htmlspecialchars($tipo).'</option>';
                            }
                            ?>
                        </select>
                    </th>
                    <th>
                        Tipo de Carga
                        <span class="filtro-icone" onclick="abrirFiltroColuna(event, 2)">&#128269;</span>
                        <select class="filtro-input" data-col="2" onchange="filtrarColunaSelect(this)">
                            <option value="">Todos</option>
                            <?php
                            $cargas = array_unique(array_column($agendamentos, 'tipo_carga'));
                            foreach ($cargas as $carga) {
                                echo '<option value="'.htmlspecialchars($carga).'">'.htmlspecialchars($carga).'</option>';
                            }
                            ?>
                        </select>
                    </th>
                    <th>
                        Tipo de Mercadoria
                        <span class="filtro-icone" onclick="abrirFiltroColuna(event, 3)">&#128269;</span>
                        <select class="filtro-input" data-col="3" onchange="filtrarColunaSelect(this)">
                            <option value="">Todos</option>
                            <?php
                            $mercadorias = array_unique(array_column($agendamentos, 'tipo_mercadoria'));
                            foreach ($mercadorias as $mercadoria) {
                                echo '<option value="'.htmlspecialchars($mercadoria).'">'.htmlspecialchars($mercadoria).'</option>';
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
                        Paletes
                        <span class="filtro-icone" onclick="abrirFiltroColuna(event, 6)">&#128269;</span>
                        <select class="filtro-input" data-col="6" onchange="filtrarColunaSelect(this)">
                            <option value="">Todos</option>
                            <?php
                            $paletes = array_unique(array_column($agendamentos, 'quantidade_paletes'));
                            foreach ($paletes as $palete) {
                                echo '<option value="'.htmlspecialchars($palete).'">'.htmlspecialchars($palete).'</option>';
                            }
                            ?>
                        </select>
                    </th>
                    <th>
                        Volumes
                        <span class="filtro-icone" onclick="abrirFiltroColuna(event, 7)">&#128269;</span>
                        <select class="filtro-input" data-col="7" onchange="filtrarColunaSelect(this)">
                            <option value="">Todos</option>
                            <?php
                            $volumes = array_unique(array_column($agendamentos, 'quantidade_volumes'));
                            foreach ($volumes as $volume) {
                                echo '<option value="'.htmlspecialchars($volume).'">'.htmlspecialchars($volume).'</option>';
                            }
                            ?>
                        </select>
                    </th>
                    <th>
                        Placa
                        <span class="filtro-icone" onclick="abrirFiltroColuna(event, 8)">&#128269;</span>
                        <select class="filtro-input" data-col="8" onchange="filtrarColunaSelect(this)">
                            <option value="">Todos</option>
                            <?php
                            $placas = array_unique(array_column($agendamentos, 'placa'));
                            foreach ($placas as $placa) {
                                echo '<option value="'.htmlspecialchars($placa).'">'.htmlspecialchars($placa).'</option>';
                            }
                            ?>
                        </select>
                    </th>
                    <th>Status</th>
                    <th>
                        Comprador
                        <span class="filtro-icone" onclick="abrirFiltroColuna(event, 9)">&#128269;</span>
                        <select class="filtro-input" data-col="9" onchange="filtrarColunaSelect(this)">
                            <option value="">Todos</option>
                            <?php
                            $compradores = array_unique(array_column($agendamentos, 'comprador'));
                            foreach ($compradores as $comprador) {
                                echo '<option value="'.htmlspecialchars($comprador).'">'.htmlspecialchars($comprador).'</option>';
                            }
                            ?>
                        </select>
                    </th>
                    <th>
                        Nome Motorista
                        <span class="filtro-icone" onclick="abrirFiltroColuna(event, 10)">&#128269;</span>
                        <select class="filtro-input" data-col="10" onchange="filtrarColunaSelect(this)">
                            <option value="">Todos</option>
                            <?php
                            $motoristas = array_unique(array_column($agendamentos, 'nome_motorista'));
                            foreach ($motoristas as $motorista) {
                                echo '<option value="'.htmlspecialchars($motorista).'">'.htmlspecialchars($motorista).'</option>';
                            }
                            ?>
                        </select>
                    </th>
                    <th>
                        CPF Motorista
                        <span class="filtro-icone" onclick="abrirFiltroColuna(event, 11)">&#128269;</span>
                        <select class="filtro-input" data-col="11" onchange="filtrarColunaSelect(this)">
                            <option value="">Todos</option>
                            <?php
                            $cpfs = array_unique(array_column($agendamentos, 'cpf_motorista'));
                            foreach ($cpfs as $cpf) {
                                echo '<option value="'.htmlspecialchars($cpf).'">'.htmlspecialchars($cpf).'</option>';
                            }
                            ?>
                        </select>
                    </th>
                    <th>
                        Nome do Responsável
                        <span class="filtro-icone" onclick="abrirFiltroColuna(event, 5)">&#128269;</span>
                        <select class="filtro-input" data-col="5" onchange="filtrarColunaSelect(this)">
                            <option value="">Todos</option>
                            <?php
                            $responsaveis = array_unique(array_column($agendamentos, 'nome_responsavel'));
                            foreach ($responsaveis as $responsavel) {
                                echo '<option value="'.htmlspecialchars($responsavel).'">'.htmlspecialchars($responsavel).'</option>';
                            }
                            ?>
                        </select>
                    </th>
                    <th>
                        Número de Contato
                        <span class="filtro-icone" onclick="abrirFiltroColuna(event, 12)">&#128269;</span>
                        <select class="filtro-input" data-col="12" onchange="filtrarColunaSelect(this)">
                            <option value="">Todos</option>
                            <?php
                            $contatos = array_unique(array_column($agendamentos, 'numero_contato'));
                            foreach ($contatos as $contatos) {
                                echo '<option value="'.htmlspecialchars($contatos).'">'.htmlspecialchars($contatos).'</option>';
                            }
                            ?>
                        </select>
                        </th>
                    <th>
                        Tipo de Recebimento
                        <span class="filtro-icone" onclick="abrirFiltroColuna(event, 13)">&#128269;</span>
                        <select class="filtro-input" data-col="13" onchange="filtrarColunaSelect(this)">
                            <option value="">Todos</option>
                            <?php
                            $recebimentos = array_unique(array_column($agendamentos, 'tipo_recebimento'));
                            foreach ($recebimentos as $recebimento) {
                                echo '<option value="'.htmlspecialchars($recebimento).'">'.htmlspecialchars($recebimento).'</option>';
                            }
                            ?>
                        </select>
                    </th>
                </tr>
            </thead>
            <tbody>
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
            </tbody>
        </table>
    </div>
    </div>

    <!-- Modal de edição -->
<style>
#modalEditar {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    background: rgba(0,0,0,0.4);
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
#modalEditar .modal-content {
    background: #fff;
    padding: 32px 28px 24px 28px;
    border-radius: 12px;
    min-width: 340px;
    max-width: 95vw;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    position: relative;
    font-family: 'Roboto', Arial, sans-serif;
    animation: modalFadeIn 0.2s;
}
@keyframes modalFadeIn {
    from { transform: translateY(-30px); opacity: 0; }
    to   { transform: translateY(0); opacity: 1; }
}
#modalEditar h3 {
    margin-top: 0;
    color: #254c90;
    font-size: 1.4rem;
    margin-bottom: 18px;
    text-align: center;
    letter-spacing: 1px;
}
#modalEditar form label {
    display: block;
    margin-bottom: 10px;
    color: #254c90;
    font-weight: 500;
    font-size: 1rem;
}
#modalEditar form input[type="text"],
#modalEditar form input[type="number"] {
    width: 100%;
    padding: 7px 10px;
    border: 1px solid #bfc9da;
    border-radius: 5px;
    font-size: 1rem;
    margin-top: 3px;
    margin-bottom: 8px;
    background: #f7fafd;
    transition: border 0.2s;
}
#modalEditar form input:focus {
    border-color: #254c90;
    outline: none;
}
#modalEditar .modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 18px;
}
#modalEditar button[type="submit"] {
    background: #254c90;
    color: #fff;
    border: none;
    padding: 8px 22px;
    border-radius: 5px;
    font-weight: bold;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.2s;
}
#modalEditar button[type="submit"]:hover {
    background: #0052a5;
}
#modalEditar button[type="button"] {
    background: #e0e4ea;
    color: #254c90;
    border: none;
    padding: 8px 18px;
    border-radius: 5px;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.2s;
}
#modalEditar button[type="button"]:hover {
    background: #bfc9da;
}
#modalEditar .close-x {
    position: absolute;
    top: 12px;
    right: 18px;
    font-size: 1.3rem;
    color: #254c90;
    background: none;
    border: none;
    cursor: pointer;
    font-weight: bold;
    transition: color 0.2s;
}
#modalEditar .close-x:hover {
    color: #dc3545;
}
@media (max-width: 600px) {
    #modalEditar .modal-content {
        min-width: 90vw;
        padding: 18px 6vw 14px 6vw;
    }
}
.modal-grid {
    display: flex;
    gap: 32px;
}
.modal-grid > div {
    flex: 1 1 0;
}
@media (max-width: 700px) {
    .modal-grid {
        flex-direction: column;
        gap: 0;
    }
}
</style>

<div id="modalEditar">
  <div class="modal-content">
    <button class="close-x" onclick="fecharModalEditar()" title="Fechar">&times;</button>
    <h3>Editar Agendamento</h3>
    <form id="formEditarAgendamento" autocomplete="off">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-grid">
        <div>
          <label>Tipo do Caminhão:
            <select name="tipo_caminhao" id="edit_tipo_caminhao" required>
              <option value="">Selecione</option>
              <option value="truck">Truck</option>
              <option value="toco">Toco</option>
              <option value="carreta">Carreta</option>
              <option value="utilitarios">Utilitários</option>
            </select>
          </label>
          <label>Tipo de Carga:
            <input type="text" name="tipo_carga" id="edit_tipo_carga" required>
          </label>
          <label>Tipo de Mercadoria:
            <input type="text" name="tipo_mercadoria" id="edit_tipo_mercadoria" required>
          </label>
          <label>Fornecedor:
            <input type="text" name="fornecedor" id="edit_fornecedor" required>
          </label>
          <label>Data:
            <input type="date" name="data_agendamento" id="edit_data_agendamento" required>
          </label>
          <label>Qtde de Paletes:
            <input type="number" name="quantidade_paletes" id="edit_quantidade_paletes" min="0" required>
          </label>
          <label>Qtde de Volumes:
            <input type="number" name="quantidade_volumes" id="edit_quantidade_volumes" min="0" required>
          </label>
        </div>
        <div>
          <label>Placa:
            <input type="text" name="placa" id="edit_placa" required>
          </label>
          <label>Comprador:
            <input type="text" name="comprador" id="edit_comprador" required>
          </label>
          <label>Nome Motorista:
            <input type="text" name="nome_motorista" id="edit_nome_motorista" required>
          </label>
          <label>CPF Motorista:
            <input type="text" name="cpf_motorista" id="edit_cpf_motorista" required>
          </label>
            <label>Número de Contato:
            <input type="text" name="numero_contato" id="edit_numero_contato" required>
            </label>
          <label>Tipo de Recebimento:
            <input type="text" name="tipo_recebimento" id="edit_tipo_recebimento" required>
          </label>
        </div>
      </div>
      <div class="modal-actions">
        <button type="submit">Salvar</button>
        <!-- <button type="button" onclick="excluirAgendamento(document.getElementById('edit_id').value)" style="background:#dc3545; color:white;">Excluir</button> -->
        <button type="button" onclick="fecharModalEditar()">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Conferência -->
<div id="modalConferencia" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; z-index:2000;">
  <div class="modal-conferencia-content">
    <div class="modal-conferencia-header">
      <span>Dados da Conferência</span>
      <button onclick="fecharModalConferencia()" class="modal-conferencia-close" title="Fechar">&times;</button>
    </div>
    <div id="conferenciaDados" class="modal-conferencia-body">
      <!-- Dados serão preenchidos via JS -->
    </div>
  </div>
</div>

<style>
.modal-conferencia-content {
    background: #fff;
    border-radius: 14px;
    min-width: 340px;
    width: 90vw;          /* Responsivo */
    max-width: 800px;     /* Nunca maior que isso */
    box-shadow: 0 8px 32px rgba(37,76,144,0.18);
    position: relative;
    font-family: 'Roboto', Arial, sans-serif;
    animation: modalFadeIn 0.2s;
    overflow: hidden;
}

@keyframes modalFadeIn {
    from { transform: translateY(-30px); opacity: 0; }
    to   { transform: translateY(0); opacity: 1; }
}

.modal-conferencia-header {
    background: linear-gradient(90deg, #254c90 60%, #17a2b8 100%);
    color: #fff;
    padding: 18px 24px 14px 24px;
    font-size: 1.3rem;
    font-weight: 700;
    letter-spacing: 1px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-radius: 14px 14px 0 0;
    box-shadow: 0 2px 8px rgba(37,76,144,0.08);
}

.modal-conferencia-close {
    background: none;
    border: none;
    color: #fff;
    font-size: 1.6rem;
    cursor: pointer;
    font-weight: bold;
    margin-left: 12px;
    transition: color 0.2s;
}

.modal-conferencia-close:hover {
    color: #dc3545;
}

.modal-conferencia-body {
    padding: 28px 32px 24px 32px;
    font-size: 1.15rem; /* Levemente reduzido para equilíbrio em telas grandes */
    color: #254c90;
}

.modal-conferencia-body ul {
    padding-left: 22px;
    margin: 0;
}

.modal-conferencia-body li {
    margin-bottom: 12px;
    font-size: 1.15rem;
    color: #254c90;
}

.modal-conferencia-body li b {
    color: #0052a5;
    font-size: 1.15rem;
}

/* Responsivo para telas pequenas */
@media (max-width: 600px) {
    .modal-conferencia-content {
        min-width: 90vw;
        padding: 0;
    }
    .modal-conferencia-body {
        padding: 16px 6vw 12px 6vw;
    }
}

/* Responsivo para telas muito grandes, como TVs */
@media (min-width: 1600px) {
    .modal-conferencia-content {
        max-width: 950px; /* Permite crescer um pouco mais em TVs muito grandes */
    }
    .modal-conferencia-body {
        font-size: 1.1rem;
    }
    .modal-conferencia-body li,
    .modal-conferencia-body li b {
        font-size: 1.1rem;
    }
}


</style>
</body>
</html>
