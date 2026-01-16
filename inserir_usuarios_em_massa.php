<?php
/**
 * Script para inserir usuários em massa na tabela 'usuarios'
 * Altere o array $usuarios para os dados desejados.
 */

require 'db.php'; // Conexão com o banco de dados

// Lista de usuários para inserir (adicione quantos quiser)
$usuarios = [
    // usuario, departamento, tipo
    ['Cicero.Couto', 'Recebimento', 'recebimento'],
    
    // Adicione mais linhas conforme necessário
];

// Senha padrão (será hash)
$senhaPadrao = '123456';
$hashPadrao = password_hash($senhaPadrao, PASSWORD_DEFAULT);

$sucesso = 0;
$falha = 0;

foreach ($usuarios as $u) {
    [$usuario, $departamento, $tipo] = $u;

    // Verifica se já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    if ($stmt->fetch()) {
        echo "Usuário <b>$usuario</b> já existe.<br>";
        $falha++;
        continue;
    }

    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, senha, departamento, tipo, precisa_redefinir) VALUES (?, ?, ?, ?, 1)");
    if ($stmt->execute([$usuario, $hashPadrao, $departamento, $tipo])) {
        echo "Usuário <b>$usuario</b> inserido com sucesso.<br>";
        $sucesso++;
    } else {
        echo "Erro ao inserir <b>$usuario</b>.<br>";
        $falha++;
    }
}

echo "<hr><b>Total inseridos:</b> $sucesso<br><b>Total falhas:</b> $falha";
?>