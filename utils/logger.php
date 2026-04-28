<?php
/**
 * utils/logger.php
 * Grava os logs diretamente na tabela 'logs' do banco de dados.
 */

// Puxa a conexão do seu db.php (que deve estar na pasta raiz, um nível acima)
require_once __DIR__ . '/../db.php'; 

function registrar_log($usuario, $acao, $pagina, $detalhes = null) {
    global $pdo; // Pega a variável $pdo lá do db.php
    
    // Se o usuário vier vazio (ex: tela de login falha), a gente garante um nome
    if (empty($usuario)) {
        $usuario = 'Sistema/Visitante';
    }

    try {
        // O MySQL se encarrega de colocar a data_hora atual com o NOW()
        $sql = "INSERT INTO logs (usuario, acao, pagina, detalhes, data_hora) 
                VALUES (:usuario, :acao, :pagina, :detalhes, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuario,
            ':acao' => $acao,
            ':pagina' => $pagina,
            ':detalhes' => $detalhes
        ]);
        
    } catch (PDOException $e) {
        // Modo ninja: se o log falhar, ele anota no log de erros do servidor
        // mas NÃO quebra a tela do usuário que está trabalhando.
        error_log("Erro ao salvar log no BD: " . $e->getMessage());
    }
}
?>