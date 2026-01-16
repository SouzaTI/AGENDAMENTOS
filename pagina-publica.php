<!--
 * pagina-publica.php
 *
 * Página pública do calendário de agendamentos de recebimento.
 *
 * FUNCIONALIDADE:
 * - Exibe um calendário interativo para consulta e solicitação de agendamento de recebimento.
 * - Permite que o usuário se identifique informando o nome do responsável antes de acessar o calendário.
 * - Mostra legenda explicativa sobre os tipos de dias (bloqueado, disponível, parcial, total).
 * - Permite ao usuário iniciar um novo agendamento preenchendo um formulário detalhado (dados do transporte, motorista, fornecedor, etc).
 * - Exige consentimento do usuário antes de liberar o formulário de agendamento.
 * - Permite consultar os próprios agendamentos já realizados.
 * - Interface responsiva, com legendas adaptadas para desktop e mobile.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Utiliza JavaScript para controlar a exibição do calendário, formulários e modais.
 * - Salva o nome do responsável no sessionStorage para uso em múltiplas interações.
 * - O formulário de agendamento só é exibido após consentimento explícito do usuário.
 * - O calendário é alimentado via AJAX (js/calendario.js) consultando endpoints PHP para saber a disponibilidade de cada dia.
 * - O formulário de agendamento envia os dados para processar_agendamento.php.
 * - O botão "Ver Agendamentos" direciona para a visão pública dos agendamentos do responsável.
 *
 * REQUISITOS:
 * - Requer os arquivos js/calendario.js, processar_agendamento.php e visao-agendamentos-publico.php.
 * - Utiliza imagens e CSS próprios para layout e identidade visual.
-->

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Calendário de Reservas</title>
  <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
  <link rel="stylesheet" href="css/estilos-calendario.css" />
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: url('./img/background.png') no-repeat center center fixed;
      background-size: cover;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 20px;
      color: #254c90;
    }

    h1 {
    color: #ffffff; /* Cor branca */
  }

    /* Esconde a legenda mobile no desktop, mostra a vertical tradicional */
.legend-mobile { display: none; }
.legend-desktop { display: block; }

