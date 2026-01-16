<?php
/**
 * visao-recebimento.php
 *
 * Página principal de controle do recebimento de cargas/agendamentos.
 *
 * FUNCIONALIDADE:
 * - Exibe uma tabela com todos os agendamentos do dia selecionado (ou do dia atual por padrão).
 * - Permite filtrar visualmente por colunas (data, tipo de caminhão, carga, fornecedor, tipo de recebimento, senha).
 * - Permite alterar o status do agendamento (ex: Liberado, Recebendo, Recebido) e o local de recebimento.
 * - Permite chamar o motorista (botão "Chamar") e registrar a conferência da carga (botão "Conferir" com modal).
 * - Atualiza automaticamente a página a cada 5 segundos, exceto quando o usuário está interagindo com campos editáveis ou modais.
 * - Usa AJAX para atualizar status, local de recebimento e registrar conferência sem recarregar a página.
 * - Possui botão para abrir o painel de senhas em nova aba.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Busca os agendamentos do banco de dados filtrando pela data e status relevantes.
 * - Renderiza a tabela HTML com filtros dinâmicos por coluna.
 * - Aplica estilos visuais distintos conforme o status do agendamento.
 * - Usa JavaScript para:
 *   - Atualizar status/local via AJAX.
 *   - Abrir modal de conferência e registrar dados via AJAX.
 *   - Filtrar linhas da tabela por coluna.
 *   - Controlar o reload automático da página.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Requer scripts PHP auxiliares: atualizar_status.php, atualizar_local_recebimento.php, registrar_conferencia.php, chamar_motorista.php.
 * - Utiliza arquivos de imagem para background e header.
 */

require 'db.php';

// Controle de acesso: só permite usuários do tipo "recebimento" ou "operacional"
session_start();
$tipoUsuario = $_SESSION['tipoUsuario'] ?? '';
if (!in_array($tipoUsuario, ['recebimento', 'supervisor'])) {
    header('Location: login.php');
    exit;
}

$filtro_data = $_GET['filtro_data'] ?? date('Y-m-d');

$sql = "SELECT id, data_agendamento, tipo_caminhao, tipo_carga, tipo_mercadoria, fornecedor, placa, status, nome_motorista, tipo_recebimento, senha, local_recebimento 
        FROM agendamentos 
        WHERE status IN ('Chegada NF', 'Liberado', 'Em Analise', 'Recebendo', 'Recebido', 'Recusado')
          AND data_agendamento = :filtro_data
        ORDER BY FIELD(tipo_recebimento, 'Porte Pequeno', 'Porte Médio', 'Porte Grande'), senha ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':filtro_data' => $filtro_data]);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recebimento - Senha de Espera</title>
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
            margin: 80px auto 0 auto; /* <-- Aumente aqui para empurrar para baixo */
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
            justify-content: space-between; /* Alinha o botão à direita */
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
.senha-destaque {
    background:rgb(238, 234, 186);         /* Amarelo claro */
    color:rgb(255, 115, 0);              /* Laranja forte */
    font-weight: bold;
    font-size: 1.3em;
    letter-spacing: 1px;
    border-radius: 0;
    text-align: center;
    box-shadow: none;
}

.botao-primario {
    flex: 1;
    padding: 10px 0;
    background:  #254c90;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    font-size: 1rem;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(37,76,144,0.10);
    margin-right: 8px;
}

.botao-primario:hover, .botao-primario:focus {
    background:  #254c90;
    transform: translateY(-2px) scale(1.03);
    box-shadow: 0 4px 16px rgba(37,76,144,0.18);
    outline: none;
}

.botao-secundario {
    flex: 1;
    padding: 10px 0;
    background: #bfc9da;
    color: #254c90;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    font-size: 1rem;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(37,76,144,0.08);
    transition: background 0.2s, color 0.2s, transform 0.2s;
}

.botao-secundario:hover, .botao-secundario:focus {
    background: #254c90;
    color: #fff;
    transform: translateY(-2px) scale(1.03);
    outline: none;
}

/* Botão Conferir */
.botao-conferir {
    padding: 7px 18px;
    background: #254c90;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    font-size: 1rem;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(37,76,144,0.10);
    transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
    outline: none;
}
.botao-conferir:disabled {
    background: #e0e5ef;
    color: #bfc9da;
    cursor: not-allowed;
    box-shadow: none;
}

