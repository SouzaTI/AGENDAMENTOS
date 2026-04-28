<?php
/**
 * db.php
 * Conexão PDO segura usando variáveis de ambiente (.env)
 */

// Pega o caminho absoluto da pasta onde o db.php está
$envPath = __DIR__ . '/.env';

// Verifica se o arquivo .env existe e lê as variáveis
if (file_exists($envPath)) {
    $envVariables = parse_ini_file($envPath);
    
    // Pega estritamente o que está no arquivo .env (sem fallbacks/plano B)
    $host     = $envVariables['DB_HOST'];
    $dbname   = $envVariables['DB_NAME'];
    $username = $envVariables['DB_USER'];
    $password = $envVariables['DB_PASS'];
} else {
    // Se o arquivo não existir, mata a execução na hora
    die("Erro crítico: Arquivo .env não encontrado. Crie o arquivo na raiz do projeto.");
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Falha rápida se as credenciais do .env estiverem erradas
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
?>