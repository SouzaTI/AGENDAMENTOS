<?php
/**
 * logout.php
 *
 * Script responsável por encerrar a sessão do usuário no sistema.
 *
 * FUNCIONALIDADE:
 * - Inicia a sessão (caso não esteja iniciada).
 * - Destroi todos os dados da sessão, efetivando o logout do usuário.
 * - Redireciona o usuário para a página de login.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Utiliza session_start() para garantir que a sessão está ativa.
 * - Usa session_destroy() para remover todos os dados da sessão.
 * - Após o logout, faz um redirect para login.php.
 */

session_start();
session_destroy();
header("Location: login.php");
exit();