<?php
/**
 * db.php
 *
 * Script de conexão PDO com o banco de dados MySQL do sistema de recebimento.
 *
 * FUNCIONALIDADE:
 * - Define os parâmetros de conexão (host, nome do banco, usuário, senha).
 * - Cria uma instância PDO para acesso ao banco de dados.
 * - Configura o modo de erro do PDO para exceção.
 * - Em caso de falha na conexão, exibe mensagem de erro e encerra o script.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Utiliza try/catch para capturar possíveis erros de conexão.
 * - O objeto $pdo é utilizado em todo o sistema para executar queries SQL de forma segura.
 *
 * REQUISITOS:
 * - Requer que o MySQL esteja rodando e o banco de dados 'recebimento' esteja criado.
 * - Usuário e senha devem estar corretos conforme configuração do ambiente.
 */

$host = '127.0.0.1:3307';
$dbname = 'recebimento'; // Nome do banco de dados
$username = 'root'; // Usuário do banco
$password = ''; // Senha do banco (deixe vazio se estiver usando XAMPP padrão)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
?>