/* Cores dos quadradinhos */
.legend-blocked { background: gray; }
.legend-available { background: #28a745; }
.legend-partial { background: orange; }
.legend-full { background: #dc3545; }

/* Estilo tradicional da legenda vertical */
.calendar-legend {
  position: static;
  margin: 0 auto 12px auto;
  background: #fff;
  padding: 6px 12px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.10);
  font-size: 12px;
  max-width: 220px;
  border: 1px solid #e3eafc;
}
.calendar-legend ul {
  list-style: none;
  padding: 0;
  margin: 0;
}
.calendar-legend li {
  display: flex;
  align-items: center;
  margin-bottom: 3px;
}
.calendar-legend li:last-child {
  margin-bottom: 0;
}
.calendar-legend span {
  display: inline-block;
  width: 14px;
  height: 14px;
  margin-right: 7px;
  border-radius: 3px;
}

/* MOBILE: só mostra a horizontal, esconde a vertical */
@media (max-width: 600px) {
  .legend-desktop { display: none; }


  @media (max-width: 600px) {
  .legend-mobile {
    display: block;
    width: 76vw;           /* Ajuste a largura aqui */
    max-width: 420px;      /* Limite máximo */
    min-width: 0;
    margin: 0 auto 12px auto;
    padding: 8px 10px;     /* Ajuste o padding aqui */
    border-radius: 12px;
    font-size: 12px;
    box-sizing: border-box;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.10);
    border: 1px solid #e3eafc;
  }
  .legend-items {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    width: 100%;
    justify-content: center;
    padding-right: 0;
  }
  .legend-logo-mobile {
    display: block;
    max-width: 80px;
    width: 80px;
    height: auto;
    margin-left: 14px;
    margin-right: 4px;
    flex-shrink: 0;
    align-self: center;
  }
  .legend-items div {
    display: flex;
    align-items: center;
    white-space: nowrap;
    font-size: 12px;
    margin-bottom: 0;
    gap: 2px;
  }
  .legend-items span {
    width: 13px;
    height: 13px;
    margin-right: 3px;
    border-radius: 2px;
    display: inline-block;
  }
}
  // ...existing code...
  .legend-mobile {
    display: block;
    max-width: 99vw;
    width: 99vw;
    padding: 6px 8px;
    border-radius: 10px;
    font-size: 11px;
    box-sizing: border-box;
    margin-bottom: 12px;
  }
  .legend-items {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    width: 100%;
    justify-content: center;
    padding-right: 0;
  }
  .legend-logo-mobile {
    display: block;
    max-width: 70px;
    width: 70px;
    height: auto;
    margin-left: 18px;
    margin-right: 4px;
    flex-shrink: 0;
    align-self: center;
  }
  .legend-items div {
    display: flex;
    align-items: center;
    white-space: nowrap;
    font-size: 11px;
    margin-bottom: 0;
    gap: 2px;
  }
  .legend-items span {
    width: 13px;
    height: 13px;
    margin-right: 3px;
    border-radius: 2px;
    display: inline-block;
  }
}

   /* Estilos do Modal de Gerenciamento */
  #modalGerenciamento {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7); /* Fundo mais escuro */
    justify-content: center;
    align-items: center;
    z-index: 1000;
    transition: opacity 0.3s ease;
  }

  #modalGerenciamento .modal-content {
    background: #fff;
    padding: 20px;
    width: 90%;
    max-width: 900px; /* Largura máxima do modal */
    max-height: 90%;
    overflow-y: auto;
    border-radius: 12px;
    position: relative;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    animation: fadeIn 0.3s ease;
  }

  #modalGerenciamento .modal-content iframe {
    width: 100%;
    height: 600px; /* Ajustar a altura */
    border: none;
  }

  #modalGerenciamento .close {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 24px;
    cursor: pointer;
    z-index: 10;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  #identificacaoContainer {
  background: rgba(255,255,255,0.97);
  border-radius: 18px;
  box-shadow: 0 8px 32px rgba(37,76,144,0.13);
  padding: 38px 32px 28px 32px;
  max-width: 380px;
  margin: 60px auto 30px auto;
  text-align: center;
  border: 2px solid #e3eafc;
  animation: fadeIn 0.5s;
}

#identificacaoContainer h2 {
  color: #254c90;
  font-size: 1.5rem;
  margin-bottom: 18px;
  font-weight: 700;
  letter-spacing: 1px;
}

#identificacaoContainer label {
  color: #254c90;
  font-weight: 500;
  font-size: 1.08rem;
  margin-bottom: 10px;
  display: block;
}

#identificacaoContainer input[type="text"] {
  width: 90%;
  padding: 12px 10px;
  border-radius: 8px;
  border: 1.5px solid #bfc9da;
  font-size: 1.08rem;
  margin-bottom: 18px;
  background: #f4f6f9;
  color: #254c90;
  outline: none;
  transition: border 0.2s;
}

#identificacaoContainer input[type="text"]:focus {
  border: 1.5px solid #254c90;
  background: #fff;
}

#identificacaoContainer button[type="submit"] {
  padding: 12px 28px;
  background: linear-gradient(90deg, #254c90 60%, #17a2b8 100%);
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  font-size: 1.08rem;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(37,76,144,0.10);
  transition: background 0.2s, color 0.2s;
}

#identificacaoContainer button[type="submit"]:hover {
  background: linear-gradient(90deg, #17a2b8 60%, #254c90 100%);
  color: #fffbe7;
}

@media (max-width: 600px) {
  .calendar-container {
    max-width: 98vw;
    padding: 6px 2vw 10px 2vw;
    border-radius: 12px;
  }
  #calendarDays {
    gap: 0;
    width: 100%;
    /* Garante que o grid ocupe toda a largura do container */
  }
  .day-cell {
    width: 10vw;
    height: 10vw;
    font-size: 1rem;
    border-radius: 4px;
    padding: 0;
    margin: 0;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: center;
  }
    .legend-logo-mobile {
        display: none;
        margin: 8px auto 0 auto;
        max-width: 60px;
        height: auto;
    }
    @media (max-width: 600px) {
      .legend-logo-mobile {
        display: block;
        max-width: 90px;      /* aumenta o tamanho da logo */
        width: 90px;
        height: auto;
        margin-left: 18px;
        margin-right: 4px;
        flex-shrink: 0;
        align-self: flex-start; /* alinha a logo ao topo do container */
        margin-top: -2px;       /* sobe um pouco a logo */
      }
    }
  }

  .logo-topo {
  max-width: 180px;
  width: 100%;
  height: auto;
  display: inline-block;
}
@media (max-width: 600px) {
  .logo-topo {
    max-width: 140px;
  }
}

