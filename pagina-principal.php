<?php
/**
 * pagina-principal.php
 *
 * Página principal do sistema de agendamento de recebimentos (restrita a usuários autenticados).
 *
 * FUNCIONALIDADE:
 * - Exibe um calendário interativo para consulta e solicitação de agendamento de recebimento.
 * - Permite ao usuário logado realizar novos agendamentos preenchendo formulário detalhado.
 * - Mostra legenda explicativa sobre os tipos de dias (bloqueado, disponível, parcial, total).
 * - Permite consultar todos os agendamentos já realizados (botão "Ver Agendamentos").
 * - Usuários "admin" e "marlon" podem acessar o gerenciamento de limites diários do calendário.
 * - Possui botão de logout para encerrar a sessão.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Verifica se o usuário está autenticado via sessão; caso contrário, redireciona para o login.
 * - Utiliza JavaScript para controlar a exibição do calendário, formulários e modais.
 * - O formulário de agendamento envia os dados para processar_agendamento.php.
 * - O calendário é alimentado via AJAX (js/calendario.js) consultando endpoints PHP para saber a disponibilidade de cada dia.
 * - O modal de gerenciamento de limites diários é exibido apenas para usuários autorizados.
 *
 * REQUISITOS:
 * - Requer autenticação de usuário (sessão iniciada).
 * - Requer os arquivos js/calendario.js, processar_agendamento.php, visao-agendamentos.php e gerenciamento-calendario.php.
 * - Utiliza imagens e CSS próprios para layout e identidade visual.
 */

session_start();

// Verifica se o usuário está logado
$usuario = $_SESSION['usuario'] ?? null;

if (!$usuario) {
    header("Location: login.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $usuario = $_SESSION['usuario'] ?? 'desconhecido';
    $acao = $_POST['acao'];
    $detalhes = json_encode($_POST);

    registrar_log($usuario, $acao, basename(__FILE__), $detalhes);

}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Agendamentos Comercial Souza</title>
  <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
  <link rel="stylesheet" href="css/estilos-calendario.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <style>
/* 🔹 Estilo Geral */
body {
  margin: 0;
  font-family: 'Segoe UI', Arial, sans-serif;
  background: url('./img/background.png') no-repeat center center fixed;
  background-size: cover;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px;
  color: #254c90;
}

h1 {
  color: #fff;
}

/* 🔹 Logo */
.logo-topo,
img[alt="Logo Comercial Souza"] {
  max-width: 220px;
  width: 100%;
  height: auto;
  display: block;
  margin: 24px auto 20px;
}

@media (max-width: 600px) {
  .logo-topo,
  img[alt="Logo Comercial Souza"] {
    max-width: 160px;
    margin: 20px auto 16px;
  }
}

/* 🔹 Legenda do Calendário */
.calendar-legend {
  background: #ffffffea;
  padding: 12px 20px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  font-size: 14px;
  width: 100%;
  max-width: 460px;
  border: 1px solid #dbe4f3;
  margin-bottom: 18px;
}

.calendar-legend ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  justify-content: center;
  gap: 18px;
}

.calendar-legend li {
  display: flex;
  align-items: center;
}

.calendar-legend span {
  width: 18px;
  height: 18px;
  border-radius: 5px;
  margin-right: 6px;
}

.legend-blocked { background: gray; }
.legend-available { background: #28a745; }
.legend-partial { background: #fd7e14; }
.legend-full { background: #dc3545; }

/* 🔹 Modal de Gerenciamento */
#modalGerenciamento {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  z-index: 1000;
  justify-content: center;
  align-items: center;
}

#modalGerenciamento .modal-content {
  background: #fff;
  padding: 24px 20px;
  width: 95vw;
  max-width: 440px;
  max-height: 92vh;
  border-radius: 20px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.25);
  display: flex;
  flex-direction: column;
  position: relative;
  overflow-y: auto;
  gap: 14px;
  animation: fadeIn 0.4s ease;
}

