<?php
/**
 * login.php
 *
 * Página de login e registro de usuários do sistema de agendamento de recebimentos.
 */

session_start();
require 'db.php'; // Conexão com o banco de dados

$erro = '';
$sucesso = '';

// --- Redefinição obrigatória de senha ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_nova_senha'])) {
    if (isset($_SESSION['usuario'])) {
        $novaSenha = $_POST['nova_senha'];
        $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, precisa_redefinir = 0 WHERE usuario = ?");
        $stmt->execute([$hash, $_SESSION['usuario']]);
        $_SESSION['sucesso_redefinicao'] = "Senha alterada com sucesso! Faça login com sua nova senha.";
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

// --- Troca voluntária de senha ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trocar_senha'])) {
    $usuarioTroca = $_POST['usuario_troca'];
    $senhaAtual = $_POST['senha_atual'];
    $novaSenhaTroca = $_POST['nova_senha_troca'];
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
    $stmt->execute([':usuario' => $usuarioTroca]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($senhaAtual, $user['senha'])) {
        $hash = password_hash($novaSenhaTroca, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE usuario = ?");
        $stmt->execute([$hash, $usuarioTroca]);
        $sucesso = "Senha alterada com sucesso! Faça login com sua nova senha.";
    } else {
        $erro = "Usuário ou senha atual incorretos.";
    }
}

// --- Registro de novo usuário ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar'])) {
    $novo_usuario = $_POST['novo_usuario'];
    $nova_senha = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
    $departamento = $_POST['nome_completo'];
    $tipo = "usuario"; // Força o tipo para "usuario"

    // Verifica se já existe
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
    $stmt->execute([':usuario' => $novo_usuario]);
    if ($stmt->fetch()) {
        $erro = "Usuário já existe!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, senha, departamento, tipo) VALUES (:usuario, :senha, :departamento, :tipo)");
        $stmt->execute([
            ':usuario' => $novo_usuario,
            ':senha' => $nova_senha,
            ':departamento' => $departamento,
            ':tipo' => $tipo
        ]);
        $sucesso = "Usuário registrado com sucesso!";
    }
}

// --- Login ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['registrar']) && !isset($_POST['salvar_nova_senha']) && !isset($_POST['trocar_senha'])) {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Consulta o banco de dados para verificar o usuário
    $sql = "SELECT * FROM usuarios WHERE usuario = :usuario";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':usuario' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $loginValido = false;
    if ($user) {
        if (
            ($usuario === 'admin' && $senha === $user['senha']) ||
            ($usuario === 'recebimento' && $senha === $user['senha'])
        ) {
            $loginValido = true;
        } elseif (password_verify($senha, $user['senha'])) {
            $loginValido = true;
        }
    }

    if ($loginValido) {
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['departamento'] = $user['departamento'];
        $_SESSION['tipoUsuario'] = $user['tipo'];

        if ($user['precisa_redefinir']) {
            $precisaRedefinir = true;
        } else {
            // Redireciona conforme o tipo de usuário
            if ($user['usuario'] === 'recebimento' || $user['tipo'] === 'operacional' || $user['tipo'] === 'recebimento') {
                header('Location: visao-recebimento.php');
            } elseif ($user['tipo'] === 'portaria') {
                header('Location: portaria.php');
            } else {
                header('Location: pagina-principal.php?comprador=' . urlencode($user['usuario']));
            }
            exit;
        }
    } else {
        $erro = "Credenciais inválidas. Tente novamente.";
    }
}