/* Botão Chamar */
.botao-chamar {
    padding: 7px 18px;
    background: #254c90;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    font-size: 1rem;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(37,76,144,0.10);
    transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
    outline: none;
}
.botao-chamar:disabled {
    background: #e0e5ef;
    color: #bfc9da;
    cursor: not-allowed;
    box-shadow: none;
}

/* Texto Conferido */
.texto-conferido {
    color: #168d2e;
    font-weight: bold;
    font-size: 1.05rem;
    letter-spacing: 1px;
}

/* Texto Chamado */
.texto-chamado {
    text-decoration: line-through;
    color: #aaa;
    font-weight: bold;
    font-size: 1.05rem;
    letter-spacing: 1px;
}

.local-recebimento-select {
    background: #254c90;
    color: #fff;
    border: 1.5px solid #254c90;
    border-radius: 6px;
    padding: 7px 12px;
    font-size: 1rem;
    font-weight: bold;
    outline: none;
    transition: border 0.2s, box-shadow 0.2s;
    box-shadow: 0 2px 8px rgba(37,76,144,0.08);
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}
.local-recebimento-select:focus {
    border: 1.5px solid #17a2b8;
    box-shadow: 0 0 0 2px #17a2b833;
}
.local-recebimento-select option {
    color: #254c90;
    background: #fff;
    font-weight: normal;
}

.btn-painel-senhas {
    margin-left: auto;
    padding: 10px 18px;
    background: #168d2e;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    font-size: 1rem;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(37,76,144,0.10);
    transition: background 0.2s, transform 0.2s;
    margin-right: 8px;
}
.btn-painel-senhas:hover, .btn-painel-senhas:focus {
    background: #168d2e;
    transform: translateY(-2px) scale(1.03);
    outline: none;
}

/* Botão de Perfil acima do container */
.perfil-button-container {
    width: 100%;
    max-width: 95vw;
    display: flex;
    justify-content: flex-end;
    margin: 30px auto 0 auto;
}

.perfil-button {
    padding: 10px 18px;
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    box-shadow: 1px 1px 5px rgba(0,0,0,0.2);
    font-size: 1rem;
}

.perfil-button:hover {
    background-color: #218838;
}

/* Modal de perfil */
#modalPerfil {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.6);
    align-items: center;
    justify-content: center;
    z-index: 2000;
}
.modal-perfil-content {
    background: #fff;
    padding: 24px;
    border-radius: 12px;
    min-width: 320px;
    max-width: 95vw;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    position: relative;
}
.modal-perfil-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}
.modal-perfil-title {
    color: #254c90;
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
}
.modal-perfil-close {
    background: none;
    border: none;
    color: #254c90;
    font-size: 1.5rem;
    cursor: pointer;
}
.modal-perfil-close:hover {
    color: #0056b3;
}
.modal-perfil-footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
}
.modal-perfil-button {
    padding: 10px 20px;
    background: #254c90;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.3s, transform 0.3s;
}
.modal-perfil-button:hover {
    background: #0056b3;
    transform: translateY(-2px);
}
.modal-perfil-form input[type="password"] {
    width: 100%;
    padding: 8px;
    margin: 10px 0 18px 0;
    border-radius: 6px;
    border: 1px solid #ccc;
}
.modal-perfil-form button[type="submit"] {
    width: 100%;
    background: #254c90;
    color: #fff;
    padding: 10px 0;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
}
.modal-perfil-form button[type="submit"]:hover {
    background: #0056b3;
}
.modal-perfil-form .logout-btn {
    width: 100%;
    background: #d9534f;
    color: #fff;
    padding: 10px 0;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 10px;
}
.modal-perfil-form .logout-btn:hover {
    background: #c9302c;
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
        else if (status === 'Recusado') cell.classList.add('status-recusado');
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
                .then(data => {
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

    function abrirModalPerfil() {
        document.getElementById('modalPerfil').style.display = 'flex';
    }

    function fecharModalPerfil() {
        document.getElementById('modalPerfil').style.display = 'none';
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Fecha o modal de perfil ao clicar fora dele
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('modalPerfil');
            if (modal && modal.style.display === 'flex' && event.target === modal) {
                fecharModalPerfil();
            }
        });
    });
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

