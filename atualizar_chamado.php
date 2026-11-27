<?php
require_once 'funcoes.php';
require_once 'conexao.php';

apenas_post();
exigir_admin();

if (!csrf_valido()) {
    mensagem('falha', 'Sessão expirada.');
    ir('admin.php');
}

$id           = (int) $_POST['id'];
$status       = $_POST['status'] ?? 'aberto';
$nivel        = $_POST['nivel'] ?? 'N1';
$fila         = $_POST['fila'] ?? 'Fila Inicial N1';
$resposta     = trim($_POST['resposta'] ?? '');
$modo_origem  = $_POST['modo_origem'] ?? 'ativos';
$nivel_origem = $_POST['nivel_origem'] ?? '';

$usuario_admin = usuario_logado();
$nome_admin    = $usuario_admin['nome'] ?? 'Admin';

try {
    $stmt = $conn->prepare('UPDATE chamados SET status = ?, nivel = ?, fila = ?, resposta = ? WHERE id = ?');
    $stmt->bind_param('ssssi', $status, $nivel, $fila, $resposta, $id);
    $stmt->execute();
    $stmt->close();

    $log = "Atualizado por $nome_admin: Fila '$fila' [$nivel] com status '$status'.";
    adicionar_historico($conn, $id, $log);

    mensagem('sucesso', 'Chamado atualizado com sucesso.');
} catch (mysqli_sql_exception $ex) {
    error_log('Erro ao atualizar chamado: ' . $ex->getMessage());
    mensagem('falha', 'Erro ao processar atualização.');
}

$query = [];
if ($modo_origem) $query['modo'] = $modo_origem;
if ($nivel_origem) $query['nivel'] = $nivel_origem;

$url = 'admin.php' . (count($query) > 0 ? '?' . http_build_query($query) : '');
ir($url);
