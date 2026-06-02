<?php
/**
 * teste-publico.php
 * Ambiente de Homologação: Layout em Cards Responsivos
 */

require 'db.php';

$nomeResponsavel = $_GET['nome_responsavel'] ?? '';

if ($nomeResponsavel) {
    $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE nome_responsavel = ? ORDER BY data_agendamento DESC");
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
    <title>Meus Agendamentos (Teste Cards)</title>
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
            max-width: 1200px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            box-sizing: border-box;
            margin-bottom: 40px;
        }

        .header-agendamentos {
            display: flex;
            align-items: center;
            justify-content: space-between;
            /* Fundo em degradê elegante no lugar da imagem */
            background: linear-gradient(90deg, #254c90 0%, #173260 100%); 
            border-radius: 8px;
            padding: 20px 30px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(37,76,144,0.15);
        }

        @media (max-width: 768px) {
            .header-agendamentos {
                flex-direction: column;
                text-align: center;
                gap: 15px;
                padding: 20px 15px;
            }
        }

        .header-agendamentos h2 {
            color: #fff;
            margin: 0;
            font-size: 28px;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }

        .btn-voltar {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            transition: 0.3s;
            text-transform: uppercase;
        }
        .btn-voltar:hover { background-color: #218838; transform: translateY(-2px); }

        /* Barra de Pesquisa Global (Substitui os filtros das colunas antigas) */
        .search-bar {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #bfc9da;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 20px;
            box-sizing: border-box;
            color: #254c90;
        }
        .search-bar:focus { outline: none; border-color: #254c90; }

        /* ====== NOVO SISTEMA DE CARDS ====== */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .agendamento-card {
            background: #fff;
            border: 1px solid #e3eafc;
            border-left: 6px solid #254c90;
            border-radius: 10px;
            padding: 18px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
        }
        .agendamento-card:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.1); }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .card-data { font-size: 1.2rem; font-weight: bold; color: #254c90; }
        .card-placa { font-family: monospace; font-size: 1.1rem; background: #f4f6f9; padding: 4px 10px; border-radius: 6px; font-weight: bold; color: #333; border: 1px solid #ddd; }

        .card-body p { margin: 6px 0; font-size: 14px; color: #444; }
        .card-body strong { color: #254c90; }

        .card-status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 13px;
            color: white;
            margin-top: 10px;
            text-transform: uppercase;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }

        /* Cores dos Status */
        .bg-receber { background-color: #28a745; }
        .bg-liberado { background-color: #17a2b8; }
        .bg-recebendo { background-color: #ffc107; color: #000; }
        .bg-recebido { background-color: #dc3545; }
        .bg-analise { background-color: #ff9800; }

        .btn-editar-card {
            width: 100%;
            padding: 12px;
            background: #254c90;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 15px;
            margin-top: 15px;
            cursor: pointer;
            transition: 0.3s;
            text-transform: uppercase;
        }
        .btn-editar-card:hover { background: #0052a5; }

        /* ====== MODAL DE EDIÇÃO (Original c/ CSS ajustado) ====== */
        #modalEditar {
            display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.6); align-items: center; justify-content: center; z-index: 1000;
        }
        #modalEditar .modal-content {
            background: #fff; padding: 30px; border-radius: 12px; min-width: 320px; max-width: 800px;
            width: 90vw; box-shadow: 0 10px 30px rgba(0,0,0,0.3); position: relative; max-height: 90vh; overflow-y: auto;
        }
        #modalEditar h3 { margin-top: 0; color: #254c90; font-size: 1.6rem; text-align: center; margin-bottom: 20px; text-transform: uppercase; }
        .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        #modalEditar form label { display: block; margin-bottom: 5px; color: #254c90; font-weight: bold; font-size: 14px; }
        #modalEditar form input, #modalEditar form select {
            width: 100%; padding: 10px; border: 1px solid #bfc9da; border-radius: 6px; font-size: 15px; margin-bottom: 12px; box-sizing: border-box;
        }
        .campo-travado { background-color: #e9ecef; cursor: not-allowed; color: #666; }
        .modal-actions { display: flex; justify-content: space-between; margin-top: 20px; gap: 10px; }
        .modal-actions button { flex: 1; padding: 12px; border: none; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer; text-transform: uppercase;}
        .btn-salvar { background: #254c90; color: #fff; }
        .btn-excluir { background: #dc3545; color: #fff; }
        .btn-cancelar { background: #e0e4ea; color: #333; }
        .close-x { position: absolute; top: 15px; right: 20px; font-size: 24px; color: #666; background: none; border: none; cursor: pointer; }

        @media (max-width: 768px) {
            .header-agendamentos { flex-direction: column; text-align: center; gap: 15px; padding: 15px; }
            .modal-grid { grid-template-columns: 1fr; gap: 0; }
            .modal-actions { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-agendamentos">
        <h2>Meus Agendamentos</h2>
        <button onclick="window.location.href='pagina-publica.php'" class="btn-voltar">
            Voltar ao Calendário
        </button>
    </div>

    <input type="text" id="inputPesquisa" class="search-bar" placeholder="🔍 Pesquisar por placa, fornecedor, carga ou motorista..." onkeyup="filtrarCards()">

    <?php if (empty($agendamentos)): ?>
        <div style="text-align: center; padding: 40px; color: #666; font-size: 18px;">
            Nenhum agendamento encontrado para "<?= htmlspecialchars($nomeResponsavel) ?>".
        </div>
    <?php else: ?>
        <div class="cards-grid" id="gridCards">
            <?php foreach ($agendamentos as $agendamento): 
                // Define a cor do badge baseado no status
                $corBadge = 'bg-receber';
                if ($agendamento['status'] == 'Liberado') $corBadge = 'bg-liberado';
                else if ($agendamento['status'] == 'Recebendo') $corBadge = 'bg-recebendo';
                else if ($agendamento['status'] == 'Recebido') $corBadge = 'bg-recebido';
                else if ($agendamento['status'] == 'Em Analise') $corBadge = 'bg-analise';
            ?>
                <div class="agendamento-card" data-search="<?= strtolower($agendamento['placa'].' '.$agendamento['fornecedor'].' '.$agendamento['tipo_carga'].' '.$agendamento['nome_motorista']) ?>">
                    <div class="card-header">
                        <span class="card-data">📅 <?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?></span>
                        <span class="card-placa"><?= htmlspecialchars($agendamento['placa']) ?></span>
                    </div>
                    <div class="card-body">
                        <p><strong>Caminhão:</strong> <span style="text-transform: uppercase;"><?= htmlspecialchars($agendamento['tipo_caminhao']) ?></span></p>
                        <p><strong>Carga:</strong> <?= htmlspecialchars($agendamento['tipo_carga']) ?> (<?= htmlspecialchars($agendamento['tipo_mercadoria']) ?>)</p>
                        <p><strong>Fornecedor:</strong> <?= htmlspecialchars($agendamento['fornecedor']) ?></p>
                        <p><strong>Motorista:</strong> <?= htmlspecialchars($agendamento['nome_motorista']) ?></p>
                        <p><strong>Contato:</strong> <?= htmlspecialchars($agendamento['numero_contato']) ?></p>
                        <p><strong>Logística:</strong> <?= htmlspecialchars($agendamento['quantidade_paletes']) ?> Paletes | <?= htmlspecialchars($agendamento['quantidade_volumes']) ?> Volumes</p>
                        
                        <div class="card-status-badge <?= $corBadge ?>">
                            Status: <?= htmlspecialchars($agendamento['status']) ?>
                        </div>
                    </div>
                    
                    <?php /* <button class="btn-editar-card" onclick='abrirModalEdicaoCard(<?= json_encode($agendamento) ?>)'>
                        ✏️ Editar Informações
                    </button>
                    */ ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="modalEditar">
  <div class="modal-content">
    <button class="close-x" onclick="fecharModalEditar()" title="Fechar">&times;</button>
    <h3>Editar Agendamento</h3>
    <form id="formEditarAgendamento" autocomplete="off">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-grid">
        <div>
          <label>Tipo do Caminhão:</label>
            <select name="tipo_caminhao" id="edit_tipo_caminhao" class="campo-travado" disabled>
              <option value="truck">Truck</option>
              <option value="toco">Toco</option>
              <option value="carreta">Carreta</option>
              <option value="utilitarios">Utilitários</option>
            </select>
          <label>Tipo de Carga:</label>
            <input type="text" name="tipo_carga" id="edit_tipo_carga" class="campo-travado" readonly>
          <label>Tipo de Mercadoria:</label>
            <input type="text" name="tipo_mercadoria" id="edit_tipo_mercadoria" required>
          <label>Fornecedor:</label>
            <input type="text" name="fornecedor" id="edit_fornecedor" required>
          <label>Data original:</label>
            <input type="date" name="data_agendamento" id="edit_data_agendamento" class="campo-travado" readonly>
          
          <label>Qtde de Paletes:</label>
            <input type="number" name="quantidade_paletes" id="edit_quantidade_paletes" min="0" required>
          <label>Qtde de Volumes:</label>
            <input type="number" name="quantidade_volumes" id="edit_quantidade_volumes" min="0" required>
        </div>
        <div>
          <label>Placa:</label>
            <input type="text" name="placa" id="edit_placa" style="text-transform: uppercase;" required>
          <label>Comprador:</label>
            <input type="text" name="comprador" id="edit_comprador" required>
          <label>Nome Motorista:</label>
            <input type="text" name="nome_motorista" id="edit_nome_motorista" required>
          <label>CPF Motorista:</label>
            <input type="text" name="cpf_motorista" id="edit_cpf_motorista" required>
          <label>Número de Contato:</label>
            <input type="text" name="numero_contato" id="edit_numero_contato" required>
          <label>Tipo de Recebimento:</label>
            <input type="text" name="tipo_recebimento" id="edit_tipo_recebimento" class="campo-travado" readonly>
        </div>
      </div>
      <div class="modal-actions">
        <button type="submit" class="btn-salvar">Salvar Alterações</button>
        <button type="button" class="btn-excluir" onclick="excluirAgendamento(document.getElementById('edit_id').value)">Excluir Agendamento</button>
        <button type="button" class="btn-cancelar" onclick="fecharModalEditar()">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
    // 1. Pesquisa Inteligente dos Cards (Substitui os filtros das colunas)
    function filtrarCards() {
        const termo = document.getElementById('inputPesquisa').value.toLowerCase();
        const cards = document.querySelectorAll('.agendamento-card');
        
        cards.forEach(card => {
            const textoCard = card.getAttribute('data-search');
            if (textoCard.includes(termo)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // 2. A Mágica da Injeção de Dados (Lê o JSON do PHP e joga no Modal)
    function abrirModalEdicaoCard(agendamento) {
        document.getElementById('edit_id').value = agendamento.id;
        document.getElementById('edit_tipo_caminhao').value = agendamento.tipo_caminhao.toLowerCase();
        document.getElementById('edit_tipo_carga').value = agendamento.tipo_carga;
        document.getElementById('edit_tipo_mercadoria').value = agendamento.tipo_mercadoria;
        document.getElementById('edit_fornecedor').value = agendamento.fornecedor;
        document.getElementById('edit_data_agendamento').value = agendamento.data_agendamento;
        document.getElementById('edit_quantidade_paletes').value = agendamento.quantidade_paletes;
        document.getElementById('edit_quantidade_volumes').value = agendamento.quantidade_volumes;
        document.getElementById('edit_placa').value = agendamento.placa;
        document.getElementById('edit_comprador').value = agendamento.comprador;
        document.getElementById('edit_nome_motorista').value = agendamento.nome_motorista;
        document.getElementById('edit_cpf_motorista').value = agendamento.cpf_motorista;
        
        // Correção do ID do banco para o HTML
        document.getElementById('edit_numero_contato').value = agendamento.numero_contato || agendamento.celular_motorista || '';
        document.getElementById('edit_tipo_recebimento').value = agendamento.tipo_recebimento;

        document.getElementById('modalEditar').style.display = 'flex';
    }

    function fecharModalEditar() {
        document.getElementById('modalEditar').style.display = 'none';
    }

    // 3. Exclusão Segura
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
                    location.reload();
                } else {
                    alert("Erro ao excluir: " + data.message);
                }
            }).catch(() => alert("Erro na conexão com o servidor."));
        }
    }

    // 4. Envio Seguro (Bypass nos campos readonly para o PHP receber os dados)
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('formEditarAgendamento').onsubmit = function(e) {
            e.preventDefault();
            
            // Destrava os campos apenas no milissegundo do envio para o FormData ler
            const camposDisabled = this.querySelectorAll('select[disabled], input[readonly]');
            camposDisabled.forEach(campo => campo.removeAttribute('disabled'));

            const formData = new FormData(this);

            // Trava de novo imediatamente
            camposDisabled.forEach(campo => {
                if(campo.tagName === 'SELECT') campo.setAttribute('disabled', 'disabled');
            });

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
            .catch(() => alert('Erro ao salvar as alterações.'));
        };
    });
</script>
</body>
</html>