<?php
/**
 * visao-agendamentos-publico.php
 *
 * Página pública para consulta e edição de agendamentos por responsável.
 *
 * FUNCIONALIDADE:
 * - Permite que um responsável consulte todos os agendamentos cadastrados em seu nome.
 * - Exibe uma tabela com filtros por coluna (data, caminhão, carga, fornecedor, etc).
 * - Permite editar ou excluir agendamentos via modal (caso necessário).
 * - Mostra o status do agendamento (apenas visualização, não editável).
 * - Possui botão para retornar ao calendário público.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Recebe o nome do responsável via parâmetro GET.
 * - Busca no banco de dados todos os agendamentos associados ao responsável informado.
 * - Renderiza a tabela HTML com filtros dinâmicos por coluna.
 * - Usa JavaScript para:
 *   - Abrir modal de edição e enviar alterações via AJAX.
 *   - Excluir agendamento via AJAX.
 *   - Filtrar linhas da tabela por coluna.
 *   - Exibir modais de conferência (se necessário).
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Requer scripts PHP auxiliares: editar-agendamento.php.
 * - Utiliza arquivos de imagem para background e header.
 */

require 'db.php';

$nomeResponsavel = $_GET['nome_responsavel'] ?? '';

if ($nomeResponsavel) {
    $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE nome_responsavel = ?");
    $stmt->execute([$nomeResponsavel]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $agendamentos = [];
}
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
            font-size: 15px;
            white-space: nowrap;
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
        }

        .status-receber { background-color: #28a745; color: #fff; }
        .status-liberado { background-color: #17a2b8; color: #fff; }
        .status-recebendo { background-color: #ffc107; color: #000; }
        .status-recebido { background-color: #dc3545; color: #fff; }
        .status-em-analise { background-color: #ff9800; color: #fff; }

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
    background-size: cover;
    border-radius: 8px 8px 0 0;
    padding: 18px 24px;
    margin: -20px -20px -10px -20px;
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

.top-bar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    width: 100%;
    margin-bottom: 8px;
}

.btn-voltar {
    position: absolute;
    top: 18px;
    right: 18px;
    z-index: 10;
    padding: 6px 14px;    /* <-- Diminua aqui */
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    box-shadow: 1px 1px 5px rgba(0, 0, 0, 0.2);
    transition: background-color 0.3s ease;
    font-size: 0.95rem;   /* <-- Diminua aqui se quiser */
}

.btn-voltar:hover {
    background-color: #218838;
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
    </style>
    <script>
    // Funções globais (definidas apenas uma vez)
    function atualizarStatus(select, agendamentoId) {
        const cell = select.parentElement;
        cell.className = '';
        aplicarEstiloStatus(cell, select.value);

        fetch('atualizar_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: agendamentoId, status: select.value })
        })
        .then(response => response.json())
        .then(data => {
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
        if (status === 'A Receber') cell.classList.add('status-receber');
        else if (status === 'Liberado') cell.classList.add('status-liberado');
        else if (status === 'Recebendo') cell.classList.add('status-recebendo');
        else if (status === 'Recebido') cell.classList.add('status-recebido');
        else if (status === 'Em Analise') cell.classList.add('status-em-analise');
    }

    function editarAgendamento(id) {
        const tr = document.querySelector('button[onclick="editarAgendamento(' + id + ')"]').closest('tr');
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_tipo_caminhao').value = tr.children[1].innerText.toLowerCase();
        document.getElementById('edit_tipo_carga').value = tr.children[2].innerText;
        document.getElementById('edit_tipo_mercadoria').value = tr.children[3].innerText;
        document.getElementById('edit_fornecedor').value = tr.children[4].innerText;
        document.getElementById('edit_quantidade_paletes').value = tr.children[6].innerText;
        document.getElementById('edit_quantidade_volumes').value = tr.children[7].innerText;
        document.getElementById('edit_placa').value = tr.children[8].innerText;
        document.getElementById('edit_comprador').value = tr.children[10].innerText;
        document.getElementById('edit_nome_motorista').value = tr.children[11].innerText;
        document.getElementById('edit_cpf_motorista').value = tr.children[12].innerText;
        document.getElementById('edit_celular_motorista').value = tr.children[13].innerText;

        // Tipo de recebimento
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
    }

    function excluirAgendamento(id) {
        if (confirm("Tem certeza que deseja excluir este agendamento?")) {
            const formData = new FormData();
            formData.append("id", id);
            formData.append("excluir", "true");
            fetch("editar-agendamento.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then (data => {
                if (data.success) {
                    alert("Agendamento excluído com sucesso!");
                    fecharModalEditar();
                    location.reload();
                } else {
                    alert("Erro ao excluir o agendamento: " + data.message);
                }
            })
            .catch(error => {
                alert("Erro na conexão com o servidor.");
                console.error(error);
            });
        }
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

    document.addEventListener('mousedown', function(e) {
        const filtroAtivo = document.querySelector('th.filtro-ativo');
        if (filtroAtivo) {
            if (filtroAtivo.contains(e.target)) return;
            filtroAtivo.classList.remove('filtro-ativo');
            const select = filtroAtivo.querySelector('.filtro-input');
            if (select) select.style.display = 'none';
        }
    });

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
                location.reload();
            })
            .catch(() => alert('Erro ao salvar.'));
        };

        // Não adicione eventos de clique nas linhas!
    });
    </script>
</head>
<body>
    

    <div class="container">
  <button onclick="window.location.href='pagina-publica.php'" class="btn-voltar">
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
                        Qtde de Paletes
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
                        Qtde de Volumes
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
                        Celular Motorista
                        <span class="filtro-icone" onclick="abrirFiltroColuna(event, 12)">&#128269;</span>
                        <select class="filtro-input" data-col="12" onchange="filtrarColunaSelect(this)">
                            <option value="">Todos</option>
                            <?php
                            $celulares = array_unique(array_column($agendamentos, 'celular_motorista'));
                            foreach ($celulares as $celular) {
                                echo '<option value="'.htmlspecialchars($celular).'">'.htmlspecialchars($celular).'</option>';
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
                        <td><?= htmlspecialchars($agendamento['fornecedor']) ?></td>
                        <td><?= htmlspecialchars($agendamento['nome_responsavel']) ?></td>
                        <td><?= htmlspecialchars($agendamento['quantidade_paletes']) ?></td>
                        <td><?= htmlspecialchars($agendamento['quantidade_volumes']) ?></td>
                        <td><?= htmlspecialchars($agendamento['placa']) ?></td>
                        <td class="status-receber">
                            <select class="status-select" disabled>
                                <option value="A Receber" <?= $agendamento['status'] == 'A Receber' ? 'selected' : '' ?>>A Receber</option>
                                <option value="Liberado" <?= $agendamento['status'] == 'Liberado' ? 'selected' : '' ?>>Liberado</option>
                                <option value="Em Analise" <?= $agendamento['status'] == 'Em Analise' ? 'selected' : '' ?>>Em Analise</option>
                                <option value="Recebendo" <?= $agendamento['status'] == 'Recebendo' ? 'selected' : '' ?>>Recebendo</option>
                                <option value="Recebido" <?= $agendamento['status'] == 'Recebido' ? 'selected' : '' ?>>Recebido</option>
                            </select>
                        </td>
                        <td><?= htmlspecialchars($agendamento['comprador']) ?></td>
                        <td><?= htmlspecialchars($agendamento['nome_motorista']) ?></td>
                        <td><?= htmlspecialchars($agendamento['cpf_motorista']) ?></td>
                        <td><?= htmlspecialchars($agendamento['numero_contato']) ?></td>
                        <td style="position:relative;">
                            <?= htmlspecialchars($agendamento['tipo_recebimento']) ?>
                            <button class="edit-btn" onclick="editarAgendamento(<?= $agendamento['id'] ?>)">Editar</button>
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
            <input type="text" name="numero_contato" id="edit_cumero_contato" required>
          </label>
          <label>Tipo de Recebimento:
            <input type="text" name="tipo_recebimento" id="edit_tipo_recebimento" required>
          </label>
        </div>
      </div>
      <div class="modal-actions">
        <button type="submit">Salvar</button>
        <button type="button" onclick="excluirAgendamento(document.getElementById('edit_id').value)" style="background:#dc3545; color:white;">Excluir</button>
        <button type="button" onclick="fecharModalEditar()">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Conferência -->
<div id="modalConferencia" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; z-index:2000;">
  <div style="background:#fff; padding:32px 28px; border-radius:12px; min-width:320px; max-width:95vw; position:relative;">
    <button onclick="fecharModalConferencia()" style="position:absolute; top:12px; right:18px; font-size:1.3rem; background:none; border:none; cursor:pointer;">&times;</button>
    <h3>Dados da Conferência</h3>
    <div id="conferenciaDados">
      <!-- Dados serão preenchidos via JS -->
    </div>
  </div>
</div>

<style>
#modalConferencia h3 {
    background: linear-gradient(90deg, #254c90 60%, #17a2b8 100%);
    color: #fff;
    padding: 16px 0 16px 18px;
    margin: -32px -28px 18px -28px; /* cobre toda a largura do modal */
    border-radius: 12px 12px 0 0;
    font-size: 1.5rem;
    font-weight: bold;
    letter-spacing: 1px;
    box-shadow: 0 2px 8px rgba(37,76,144,0.08);
    text-align: left;
}
</style>
</body>
</html>
