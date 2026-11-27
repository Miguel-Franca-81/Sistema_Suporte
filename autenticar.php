<?php
require_once 'funcoes.php';
require_once 'conexao.php';

apenas_post();

if (!csrf_valido()) {
    mensagem('falha', 'Sua sessão expirou. Tente novamente.');
    ir('index.php');
}

$email = strtolower(trim($_POST['email'] ?? ''));
$senha = $_POST['senha'] ?? '';

$achou = false;
try {
    $stmt = $conn->prepare('SELECT id, nome, email, senha, perfil, nivel_admin FROM login WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($id, $nome, $emailDb, $hash, $perfil, $nivel_admin);
    $achou = $stmt->fetch();
    $stmt->close();
} catch (mysqli_sql_exception $ex) {
    error_log('Erro no login: ' . $ex->getMessage());
    mensagem('falha', 'Não foi possível entrar agora. Tente novamente.');
    ir('index.php');
}

if (!$achou || !password_verify($senha, $hash)) {
    mensagem('falha', 'E-mail ou senha incorretos.');
    ir('index.php');
}

session_regenerate_id(true);
$_SESSION['usuario'] = [
    'id'          => (int) $id,
    'nome'        => $nome,
    'email'       => $emailDb,
    'perfil'      => $perfil,
    'nivel_admin' => $nivel_admin ?? 'N1',
];

ir(destino_inicial());
