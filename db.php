<?php
/**
 * db.php
 * Conexão PDO segura usando variáveis de ambiente (.env) na mesma raiz
 */

// Como estão na mesma pasta, o __DIR__ mata o problema do caminho direto!
$envPath = __DIR__ . '/.env';

if (file_exists($envPath)) {
    $envVariables = parse_ini_file($envPath);
    
    $host     = $envVariables['DB_HOST'];
    $dbname   = $envVariables['DB_NAME'];
    $username = $envVariables['DB_USER'];
    $password = $envVariables['DB_PASS'];
} else {
    die("Erro crítico: Arquivo .env não encontrado na raiz do projeto.");
}

try {
    // Tratamento cirúrgico: se tiver a porta (:3307) no HOST, a gente separa pro PDO
    if (strpos($host, ':') !== false) {
        list($realHost, $port) = explode(':', $host);
        $dsn = "mysql:host=$realHost;port=$port;dbname=$dbname;charset=utf8mb4";
    } else {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    }

    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Se quiser testar se conectou mesmo, pode descomentar a linha abaixo:
    // echo "Conexão estabelecida com sucesso no banco: " . $dbname;

} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
?>