#placa {
  text-transform: uppercase;
}

#identificacaoContainer,
.calendar-container,
#btnVerAgendamentos {
  display: none;
}

/* --- TAMANHO DA IMAGEM DO CONSENTIMENTO NO MODAL --- */
.img-consentimento {
  max-width: 420px;   /* <-- AJUSTE O TAMANHO DA IMAGEM NO DESKTOP AQUI */
  width: 95%;
  margin-bottom: 18px;
  display: block;
  margin-left: auto;
  margin-right: auto;
}

/* --- TAMANHO DA IMAGEM DO CONSENTIMENTO NO MOBILE --- */
@media (max-width: 600px) {
  .img-consentimento {
    max-width: 320px;   /* <-- AJUSTE O TAMANHO DA IMAGEM NO MOBILE AQUI */
    width: 90vw;
    margin-bottom: 14px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.10);
    display: block;
    margin-left: auto;
    margin-right: auto;
  }
}


/* --- CENTRALIZAÇÃO DO CONSENTIMENTO (checkbox + texto) --- */

/* Wrapper externo: centraliza o bloco do consentimento */
.consentimento-label-wrapper {
  width: 100%;                /* <-- Pode ajustar para menos se quiser menos largura */
  display: flex;              /* <-- Mantém o flexbox para centralizar */
  justify-content: center;    /* <-- Centraliza horizontalmente */
}

/* Bloco interno: alinha checkbox e texto lado a lado e centraliza */
.consentimento-label {
  display: flex;              /* <-- Mantém o flexbox para alinhar checkbox e texto */
  align-items: center;        /* <-- Alinha verticalmente */
  gap: 8px;                   /* <-- Espaço entre checkbox e texto (aumente/diminua se quiser) */
  font-size: 1rem;            /* <-- Tamanho do texto (altere para maior/menor se quiser) */
  flex-wrap: nowrap;         /* <-- Evita quebrar texto */
  white-space: nowrap;       /* <-- Garante que o texto fique em uma linha */
}

/* --- MOBILE: Ajustes específicos para telas pequenas --- */
@media (max-width: 600px) {
  .consentimento-label-wrapper {
    width: 100%;              /* <-- Pode ajustar para menos se quiser menos largura no mobile */
    display: flex;
    justify-content: center;
    /* margin-bottom: 10px;   <-- Descomente se quiser mais espaço abaixo no mobile */
  }
  .consentimento-label {
    display: flex;
    align-items: center;
    justify-content: center;  /* <-- ESSENCIAL para centralizar no mobile */
    font-size: 1rem;          /* <-- Tamanho do texto no mobile */
    gap: 8px;                 /* <-- Espaço entre checkbox e texto no mobile */
    width: auto;              /* <-- Não força largura total */
    text-align: center;       /* <-- Centraliza texto se quebrar linha */
    margin: 0 auto;           /* <-- Centraliza bloco se necessário */
  }
  #checkboxConsentimentoModal {
    transform: scale(1.2);    /* <-- Tamanho do checkbox no mobile */
    accent-color: #254c90;    /* <-- Cor do checkbox */
  }
}
  </style>