#modalGerenciamento h2 {
  font-size: 1.4rem;
  color: #254c90;
  text-align: center;
  font-weight: 700;
  margin-bottom: 8px;
}

#modalGerenciamento .close {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 32px;
  height: 32px;
  background: #f1f5f9;
  border: none;
  border-radius: 50%;
  font-size: 1.4rem;
  color: #254c90;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  transition: background 0.3s, transform 0.3s;
}

#modalGerenciamento .close:hover {
  background: #d9534f;
  color: white;
  transform: rotate(90deg);
}

/* 🔹 Inputs e Seletores */
#modalGerenciamento input,
#modalGerenciamento select {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #d3d9e4;
  border-radius: 8px;
  background: #fff;
  font-size: 1rem;
  box-sizing: border-box;
  transition: border-color 0.25s, box-shadow 0.25s;
}

#modalGerenciamento input:focus,
#modalGerenciamento select:focus {
  border-color: #254c90;
  box-shadow: 0 0 0 3px rgba(37, 76, 144, 0.2);
  outline: none;
}

#modalGerenciamento label {
  font-weight: 600;
  color: #254c90;
  margin-bottom: 4px;
  display: block;
}

/* 🔹 Botões */
#modalGerenciamento button[type="submit"],
#modalGerenciamento .modal-content button:not(.close) {
  width: 100%;
  padding: 12px 0;
  background: linear-gradient(to right, #254c90, #1e3b75);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s, transform 0.2s;
}

#modalGerenciamento button[type="submit"]:hover,
#modalGerenciamento .modal-content button:not(.close):hover {
  background: linear-gradient(to right, #1a3666, #162d57);
  transform: translateY(-2px);
}

#modalGerenciamento iframe {
  width: 100%;
  min-height: 220px;
  height: 38vh;
  border: none;
  border-radius: 10px;
  background: #f8fafc;
  flex: 1 1 auto;
}

/* 🔹 Calendário Responsivo */
@media (max-width: 600px) {
  .calendar-container {
    width: 98vw;
    padding: 6px 2vw 12px 2vw;
    border-radius: 10px;
  }
  #calendarDays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
  }
  .day-cell,
  #calendarDays > div {
    width: 28px;
    height: 28px;
    min-width: 28px;
    max-width: 32px;
    font-size: 12px;
    border-radius: 4px;
    padding: 0;
    margin: 0;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .calendar-weekdays > div {
    font-size: 12px;
    padding: 2px 0;
  }
}

/* 🔹 Legenda Mobile */
.legend-mobile {
  display: none;
}

@media (max-width: 768px) {
  .calendar-legend {
    display: none;
  }

  .legend-mobile {
    display: block;
    background: rgba(255, 255, 255, 0.95);
    padding: 12px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    margin-bottom: 20px;
    width: 90vw;
    max-width: 440px;
    margin-left: auto;
    margin-right: auto;
  }

  .legend-mobile .legend-items {
    display: flex;
    justify-content: space-around;
    font-size: 12px;
  }

  .legend-mobile span {
    width: 15px;
    height: 15px;
    margin-right: 5px;
    border-radius: 4px;
  }
}

/* 🔹 Animação */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.96);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* 🔹 Placa em caixa alta */
#placa {
  text-transform: uppercase;
}

/* Tabela do modal de agendamentos do dia */
#tabelaAgendamentosDia th, #tabelaAgendamentosDia td {
    border: 1px solid #dbe4f3;
    padding: 7px 4px;
    text-align: center;
    font-size: 16px;
    white-space: nowrap;
}
#tabelaAgendamentosDia tr:hover {
    background: #f2f6fc;
}
#tabelaAgendamentosDia th {
    background: #0052a5;
    color: #fff;
}

