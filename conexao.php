<?php
$db_servidor = "localhost";
$db_usuario  = "root";
$db_senha    = "";
$db_nome     = "sistema";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/backup.php';

try {
    try {
        $conn = new mysqli($db_servidor, $db_usuario, $db_senha, $db_nome);
    } catch (mysqli_sql_exception $ex) {
        // 1049 = banco inexistente: cria sozinho (não precisa mais importar o banco.sql).
        if ((int) $ex->getCode() !== 1049) {
            throw $ex;
        }
        $tmp = new mysqli($db_servidor, $db_usuario, $db_senha);
        $tmp->query("CREATE DATABASE IF NOT EXISTS `$db_nome` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $tmp->close();
        $conn = new mysqli($db_servidor, $db_usuario, $db_senha, $db_nome);
    }
    $conn->set_charset('utf8mb4');

    // Cria tabelas, atualiza bancos antigos e restaura o backup se o banco estiver vazio.
    preparar_banco($conn);
} catch (mysqli_sql_exception $ex) {
    error_log('Falha na conexão: ' . $ex->getMessage());
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados NEXUS.');
}

// Salvamento automático: ao fim de TODA requisição que grava dados (POST),
// uma cópia atualizada vai para dados/backup.dados.php.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && !in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), ['autenticar.php'], true)) {
    register_shutdown_function(function () use ($conn) {
        try {
            salvar_backup($conn);
        } catch (Throwable $ex) {
            error_log('NEXUS: backup automático falhou: ' . $ex->getMessage());
        }
    });
}