<!-- Modal de Perfil -->
<div id="modalPerfil">
  <div class="modal-perfil-content">
    <div class="modal-perfil-header">
      <h3 class="modal-perfil-title">Meu Perfil</h3>
      <button type="button" class="modal-perfil-close" onclick="fecharModalPerfil()">&times;</button>
    </div>
    <form class="modal-perfil-form" method="POST" action="trocar_senha.php" style="margin-bottom:18px;">
      <label for="nova_senha" style="font-weight:500;">Nova senha:</label>
      <input type="password" name="nova_senha" id="nova_senha" placeholder="Nova senha" required>
      <button type="submit">Salvar Senha</button>
    </form>
    <form class="modal-perfil-form" action="logout.php" method="POST">
      <button type="submit" class="logout-btn">Sair</button>
    </form>
  </div>
</div>

<div class="container">
    <div class="header-agendamentos">
        <h2>Lista de Agendamentos</h2>
        <button class="btn-painel-senhas" onclick="window.open('painel-senhas.php', '_blank')">
            Painel de Senhas
        </button>

        
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
                <th>Status</th>                
                <th>
                    Tipo de Recebimento
                    <span class="filtro-icone" onclick="abrirFiltroColuna(event, 7)">&#128269;</span>
                    <select class="filtro-input" data-col="7" onchange="filtrarColunaSelect(this)">
                        <option value="">Todos</option>
                        <?php
                        $recebimentos = array_unique(array_column($agendamentos, 'tipo_recebimento'));
                        foreach ($recebimentos as $recebimento) {
                            echo '<option value="'.htmlspecialchars($recebimento).'">'.htmlspecialchars($recebimento).'</option>';
                        }
                        ?>
                    </select>
                </th>
                <th>
                    Senha de Espera
                    <span class="filtro-icone" onclick="abrirFiltroColuna(event, 8)">&#128269;</span>
                    <select class="filtro-input" data-col="8" onchange="filtrarColunaSelect(this)">
                        <option value="">Todos</option>
                        <?php
                        $senhas = array_unique(array_column($agendamentos, 'senha'));
                        foreach ($senhas as $senha) {
                            echo '<option value="'.htmlspecialchars($senha).'">'.htmlspecialchars($senha).'</option>';
                        }
                        ?>
                    </select>
                </th>                
                <th>Local Recebimento</th>
                <th>Conferir</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($agendamentos as $agendamento): ?>
    <tr>
        <td><?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?></td>
        <td><?= htmlspecialchars($agendamento['tipo_caminhao']) ?></td>
        <td><?= htmlspecialchars($agendamento['tipo_carga']) ?></td>
        <td><?= htmlspecialchars($agendamento['fornecedor']) ?></td>
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
                <option value="Recusado" <?= $agendamento['status'] == 'Recusado' ? 'selected' : '' ?> disabled>Recusado</option>
            </select>
        </td>
        <td><?= htmlspecialchars($agendamento['tipo_recebimento']) ?></td>
        <td class="senha-destaque"><?= htmlspecialchars($agendamento['senha']) ?></td>
        <td>
            <?php if ($agendamento['status'] != 'Recebido'): ?>
                <select class="local-recebimento-select" onchange="atualizarLocalRecebimento(<?= $agendamento['id'] ?>, this.value)">
                    <option value="">Selecione o local</option>
                    <option value="P1" <?= $agendamento['local_recebimento']=='P1'?'selected':'' ?>>P1</option>
                    <option value="P2" <?= $agendamento['local_recebimento']=='P2'?'selected':'' ?>>P2</option>
                </select>
            <?php else: ?>
                <?= htmlspecialchars($agendamento['local_recebimento'] ?? '-') ?>
            <?php endif; ?>
        </td>
        <td>
            <button class="botao-conferir" onclick="abrirModal(<?= $agendamento['id'] ?>)" <?= $agendamento['status'] == 'Recebido' ? 'disabled' : '' ?>>Conferir</button>
        </td>
        <td>
            <?php if ($agendamento['status'] == 'Liberado'): ?>
                <button class="botao-chamar" onclick="chamarSenhaServidor(<?= $agendamento['id'] ?>)">Chamar</button>
            <?php elseif ($agendamento['status'] == 'Recebendo'): ?>
                <span class="texto-chamado">Chamado</span>
            <?php elseif ($agendamento['status'] == 'Recebido'): ?>
                <span class="texto-conferido">Conferido</span>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
</tbody>
    </table>
</button>
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
      <label style="font-weight:500; color:#254c90;">Quantidade de Volumes Recebidas:</label>
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
  </div>
</div>



</body>
</html>