/* 🔹 Ações de Supervisor */
.supervisor-actions-modal {
  position: absolute;
  top: 180px;
  right: 7vw;
  z-index: 10;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.supervisor-actions-content {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 6px 24px rgba(0,0,0,0.13);
  padding: 28px 28px 22px 28px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  min-width: 230px;
  gap: 16px;
}

.supervisor-actions-content button {
  padding: 10px 22px;
  margin-bottom: 6px;
  background: #254c90;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  font-size: 1rem;
  transition: background 0.2s;
}
.supervisor-actions-content button:last-child {
  background: #28a745;
}
.supervisor-actions-content button:hover {
  filter: brightness(1.08);
}
@media (max-width: 900px) {
  .supervisor-actions-modal {
    position: static;
    margin: 20px auto 0 auto;
    align-items: center;
  }
  .supervisor-actions-content {
    min-width: 0;
    width: 90vw;
    align-items: center;
  }
}

/* Modal do Painel de Tempo */
#modalPainelTempo {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.45);
  z-index: 9999;
  align-items: center;
  justify-content: center;
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

#modalPainelTempo h2 {
  color: #254c90;
  margin-bottom: 18px;
}

#modalPainelTempo #painelTempoConteudo {
  font-size: 1.3em;
  margin-bottom: 10px;
}

#modalPainelTempo #painelTempoStatus {
  font-size: 1.1em;
  font-weight: bold;
  margin-top: 18px;
}

/* Estilo para o botão de fechar do modal */
#modalPainelTempo button {
  position: absolute;
  top: 18px;
  right: 22px;
  background: none;
  border: none;
  font-size: 2rem;
  color: #254c90;
  cursor: pointer;
}

.flatpickr-calendar {
  z-index: 10001 !important;
  font-size: 15px;
  min-width: 220px !important;
  width: auto !important;
  left: 50% !important;
  transform: translateX(-50%) !important;
  top: 80px !important; /* Ajuste conforme o topo do seu modal */
}

.flatpickr-calendar .flatpickr-days {
  min-width: 220px !important;
}

.flatpickr-calendar.arrowTop:before,
.flatpickr-calendar.arrowTop:after {
  left: 50% !important;
  transform: translateX(-50%);
}

/* 🔹 Ajustes para Tabela do Modal de Agendamentos (OTIMIZADO) */

/* 🔹 Modal de Agendamentos do Dia - Layout moderno e flexível */
.modal-agendamentos-dia-content {
    /* O container que tem a rolagem horizontal */
    overflow-x: auto;
    width: 100%;
    box-sizing: border-box;
    padding: 18px 20px 30px 20px; /* Adiciona padding para as bordas */
}

#conteudoAgendamentosDia table,
.tabela-agendamentos-dia {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    background: #ffffffff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 18px rgba(0,0,0,0.08);
    margin: 0 auto;
    font-size: 15px;
    min-width: 1200px;
    table-layout: fixed;
    overflow-x: auto;
    display: block; /* Importante para que o overflow funcione em tabelas */
}

#conteudoAgendamentosDia th,
#conteudoAgendamentosDia td {
    padding: 8px 6px;
    font-size: 13px;
    text-transform: uppercase;
    white-space: normal;
    word-break: break-word;
    border-bottom: 1px solid #e3eaf5;
    text-align: center;
}

#conteudoAgendamentosDia th {
    background: linear-gradient(90deg, #254c90 80%, #1e3b75 100%);
    color: #fff;
    font-weight: 700;
    font-size: 14px;
    border-bottom: 2px solid #254c90;
    letter-spacing: 1px;
}

#conteudoAgendamentosDia tr:last-child td {
    border-bottom: none;
}

#conteudoAgendamentosDia tr:hover td {
    background: #f2f6fc;
}

#conteudoAgendamentosDia td {
    background: #fcfdff;
}

#conteudoAgendamentosDia td:nth-child(odd) {
    background: #f6f8fa;
}

/* Modal e tabela: estilos para a responsividade total (OTIMIZADO) */
.modal-agendamentos-dia-inner {
    width: auto;
    padding: 32px 18px 38px 18px;
    position: relative;
    box-shadow: 0 8px 32px rgba(37,76,144,0.18);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    overflow-x: auto;
    border-radius: 22px;
    max-width: 90vw;
    margin: 0 auto;
}

