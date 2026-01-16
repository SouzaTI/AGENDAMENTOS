<?php

session_start();
require_once __DIR__ . '/utils/logger.php';
$data = json_decode(file_get_contents('php://input'), true);
registrar_log(
    $_SESSION['usuario'] ?? 'desconhecido',
    $data['acao'] ?? 'Ação não informada',
    $data['pagina'] ?? 'Página não informada',
    $data['detalhes'] ?? null
);
http_response_code(204);