</head>
<body>
  <!-- Logo acima do título -->
  <div style="width:100%;text-align:center;margin-bottom:-16px;">
    <img src="img/Logo.svg" alt="Logo Souza" class="logo-topo">
  </div>
  <h1>Agendamentos</h1>

  <!-- Legenda do Calendário -->
  <!-- Legenda Desktop (vertical, tradicional) -->
  <div class="calendar-legend legend-desktop">
    <ul>
      <li><span class="legend-blocked"></span> Dia Bloqueado</li>
      <li><span class="legend-available"></span> Dia Disponível</li>
      <li><span class="legend-partial"></span> Dia Parcialmente Agendado</li>
      <li><span class="legend-full"></span> Dia Totalmente Agendado</li>
    </ul>
  </div>

  <!-- Legenda Mobile (horizontal, logo à direita) -->
  <div class="calendar-legend legend-mobile">
    <div class="legend-items">
      <div><span class="legend-blocked"></span> Bloqueado</div>
      <div><span class="legend-available"></span> Disponível</div>
      <div><span class="legend-partial"></span> Parcial</div>
      <div><span class="legend-full"></span> Total</div>
    </div>
  </div>

  <!-- Calendário -->
  <div class="calendar-container">
    <div class="calendar-header">
      <button id="prevMonth" class="nav-button">&lt;</button>
      <div class="month-year" id="monthYear"></div>
      <button id="nextMonth" class="nav-button">&gt;</button>
    </div>
    <div class="calendar-weekdays">
      <div>Dom</div>
      <div>Seg</div>
      <div>Ter</div>
      <div>Qua</div>
      <div>Qui</div>
      <div>Sex</div>
      <div>Sáb</div>
    </div>

    <div id="calendar">
        <div id="calendarDays"></div>
    </div>
  </div>

  <!-- Modal de Agendamento -->
<div class="modal" id="modal">
    <div class="modal-content">
      <!-- Botão de fechar (X) -->
      <span class="close" style="position: absolute; top: 10px; right: 10px; font-size: 24px; cursor: pointer;">&times;</span>
      
      <!-- Consentimento dentro do modal -->
      <div id="consentimentoModal" style="text-align:center; margin-bottom:24px;">
        <img src="img/recebimento.png" alt="Recebimento" class="img-consentimento">
        <div style="margin-bottom:16px;">
          <div class="consentimento-label-wrapper">
            <div class="consentimento-label">
              <input type="checkbox" id="checkboxConsentimentoModal">
              <span>Li e estou de acordo</span>
            </div>
          </div>
        </div>
        <button id="btnLiberarAgendamentoModal" disabled style="
          padding: 10px 24px;
          background: #254c90;
          color: #fff;
          border: none;
          border-radius: 8px;
          font-size: 1rem;
          font-weight: bold;
          cursor: pointer;
          opacity: 0.7;
          transition: opacity 0.2s;
        ">Continuar</button>
      </div>

    <h2 id="tituloTransporte" style="display:none;">Informações do Transporte</h2>
<form id="reservationForm" action="processar_agendamento.php" method="POST" style="display:none;">
        <input type="hidden" name="dataAgendamento" id="dataAgendamento">
        <input type="hidden" name="nome_responsavel" id="hiddenNomeResponsavel">
        <input type="hidden" name="origem" value="publica">

        <label for="nomeResponsavelModal">Nome do Responsável</label>
        <input type="text" id="nomeResponsavelModal" readonly style="background:#f3f3f3; font-weight:bold; margin-bottom:10px;">

        <div class="form-grid">
          <div>
            <label for="tipoCaminhao">Tipo do Caminhão</label>
            <select id="tipoCaminhao" name="tipoCaminhao" required>
              <option value="">Selecione</option>
              <option value="truck">Truck</option>
              <option value="toco">Toco</option>
              <option value="carreta">Carreta</option>
            </select>

            <label for="tipoCarga">Tipo de Carga</label>
            <select id="tipoCarga" name="tipoCarga" required>
              <option value="">Selecione</option>
              <option value="Batida">Batida</option>
              <option value="Paletizada">Paletizada</option>
            </select>

            <label for="tipoMercadoria">Tipo de Mercadoria</label>
            <input type="text" id="tipoMercadoria" name="tipoMercadoria" required>

            <label for="fornecedor">Fornecedor</label>
            <input type="text" id="fornecedor" name="fornecedor" required>

            <label for="comprador">Nome do Comprador</label>
            <input type="text" id="comprador" name="comprador" required>
          </div>
          <div>
            <label for="quantidadePaletes">Quantidade de Paletes</label>
            <input type="number" id="quantidadePaletes" name="quantidadePaletes" min="0" required>

            <label for="quantidadeVolumes">Quantidade de Volumes</label>
            <input type="number" id="quantidadeVolumes" name="quantidadeVolumes" min="0" required>

            <label for="placa">Placa</label>
            <input type="text" id="placa" name="placa" pattern="[A-Z]{3}[0-9][A-Z0-9][0-9]{2}" required>
            
            <!-- Movido para a segunda coluna após Placa -->
            <label for="quantidadeNotas">Quantidade de Notas Fiscais</label>
            <input type="number" id="quantidadeNotas" name="quantidadeNotas" min="1" placeholder="Informe a quantidade" required>
          </div>
        </div> 

        <label for="nomeMotorista">Nome do Motorista</label>
        <input type="text" id="nomeMotorista" name="nomeMotorista" required>

        <label for="cpfMotorista">CPF do Motorista</label>
        <input type="text" id="cpfMotorista" name="cpfMotorista" pattern="\d{11}" maxlength="11" required>

        <label for="numeroContato">Número de Contato</label>
        <input type="tel" id="numeroContato" name="numeroContato" required>

        <label for="tipoRecebimento">Tipo de Recebimento</label>
        <select id="tipoRecebimento" name="tipoRecebimento" required>
          <option value="">Selecione</option>
          <option value="Porte Pequeno">Porte Pequeno</option>
          <option value="Porte Médio">Porte Médio</option>
          <option value="Porte Grande">Porte Grande</option>
        </select>

        <button type="submit">Confirmar Agendamento</button>
      </form>
    </div>
  </div>

  <!-- Formulário de identificação do responsável -->
