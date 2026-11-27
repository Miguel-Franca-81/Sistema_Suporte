<?php
require_once 'funcoes.php';
require_once 'conexao.php';

apenas_post();
exigir_login();

if (!csrf_valido()) {
    mensagem('falha', 'Sessão expirada. Tente novamente.');
    ir('chamado.php');
}

$usuario_id   = usuario_logado()['id'];
$departamento = trim($_POST['departamento'] ?? '');
$andar        = trim($_POST['andar'] ?? '');
$telefone     = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
$equipamento  = trim($_POST['equipamento'] ?? '');
$num_eq       = trim($_POST['num_eq'] ?? '');
$descricao    = trim($_POST['descricao'] ?? '');

$nivel = 'N1';
$fila  = 'Fila Inicial N1';
$historico_inicial = '[' . date('d/m/Y H:i') . '] Chamado registrado no NEXUS e enviado para a Triagem N1.';

try {
    $stmt = $conn->prepare('INSERT INTO chamados (usuario_id, departamento, andar, telefone, equipamento, num_eq, descricao, nivel, fila, historico) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssssssss', $usuario_id, $departamento, $andar, $telefone, $equipamento, $num_eq, $descricao, $nivel, $fila, $historico_inicial);
    $stmt->execute();
    $stmt->close();

    mensagem('sucesso', 'Chamado transmitido com sucesso! Entrada realizada na fila N1.');
    ir('chamado.php');
} catch (mysqli_sql_exception $ex) {
    error_log('Erro ao salvar chamado: ' . $ex->getMessage());
    guardar_antigo($_POST);
    mensagem('falha', 'Não foi possível abrir o chamado.');
    ir('chamado.php');
}