@media (max-width: 1300px) {
    .modal-agendamentos-dia-inner {
        min-width: 900px;
        padding: 18px 4vw 28px 4vw;
    }
}
@media (max-width: 900px) {
    .modal-agendamentos-dia-inner {
        min-width: 700px;
        padding: 10px 1vw 18px 1vw;
    }
    #conteudoAgendamentosDia table,
    .tabela-agendamentos-dia {
        min-width: 700px;
        font-size: 11px;
        overflow-x: auto;
        display: block; /* Importante para que o overflow funcione em tabelas */
    }
    #conteudoAgendamentosDia th, #conteudoAgendamentosDia td {
        font-size: 11px;
        padding: 4px 2px;
    }
}
@media (max-width: 700px) {
    .modal-agendamentos-dia-inner {
        min-width: 400px;
        padding: 2vw 0 2vw 0;
    }
}

/* Botão de fechar fixo no topo direito */
.fechar-modal-agendamentos-dia {
    position: absolute;
    top: 22px;
    right: 32px;
    background: #28a745;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    padding: 10px 28px;
    font-size: 1.1em;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(40,167,69,0.08);
    transition: background 0.2s;
    z-index: 2;
}
.fechar-modal-agendamentos-dia:hover {
    background: #218838;
}

.modal-zoom-alert {
    background: linear-gradient(90deg, #254c90 80%, #1e3b75 100%);
    color: #fff;
    border-radius: 8px;
    padding: 8px 18px;
    margin: 0 0 18px 0;
    font-size: 1.05em;
    text-align: center;
    box-shadow: 0 2px 8px rgba(37,76,144,0.08);
    letter-spacing: 0.5px;
}

.modal-agenda-title {
    margin-top: 0;
    margin-bottom: 10px;
    text-align: left;
    font-size: 1.5em;
    color: #254c90;
    font-weight: 900;
    letter-spacing: 1px;
    border-bottom: 2.5px solid #254c90;
    padding-bottom: 8px;
    background: linear-gradient(90deg, #f8fafc 80%, #e3eaf5 100%);
    border-radius: 8px 8px 0 0;
    box-shadow: 0 2px 8px rgba(37,76,144,0.04);
}



/* 🔹 Fim - Estilos Gerais */
</style>

</head>
<body>
  <img src="./img/Logo.svg" alt="Logo Comercial Souza">



<!-- Botão de Perfil Único -->
<div style="position: absolute; top: 20px; right: 20px;">
  <button onclick="document.getElementById('modalPerfil').style.display='flex'" style="
    padding: 10px 18px;
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    box-shadow: 1px 1px 5px rgba(0,0,0,0.2);
    font-size: 1rem;
  ">
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

  <!-- Botão de Gerenciamento (somente para admin e supervisor) -->
<?php if (
    (isset($_SESSION['tipoUsuario']) && $_SESSION['tipoUsuario'] === 'supervisor') ||
    (isset($usuario) && $usuario === 'admin')
): ?>

<?php endif; ?>


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
        <div style="background:#fff; border-radius:18px; min-width:320px; max-width:95vw; padding:32px 28px 28px 28px; box-shadow:0 10px 32px rgba(0,0,0,0.18); position:relative; text-align:center;">
          <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
            <span id="painelTempoData" style="font-size:1.08em; font-weight:bold; color:#254c90; min-width:110px; display:inline-block; cursor:pointer; border-bottom:1px dashed #254c90;" title="Clique para escolher outro dia"></span>
            <input type="text" id="painelTempoDatePicker" style="display:none; width:0; height:0; border:none; padding:0; margin:0;">
            <button id="btnPainelFechar" title="Fechar" style="background:none; border:none; font-size:1.5rem; color:#254c90; cursor:pointer;">&times;</button>
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


  <!-- Legenda do Calendário -->
  <div class="calendar-legend">
    <ul>
        <li><span class="legend-blocked"></span>Bloqueado</li>
        <li><span class="legend-available"></span>Disponível</li>
        <li><span class="legend-partial"></span>Parcialmente Agendado</li>
        <li><span class="legend-full"></span>Totalmente Agendado</li>
    </ul>
  </div>

  <!-- Legenda Mobile (horizontal) -->
<div class="calendar-legend legend-mobile">
  <div class="legend-items">
    <div><span class="legend-blocked"></span> Bloqueado</div>
    <div><span class="legend-available"></span> Disponível</div>
    <div><span class="legend-partial"></span> Parcial</div>
    <div><span class="legend-full"></span> Cheio</div>
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

    <!-- Ações de Supervisor (acesso rápido a portaria e visão de recebimento) -->
<?php if (isset($_SESSION['tipoUsuario']) && $_SESSION['tipoUsuario'] === 'supervisor'): ?>
  <div class="supervisor-actions-modal">
    <div class="supervisor-actions-content">
      <h3 style="margin-top:0; color:#254c90;">Ações de Supervisor</h3>
      <button onclick="window.location.href='portaria.php'">
        Acessar Portaria
      </button>
      <button onclick="window.location.href='visao-recebimento.php'">
        Visão de Recebimento
      </button>
      <button onclick="window.location.href='painel-senhas.php'">
        Painel de Senhas
      </button>
    </div>
  </div>
<?php endif; ?>

  <!-- Modal de Agendamento -->
<div class="modal" id="modal">
  <div class="modal-content">
    <button class="close" id="closeModalBtn" title="Fechar">&times;</button>
    
    <h2>Informações do Transporte</h2>
    <form id="reservationForm" action="processar_agendamento.php" method="POST">
      <input type="hidden" name="dataAgendamento" id="dataAgendamento">
      <input type="hidden" name="nome_responsavel" value="<?php echo htmlspecialchars($usuario); ?>">
      <input type="hidden" name="comprador" value="<?php echo htmlspecialchars($usuario); ?>">
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

      <!-- Antes do botão de confirmação -->
<?php if ($_SESSION['tipoUsuario'] === 'supervisor'): ?>
  <button type="button" id="btnVerAgendamentosDia" style="background:#254c90; color:#fff; margin-bottom:10px;">
    Ver Agendamentos do Dia
  </button>
<?php endif; ?>

<button type="submit">Confirmar Agendamento</button>
    </form>
  </div>
</div>

   <!-- Botão para Visão dos Agendamentos -->
  <div style="margin-top: 20px; text-align: center;">
    <button onclick="window.location.href='visao-agendamentos.php'" style="
      padding: 10px 20px;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      box-shadow: 1px 1px 5px rgba(0, 0, 0, 0.2);
      transition: background-color 0.3s ease;
    " onmouseover="this.style.backgroundColor='#28a745'" onmouseout="this.style.backgroundColor='#28a745'">
      Ver Agendamentos
    </button>
  </div>

    <!-- Modal esticado -->
    <div id="modalAgendamentosDia" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); z-index:9999; color:#254c90; overflow:auto; padding:40px;">
      <div class="modal-agendamentos-dia-content" style="padding:0; background:none; box-shadow:none;">
        <div class="modal-agendamentos-dia-inner">
          <button onclick="document.getElementById('modalAgendamentosDia').style.display='none'" class="fechar-modal-agendamentos-dia">Fechar</button>
          <h2 class="modal-agenda-title">
            <span>Agendamentos de <span id="tituloDataAgendamento"></span></span>
          </h2>
          <div class="modal-zoom-alert">
            Para melhor visualização, utilize o zoom do navegador em 90% (<b>Ctrl</b> + <b>-</b> ou <b>Ctrl</b> + <b>Scroll</b>).
          </div>
          <div id="conteudoAgendamentosDia"></div>
        </div>
      </div>
    </div>

<!-- Modal para inserção de notas fiscais (visual melhorada) -->
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
  
  <!-- Passando usuário para o JS -->
  <script>
  window.tipoUsuario = "<?php echo $_SESSION['tipoUsuario'] ?? ''; ?>";
  const usuario = "<?php echo $usuario; ?>";
  console.log('Tipo do usuário:', window.tipoUsuario);
  </script>
  <script src="js/feriados.js"></script>
  <script src="js/calendario.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Placa sempre maiúscula
    var placaInput = document.getElementById('placa');
    if (placaInput) {
      placaInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
      });
    }
  
    // Modal de Perfil
    var modalPerfil = document.getElementById('modalPerfil');
    if (modalPerfil) {
      var perfilBtn = document.querySelector('button[onclick*="modalPerfil"]');
      if (perfilBtn) {
        perfilBtn.onclick = function() {
          modalPerfil.style.display = 'flex';
        };
      }
      var closePerfil = modalPerfil.querySelector('span[onclick]');
      if (closePerfil) {
        closePerfil.onclick = function() {
          modalPerfil.style.display = 'none';
        };
      }
    }
  
    // Modal de Agendamento
    var modal = document.getElementById('modal');
    var closeBtn = document.getElementById('closeModalBtn');
    if (closeBtn) {
      closeBtn.onclick = function() {
        modal.style.display = 'none';
      };
    }
  
    // Modal de Gerenciamento
    var closeGerenciamentoBtn = document.getElementById('closeGerenciamentoBtn');
    if (closeGerenciamentoBtn) {
      closeGerenciamentoBtn.onclick = function() {
        document.getElementById('modalGerenciamento').style.display = 'none';
      };
    }
  
    // Modal de Notas Fiscais
    var quantidadeNotas = document.getElementById('quantidadeNotas');
    if (quantidadeNotas) {
      quantidadeNotas.addEventListener('input', function() {
        const quantidade = parseInt(this.value);
        if (quantidade > 0) {
          gerarCamposNotasFiscais(quantidade);
          document.getElementById('modalNotasFiscais').style.display = 'flex';
        }
      });
    }
    var fecharModalNotas = document.getElementById('fecharModalNotas');
    if (fecharModalNotas) {
      fecharModalNotas.addEventListener('click', function() {
        document.getElementById('modalNotasFiscais').style.display = 'none';
        document.getElementById('mensagemErro').style.display = 'none';
      });
    }
  
    // Notas fiscais - geração dinâmica
    window.gerarCamposNotasFiscais = function(quantidade) {
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
      document.querySelectorAll('input[name="notasFiscais[]"]').forEach(input => {
        input.addEventListener('input', function(e) {
          this.value = this.value.replace(/[^0-9]/g, '');
        });
      });
    };
  
    window.confirmarNotasFiscais = function() {
      const notasFiscais = [];
      const inputs = document.querySelectorAll('input[name="notasFiscais[]"]');
      const mensagemErro = document.getElementById('mensagemErro');
      let camposVazios = false;
      inputs.forEach(input => {
        if (input.value.trim() === '') {
          camposVazios = true;
          input.style.borderColor = '#dc3545';
        } else {
          input.style.borderColor = '#dbe4f3';
          notasFiscais.push(input.value.trim());
        }
      });
      if (camposVazios) {
        mensagemErro.style.display = 'block';
        mensagemErro.textContent = `Preencha todos os ${inputs.length} campos de notas fiscais.`;
        return;
      }
      mensagemErro.style.display = 'none';
      let inputHidden = document.getElementById('notasFiscaisJSON');
      if (!inputHidden) {
        inputHidden = document.createElement('input');
        inputHidden.type = 'hidden';
        inputHidden.name = 'notasFiscaisJSON';
        inputHidden.id = 'notasFiscaisJSON';
        const form = document.getElementById('reservationForm'); 
        if (form) {
            form.appendChild(inputHidden);
        } else {
            console.error('Formulário não encontrado');
        }
      }
      inputHidden.value = JSON.stringify(notasFiscais);
      document.getElementById('modalNotasFiscais').style.display = 'none';
    };
  
    // Validação e envio do formulário de agendamento
    const form = document.getElementById('reservationForm');
    if (form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formElement = this;
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
            if (!res.success && res.podeForcar) {
              if (confirm(res.message + '\nDeseja tentar uma vaga de outro tipo de caminhão?')) {
                alert('Consultando disponibilidade em outros tipos de caminhão...');
                enviarAgendamento(true);
              }
            } else if (res.success) {
              alert('Agendamento realizado com sucesso!');
              location.reload();
            } else {
              alert(res.message || 'Erro ao agendar.');
            }
          });
        }
        enviarAgendamento(false);
      });
    }
  
    // Painel de Tempo do Dia
    let dataSelecionadaCalendario = null;
    window.onCalendarDayClick = function(dataSelecionada) {
      dataSelecionadaCalendario = dataSelecionada;
      atualizarPainelTempo(dataSelecionada);
      // ...restante da lógica de seleção do dia...
    };
  
    let diasDoMesPainel = [];
    let dataAtualPainel = null;
    let dadosMesPainel = null;
  
    function formatarDataBR(dataISO) {
      const [ano, mes, dia] = dataISO.split('-');
      return `${dia}/${mes}/${ano}`;
    }
    function formatarDigital(minutos) {
      let h = Math.floor(minutos/60);
      let m = minutos%60;
      return `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}`;
    }
  
    var btnPainelTempo = document.getElementById('btnPainelTempo');
    if (btnPainelTempo) {
      btnPainelTempo.onclick = function() {
        let dataBase = window.dataSelecionadaCalendario || new Date().toISOString().slice(0,10);
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
    }
  
    window.abrirPainelTempoModal = function(data) {
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

      document.getElementById('modalPainelTempo').style.display = 'flex';

      // Aguarda o modal renderizar para garantir que os elementos existem
      setTimeout(function() {
        var btnFechar = document.getElementById('btnPainelFechar');
        var btnPrev = document.getElementById('btnPainelPrev');
        var btnNext = document.getElementById('btnPainelNext');
  
        if (btnFechar) {
          btnFechar.onclick = function() {
            document.getElementById('modalPainelTempo').style.display = 'none';
          };
        }
        if (btnPrev) {
          btnPrev.onclick = function() {
            let idx = diasDoMesPainel.indexOf(dataAtualPainel);
            if (idx > 0) abrirPainelTempoModal(diasDoMesPainel[idx-1]);
          };
        }
        if (btnNext) {
          btnNext.onclick = function() {
            let idx = diasDoMesPainel.indexOf(dataAtualPainel);
            if (idx < diasDoMesPainel.length-1) abrirPainelTempoModal(diasDoMesPainel[idx+1]);
          };
        }
      }, 0);
  
      var painelTempoData = document.getElementById('painelTempoData');
      var datePicker = document.getElementById('painelTempoDatePicker');
      if (painelTempoData && datePicker) {
        // Inicializa flatpickr apenas uma vez
        if (!datePicker._flatpickr) {
          flatpickr(datePicker, {
            dateFormat: "Y-m-d",
            defaultDate: data,
            minDate: diasDoMesPainel[0],
            maxDate: diasDoMesPainel[diasDoMesPainel.length-1],
            disable: [
              function(date) {
                // Só permite dias do mês carregado
                const d = date.toISOString().slice(0,10);
                return !diasDoMesPainel.includes(d);
              }
            ],
            onChange: function(selectedDates, dateStr) {
              if (dateStr) {
                datePicker.style.display = 'none';
                abrirPainelTempoModal(dateStr);
              }
            },
            onClose: function() {
              datePicker.style.display = 'none';
            }
          });
        }
        painelTempoData.onclick = function() {
          datePicker.value = data;
          datePicker.style.display = 'block';
          datePicker._flatpickr.setDate(data, true);
          datePicker._flatpickr.open();
        };
      }
    };
  });
  </script>
  </body>
  </html>