<div id="identificacaoContainer" style="margin-bottom:30px;">
    <h2>👤 Identifique-se para agendar</h2>
  <form id="identificacaoForm">
    <label for="nomeResponsavel">Nome do responsável pelo agendamento:</label>
    <input type="text" id="nomeResponsavel" required>
    <button type="submit">Continuar</button>
  </form>
</div>

  <!-- Botão para Visão dos Agendamentos -->
  <div style="margin-top: 20px; text-align: center;">
    <button
      id="btnVerAgendamentos"
      onclick="window.location.href='visao-agendamentos-publico.php?nome_responsavel=' + encodeURIComponent(sessionStorage.getItem('nomeResponsavel') || '')"
      style="display:none;
        padding: 10px 20px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        box-shadow: 1px 1px 5px rgba(0, 0, 0, 0.2);
        transition: background-color 0.3s ease;
      "
    >
      Ver Agendamentos
    </button>
  </div>
  
  <!-- Modal para inserção de notas fiscais (visual melhorado) -->
<div id="modalNotasFiscais" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:9999; overflow:auto; padding:0; align-items:center; justify-content:center;">
  <div style="background:#fff; border-radius:16px; width:90%; max-width:550px; margin:0 auto; padding:35px; position:relative; color:#254c90; box-shadow:0 10px 30px rgba(0,0,0,0.25); animation:fadeIn 0.3s ease;">
    <button id="fecharModalNotas" class="fechar-modal" style="position:absolute; top:18px; right:18px; background:none; border:none; font-size:28px; cursor:pointer; color:#999; transition:color 0.2s, transform 0.2s; height:40px; width:40px; display:flex; align-items:center; justify-content:center; border-radius:50%;">&times;</button>
    <h3 style="margin-top:0; margin-bottom:20px; color:#254c90; font-size:22px;">Informar Números das Notas Fiscais</h3>
    <div id="camposNotasFiscais" style="max-height:55vh; overflow-y:auto; padding-right:10px;"></div>
    <div id="mensagemErro" style="color:#dc3545; margin-top:10px; font-weight:500; display:none;"></div>
    <div style="text-align:right; margin-top:20px;">
      <button onclick="confirmarNotasFiscais()" style="background:#254c90; color:#fff; padding:10px 20px; border:none; border-radius:5px; cursor:pointer;">Confirmar</button>
    </div>
  </div>
</div>

<script>
// Sistema de Notas Fiscais
document.getElementById('quantidadeNotas').addEventListener('change', function() {
  const quantidade = parseInt(this.value);
  if (quantidade > 0) {
    gerarCamposNotasFiscais(quantidade);
    document.getElementById('modalNotasFiscais').style.display = 'flex';
  }
});

