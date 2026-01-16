<?php
/**
 * valida_login.php
 *
 * Script responsável por validar o login do usuário no sistema.
 *
 * FUNCIONALIDADE:
 * - Recebe usuário e senha via POST.
 * - Busca o usuário informado na tabela 'usuarios'.
 * - Permite autenticação por senha em texto puro para usuários 'admin' e 'recebimento'.
 * - Para os demais, utiliza password_verify para comparar a senha informada com o hash armazenado.
 * - Se o login for válido, inicia a sessão e redireciona para a página principal.
 * - Se inválido, redireciona de volta para a tela de login com erro.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Utiliza PDO para consulta segura ao banco de dados.
 * - Armazena informações do usuário na sessão após login bem-sucedido.
 * - Não exibe mensagens de erro diretamente na tela, apenas via parâmetro GET.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Espera que a tabela 'usuarios' possua os campos: usuario, senha, nome_completo, tipo.
 */

session_start();
require 'db.php'; // Certifique-se de que este arquivo faz a conexão PDO

$usuario = $_POST['usuario'] ?? '';
$senha = $_POST['senha'] ?? '';

$sql = "SELECT * FROM usuarios WHERE usuario = :usuario";
$stmt = $pdo->prepare($sql);
$stmt->execute([':usuario' => $usuario]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$loginValido = false;
if ($user) {
    // Permite senha em texto puro para admin e recebimento
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
    $_SESSION['nomeCompleto'] = $user['nome_completo'];
    $_SESSION['tipoUsuario'] = $user['tipo'];
    header("Location: pagina-principal.php");
    exit();
} else {
    header("Location: login.php?erro=1");
    exit();
}
