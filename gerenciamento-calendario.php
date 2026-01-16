<?php
/**
 * gerenciamento-calendario.php
 *
 * Página de gerenciamento dos limites globais de agendamento do calendário.
 *
 * FUNCIONALIDADE:
 * - Permite definir e salvar os limites globais (capacidades máximas) para cada tipo de caminhão (truck, toco, carreta, utilitários).
 * - Exibe os limites atuais configurados para cada tipo.
 * - Salva os novos limites no banco de dados, atualizando ou inserindo conforme necessário.
 * - Exibe mensagens de sucesso ou erro após a operação.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Utiliza PDO para consultas e inserções seguras no banco de dados.
 * - Utiliza sessão para exibir mensagens de feedback ao usuário.
 * - Após salvar, faz redirect para evitar reenvio do formulário.
 * - Interface simples e responsiva, com formulário para edição dos limites e exibição dos valores atuais.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Espera que a tabela 'limites_agendamentos' possua os campos: data (NULL para global), tipo_caminhao, limite.
 */

session_start();
require 'db.php'; // Conexão com o banco de dados

// Recuperar os limites globais
$sql = "SELECT tipo_caminhao, limite FROM limites_agendamentos WHERE data IS NULL";
$stmt = $pdo->query($sql);
$limitesGlobais = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizar os limites em um array associativo para facilitar o acesso
$limites = [];
foreach ($limitesGlobais as $limite) {
    $limites[$limite['tipo_caminhao']] = $limite['limite'];
}

// Salvar capacidades
if (isset($_POST['salvar_capacidades'])) {
    $truck = $_POST['qtd-truck'];
    $toco = $_POST['qtd-toco'];
    $carreta = $_POST['qtd-carreta'];
    $utilitarios = $_POST['qtd-utilitarios']; // Adicionado

    // Vamos usar try catch para garantir que erros sejam capturados
    try {
        // Atualiza ou insere os limites usando INSERT ... ON DUPLICATE KEY UPDATE (assumindo que você tenha chave única)
        $tipos = [
            'truck' => $truck,
            'toco' => $toco,
            'carreta' => $carreta,
            'utilitarios' => $utilitarios // Adicionado
        ];
        foreach ($tipos as $tipo => $limite) {
            $sql = "INSERT INTO limites_agendamentos (data, tipo_caminhao, limite) 
                    VALUES (NULL, :tipo, :limite) 
                    ON DUPLICATE KEY UPDATE limite = :limite";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':tipo' => $tipo, ':limite' => $limite]);
        }

        // Mensagem de sucesso para exibir no reload
        $_SESSION['mensagem'] = "Capacidades globais salvas com sucesso!";
        $_SESSION['tipo_mensagem'] = "success";

    } catch (Exception $e) {
        // Mensagem de erro para exibir no reload
        $_SESSION['mensagem'] = "Erro ao salvar capacidades. Tente novamente.";
        $_SESSION['tipo_mensagem'] = "error";
    }

    // Redireciona para a mesma página para evitar reenvio do formulário e exibir mensagem
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gerenciamento do Calendário</title>
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
            font-size: 36px;
            color: rgb(255, 255, 255);
            margin-bottom: 20px;
        }

        .container {
            max-width: 800px;
            width: 100%;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        button {
            padding: 12px;
            background: #0052a5;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
            width: 100%;
        }

        button:hover {
            background: #003d7a;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            font-weight: bold;
            transition: opacity 0.3s ease;
            z-index: 1100;
        }

        .toast.error {
            background-color: #dc3545; /* Vermelho para erro */
        }

        .toast.success {
            background-color: #28a745; /* Verde para sucesso */
        }
    </style>
</head>
<body>

<!-- Toast de Notificação -->
<?php if (isset($_SESSION['mensagem'])): ?>
    <div id="toastNotification" class="toast <?php echo $_SESSION['tipo_mensagem']; ?>" style="display: block;">
        <span><?php echo $_SESSION['mensagem']; ?></span>
    </div>
    <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
<?php endif; ?>

    <h1>Gerenciar Capacidades Globais</h1>

    <div class="container">
        <h2>Definir Capacidades para Todos os Dias</h2>
        <form method="POST">
            <label for="qtd-truck">Truck:</label>
            <input type="number" name="qtd-truck" min="0" placeholder="Ex: 10" required>

            <label for="qtd-toco">Toco:</label>
            <input type="number" name="qtd-toco" min="0" placeholder="Ex: 5" required>

            <label for="qtd-carreta">Carreta:</label>
            <input type="number" name="qtd-carreta" min="0" placeholder="Ex: 15" required>

            <label for="qtd-utilitarios">Utilitários:</label> <!-- Novo campo -->
            <input type="number" name="qtd-utilitarios" min="0" placeholder="Ex: 8" required>

            <button type="submit" name="salvar_capacidades">Salvar Capacidades</button>
        </form>
    </div>

    <div class="container">
        <h2>Limites Globais Atuais</h2>
        <ul>
            <li>Truck: <?php echo isset($limites['truck']) ? $limites['truck'] : 'Não definido'; ?></li>
            <li>Toco: <?php echo isset($limites['toco']) ? $limites['toco'] : 'Não definido'; ?></li>
            <li>Carreta: <?php echo isset($limites['carreta']) ? $limites['carreta'] : 'Não definido'; ?></li>
            <li>Utilitários: <?php echo isset($limites['utilitarios']) ? $limites['utilitarios'] : 'Não definido'; ?></li> <!-- Novo item -->
        </ul>
    </div>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    const toast = document.getElementById("toastNotification");
    if (toast) {
        setTimeout(() => {
            toast.style.opacity = "0";
            setTimeout(() => {
                toast.style.display = "none";
            }, 300);
        }, 3000); // Oculta após 3 segundos
    }
  });
</script>
</body>
</html>