// Fechar com o botão X
document.getElementById('fecharModalNotas').addEventListener('click', function() {
  document.getElementById('modalNotasFiscais').style.display = 'none';
  document.getElementById('mensagemErro').style.display = 'none';
});

function gerarCamposNotasFiscais(quantidade) {
  let html = '';
  for (let i = 1; i <= quantidade; i++) {
    html += `
      <div style="margin-bottom:15px;">
        <label for="notaFiscal${i}">Nota Fiscal ${i}</label>
        <input type="text" 
               id="notaFiscal${i}" 
               name="notasFiscais[]" 
               placeholder="Digite o número da nota fiscal" 
               pattern="[0-9]+"
               inputmode="numeric"
               onkeypress="return event.charCode >= 48 && event.charCode <= 57"
               required>
      </div>
    `;
  }
  document.getElementById('camposNotasFiscais').innerHTML = html;
  
  // Adiciona validação para todos os campos de notas fiscais
  document.querySelectorAll('input[name="notasFiscais[]"]').forEach(input => {
    input.addEventListener('input', function(e) {
      // Remove qualquer caractere que não seja número
      this.value = this.value.replace(/[^0-9]/g, '');
    });
  });
}

function confirmarNotasFiscais() {
  const notasFiscais = [];
  const inputs = document.querySelectorAll('input[name="notasFiscais[]"]');
  const mensagemErro = document.getElementById('mensagemErro');
  let camposVazios = false;
  
  // Verificar se todos os campos estão preenchidos
  inputs.forEach(input => {
    if (input.value.trim() === '') {
      camposVazios = true;
      input.style.borderColor = '#dc3545';
    } else {
      input.style.borderColor = '#dbe4f3';
      notasFiscais.push(input.value.trim());
    }
  });
  
  // Se houver campos vazios, mostrar mensagem e não fechar o modal
  if (camposVazios) {
    mensagemErro.style.display = 'block';
    mensagemErro.textContent = `Preencha todos os ${inputs.length} campos de notas fiscais.`;
    return;
  }
  
  mensagemErro.style.display = 'none';
  
  // Armazena em um campo hidden que será enviado com o formulário
  let inputHidden = document.getElementById('notasFiscaisJSON');
  if (!inputHidden) {
    inputHidden = document.createElement('input');
    inputHidden.type = 'hidden';
    inputHidden.name = 'notasFiscaisJSON';
    inputHidden.id = 'notasFiscaisJSON';
    
    // Certifique-se de que este é o ID correto do seu formulário
    const form = document.getElementById('reservationForm'); 
    if (form) {
        form.appendChild(inputHidden);
    } else {
        console.error('Formulário não encontrado');
    }
  }
  
  inputHidden.value = JSON.stringify(notasFiscais);
  document.getElementById('modalNotasFiscais').style.display = 'none';
}
</script>

  <!-- Passando usuário para o JS -->
  <script>
    const usuario = "<?php echo $usuario; ?>";
  </script>
  <script src="js/feriados.js"></script>
  <script src="js/calendario.js"></script>
  <script>
