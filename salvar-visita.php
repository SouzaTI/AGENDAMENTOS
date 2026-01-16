<?php
/**
 * salvar-visita.php
 *
 * Endpoint para registrar informações de visita/acesso ao sistema.
 *
 * FUNCIONALIDADE:
 * - Recebe dados via requisição JSON (IP, cidade, região, país, latitude, longitude, navegador).
 * - Insere essas informações na tabela 'visitas' junto com a data/hora do acesso.
 * - Retorna uma resposta JSON indicando sucesso.
 *
 * DETALHES DO FUNCIONAMENTO:
 * - Utiliza PDO para inserção segura no banco de dados.
 * - Espera receber os dados via corpo da requisição (php://input), geralmente enviados por JavaScript.
 * - Pode ser usado para fins de estatística, segurança ou auditoria de acessos.
 *
 * REQUISITOS:
 * - Requer o arquivo db.php para conexão com o banco de dados.
 * - Espera que a tabela 'visitas' possua os campos: ip, cidade, regiao, pais, latitude, longitude, navegador, data_hora.
 */

require 'db.php'; // Arquivo de conexão com PDO

// Recebe os dados JSON enviados pelo JS
$dados = json_decode(file_get_contents('php://input'), true);

// Validação básica dos dados recebidos
$ip        = $dados['ip']        ?? '';
$cidade    = $dados['cidade']    ?? '';
$regiao    = $dados['regiao']    ?? '';
$pais      = $dados['pais']      ?? '';
$latitude  = $dados['latitude']  ?? '';
$longitude = $dados['longitude'] ?? '';
$navegador = $dados['navegador'] ?? '';

// Prepara e executa o insert
$stmt = $pdo->prepare("
    INSERT INTO visitas 
    (ip, cidade, regiao, pais, latitude, longitude, navegador, data_hora) 
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmt->execute([
    $ip, $cidade, $regiao, $pais, $latitude, $longitude, $navegador
]);

// Retorna uma resposta JSON
echo json_encode(['success' => true]);
?>
