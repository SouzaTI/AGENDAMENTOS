<?php
/**
 * visao-logs.php - Versão com Timer em Tempo Real
 */

session_start();
require 'db.php';

$idsPermitidos = [3, 6, 8];

// Lógica de Logoff
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: visao-logs.php");
    exit;
}

// Lógica de Processamento do Login
$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_login'])) {
    $userForm = $_POST['usuario'] ?? '';
    $passForm = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare("SELECT id, usuario, senha FROM usuarios WHERE usuario = :usuario");
    $stmt->execute([':usuario' => $userForm]);
    $usuarioBanco = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioBanco && password_verify($passForm, $usuarioBanco['senha'])) {
        if (in_array($usuarioBanco['id'], $idsPermitidos)) {
            $_SESSION['user_id'] = $usuarioBanco['id'];
            $_SESSION['usuario_nome'] = $usuarioBanco['usuario'];
            header("Location: visao-logs.php");
            exit;
        } else {
            $erro = "Acesso negado: Seu ID ({$usuarioBanco['id']}) não tem permissão.";
        }
    } else {
        $erro = "Usuário ou senha incorretos.";
    }
}

$logado = false;
if (isset($_SESSION['user_id']) && in_array($_SESSION['user_id'], $idsPermitidos)) {
    $logado = true;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Auditoria | Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
        
        .login-box { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 350px; margin: auto; }
        .login-box h2 { margin-top: 0; color: #1e293b; text-align: center; }
        .input-group { margin-bottom: 15px; }
        .input-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-entrar { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        
        .container { width: 95%; max-width: 1300px; margin: 20px auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px; }
        
        /* Estilo do Timer */
        #timer-container { font-size: 0.85rem; color: #1e40af; background: #dbeafe; padding: 5px 12px; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .dot { height: 8px; width: 8px; background-color: #2563eb; border-radius: 50%; display: inline-block; animation: blink 1s infinite; }
        
        @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }

        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th { text-align: left; background: #f8fafc; color: #64748b; padding: 12px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
        .badge-user { background: #f1f5f9; padding: 3px 6px; border-radius: 4px; font-weight: 600; }
        .btn-sair { color: #dc2626; text-decoration: none; font-size: 0.8rem; font-weight: 600; margin-left: 15px; }
    </style>
</head>
<body>

<?php if (!$logado): ?>
    <div class="login-box">
        <h2>Acesso Restrito</h2>
        <?php if($erro): ?> <p style="color:red; text-align:center;"><?= $erro ?></p> <?php endif; ?>
        <form method="POST">
            <div class="input-group"><input type="text" name="usuario" placeholder="Usuário" required></div>
            <div class="input-group"><input type="password" name="senha" placeholder="Senha" required></div>
            <button type="submit" name="btn_login" class="btn-entrar">Acessar Auditoria</button>
        </form>
    </div>
<?php else: ?>
    <div class="container">
        <div class="header">
            <div>
                <h2 style="margin:0;">📋 Logs de Auditoria</h2>
                <div id="timer-container">
                    <span class="dot"></span>
                    Próxima atualização em: <span id="countdown">60</span>s
                </div>
            </div>
            <div>
                <span><strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong></span>
                <a href="?logout=1" class="btn-sair">Sair</a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Usuário</th>
                    <th>Ação</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM logs ORDER BY data_hora DESC LIMIT 100");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                ?>
                <tr>
                    <td style="color: #94a3b8;"><?= date('d/m/Y H:i:s', strtotime($row['data_hora'])) ?></td>
                    <td><span class="badge-user"><?= htmlspecialchars($row['usuario']) ?></span></td>
                    <td><strong><?= htmlspecialchars($row['acao']) ?></strong></td>
                    <td><?= htmlspecialchars($row['detalhes']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Lógica do Timer
        let timeLeft = 60; // Tempo em segundos
        const display = document.getElementById('countdown');

        const timer = setInterval(function() {
            timeLeft--;
            display.textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(timer);
                location.reload(); // Atualiza a página
            }
        }, 1000); // Roda a cada 1 segundo
    </script>
<?php endif; ?>

</body>
</html>