document.addEventListener('DOMContentLoaded', function() {
  const identificacaoContainer = document.getElementById('identificacaoContainer');
  const calendarContainer = document.querySelector('.calendar-container');
  const nomeResponsavel = sessionStorage.getItem('nomeResponsavel');
  const btnVerAgendamentos = document.getElementById('btnVerAgendamentos');

  // Mostra o formulário de identificação se não houver nome salvo
  if (!nomeResponsavel) {
    calendarContainer.style.display = 'none';
    identificacaoContainer.style.display = 'block';
    btnVerAgendamentos.style.display = 'none';
  } else {
    identificacaoContainer.style.display = 'none';
    calendarContainer.style.display = 'block';
    btnVerAgendamentos.style.display = 'inline-block';
  }

  // Ao submeter o formulário de identificação
  document.getElementById('identificacaoForm').onsubmit = function(e) {
    e.preventDefault();
    const nome = document.getElementById('nomeResponsavel').value.trim();
    if (nome) {
      sessionStorage.setItem('nomeResponsavel', nome);
      identificacaoContainer.style.display = 'none';
      calendarContainer.style.display = 'block';
      btnVerAgendamentos.style.display = 'inline-block';
    }
  };

  // Sempre que abrir o modal de agendamento, preencha o campo hidden
  function preencherNomeResponsavelNoModal() {
    const nome = sessionStorage.getItem('nomeResponsavel') || '';
    const hidden = document.getElementById('hiddenNomeResponsavel');
    const visivel = document.getElementById('nomeResponsavelModal');
    if (hidden) hidden.value = nome;
    if (visivel) visivel.value = nome;
  }

  // Exemplo: sempre que clicar em um dia do calendário, preencha o campo hidden
  document.getElementById('calendarDays').addEventListener('click', function(e) {
    preencherNomeResponsavelNoModal();
  });

  // Garante que o campo hidden está preenchido antes de enviar
  document.getElementById('reservationForm').addEventListener('submit', function() {
    preencherNomeResponsavelNoModal();
    console.log('Nome responsável enviado:', document.getElementById('hiddenNomeResponsavel').value);
  });

  // Sempre que o modal for exibido, preenche o nome do responsável
  const modal = document.getElementById('modal');
  const observer = new MutationObserver(() => {
    if (modal.style.display === 'block') {
      preencherNomeResponsavelNoModal();
    }
  });
  observer.observe(modal, { attributes: true, attributeFilter: ['style'] });
});

document.getElementById('placa').addEventListener('input', function() {
  this.value = this.value.toUpperCase();
});
  </script>
  <script>
document.addEventListener('DOMContentLoaded', function() {
  // Fecha o modal ao clicar no X
  var modal = document.getElementById('modal');
  var closeBtn = modal.querySelector('.close');
  closeBtn.addEventListener('click', function() {
    modal.style.display = 'none';
  });

  // Consentimento
  var consentimentoModal = document.getElementById('consentimentoModal');
  var checkboxModal = document.getElementById('checkboxConsentimentoModal');
  var btnLiberarModal = document.getElementById('btnLiberarAgendamentoModal');
  var tituloTransporte = document.getElementById('tituloTransporte');
  var reservationForm = document.getElementById('reservationForm');

  if (checkboxModal && btnLiberarModal) {
    checkboxModal.addEventListener('change', function() {
      btnLiberarModal.disabled = !this.checked;
      btnLiberarModal.style.opacity = this.checked ? '1' : '0.7';
    });

    btnLiberarModal.addEventListener('click', function() {
      consentimentoModal.style.display = 'none';
      tituloTransporte.style.display = '';
      reservationForm.style.display = '';
    });
  }

  // Sempre que abrir o modal, reseta o consentimento
  if (modal) {
    const observer = new MutationObserver(() => {
      if (modal.style.display === 'block') {
        consentimentoModal.style.display = '';
        tituloTransporte.style.display = 'none';
        reservationForm.style.display = 'none';
        checkboxModal.checked = false;
        btnLiberarModal.disabled = true;
        btnLiberarModal.style.opacity = '0.7';
      }
    });
    observer.observe(modal, { attributes: true, attributeFilter: ['style'] });
  }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('reservationForm');
  if (!form) return;

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;

    // Validação das notas fiscais
    const notasFiscaisJSON = document.getElementById('notasFiscaisJSON');
    if (!notasFiscaisJSON || !notasFiscaisJSON.value) {
      alert('Por favor, informe os números das notas fiscais antes de confirmar o agendamento.');
      const quantidade = parseInt(document.getElementById('quantidadeNotas').value);
      if (quantidade > 0) {
        gerarCamposNotasFiscais(quantidade);
        document.getElementById('modalNotasFiscais').style.display = 'flex';
      }
      return;
    }

    function enviarAgendamento(forcar = false) {
      let dataToSend = new FormData(formElement);
      if (forcar) dataToSend.set('forcarAgendamento', '1');
      fetch('processar_agendamento.php', {
        method: 'POST',
        body: dataToSend
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          alert('Agendamento realizado com sucesso!');
          location.reload();
        } else {
          alert(res.message || 'Erro ao agendar.');
        }
      });
    }

    enviarAgendamento(false);
  });
});
</script>