// --- Mensagem de sucesso após redefinição obrigatória ---
if (isset($_SESSION['sucesso_redefinicao'])) {
    $sucesso = $_SESSION['sucesso_redefinicao'];
    unset($_SESSION['sucesso_redefinicao']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento de Cargas</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: url('./img/background.png') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #254c90;
        }
        .login-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            width: 350px;
            position: relative;
        }
        .login-container img {
            width: 200px;
            max-width: 90%;
            margin-bottom: 20px;
        }
        .login-container h2 {
            color: #0052a5;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .login-container input {
            width: calc(100% - 20px);
            padding: 15px;
            margin: 15px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        .login-container input:focus {
            border-color: #0052a5;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 82, 165, 0.5);
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            background: #0052a5;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        .login-container button:hover {
            background: #003d7a;
        }
        .login-container button:active {
            transform: scale(0.98);
        }
        @media (max-width: 600px) {
            body {
                background-image: none;
                background: #254c90;
            }
            .login-container {
                width: 100%;
                max-width: 320px;
                padding: 16px;
                margin: 0 auto;
            }
        }
        #modal, #modalTrocarSenha, #modalRedefinirSenha {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="./img/Logo.svg" alt="Logo Comercial Souza">
        <h2>Agendamento de Cargas</h2>
        <?php if (!empty($erro)): ?>
            <div style="color: red; margin-bottom: 10px;"><?php echo $erro; ?></div>
        <?php endif; ?>
        <?php if (!empty($sucesso)): ?>
            <div style="color: green; margin-bottom: 10px;"><?php echo $sucesso; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="usuario" placeholder="Usuário" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <button type="submit">ENTRAR</button>
        </form>
        <!-- Botão de registro ajustado para não enviar o formulário de login -->
        <button type="button" style="margin-top:10px;" onclick="document.getElementById('modal').style.display = 'flex'">Registrar</button>

        <!-- Modal de registro -->
        <div id="modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
            <div style="background:#fff; padding:30px; border-radius:10px; width:300px; margin:auto; position:relative;">
                <span style="position:absolute; top:10px; right:15px; cursor:pointer;" onclick="document.getElementById('modal').style.display='none'">&times;</span>
                <h3>Registrar novo usuário</h3>
                <form method="POST" action="">
                    <input type="text" name="novo_usuario" placeholder="Usuário" required>
                    <input type="password" name="nova_senha" placeholder="Senha" required>
                    <input type="text" name="nome_completo" placeholder="Departamento" required>
                    <button type="submit" name="registrar">Registrar</button>
                </form>
            </div>
        </div>

        <!-- Botão para abrir modal de troca de senha -->
        <button type="button" style="margin-top:10px;" onclick="document.getElementById('modalTrocarSenha').style.display = 'flex'">Trocar Senha</button>

        <!-- Modal de troca de senha -->
        <div id="modalTrocarSenha" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
            <div style="background:#fff; padding:30px; border-radius:10px; width:320px; margin:auto; position:relative;">
                <span style="position:absolute; top:10px; right:15px; cursor:pointer;" onclick="document.getElementById('modalTrocarSenha').style.display='none'">&times;</span>
                <h3 style="color:#254c90;">Trocar Senha</h3>
                <form method="POST" action="">
                    <input type="text" name="usuario_troca" placeholder="Usuário" required>
                    <input type="password" name="senha_atual" placeholder="Senha atual" required>
                    <input type="password" name="nova_senha_troca" placeholder="Nova senha" required>
                    <button type="submit" name="trocar_senha">Salvar Nova Senha</button>
                </form>
            </div>
        </div>
    </div>

    <?php if (isset($precisaRedefinir) && $precisaRedefinir): ?>
    <div id="modalRedefinirSenha" style="display:flex; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:99999;">
        <div style="background:#fff; padding:30px; border-radius:10px; width:320px; margin:auto; position:relative;">
            <h3 style="color:#254c90;">Redefinir Senha</h3>
            <form method="POST" action="">
                <input type="password" name="nova_senha" placeholder="Nova senha" required style="width:100%;padding:8px;margin:10px 0 18px 0;border-radius:6px;border:1px solid #ccc;">
                <button type="submit" name="salvar_nova_senha" style="width:100%;background:#254c90;color:#fff;padding:10px 0;border:none;border-radius:6px;font-weight:bold;cursor:pointer;">Salvar Nova Senha</button>
            </form>
        </div>
    </div>
    <script>
        // Impede interação com o fundo enquanto o modal está aberto
        document.body.style.overflow = 'hidden';
    </script>
    <?php endif; ?>
</body>
</html>
