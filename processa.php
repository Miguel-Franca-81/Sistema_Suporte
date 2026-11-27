<?php
require_once 'funcoes.php';
require_once 'conexao.php';

apenas_post();

if (!csrf_valido()) {
    mensagem('falha', 'Sua sessão expirou. Tente novamente.');
    ir('cadastro.php');
}

$nome      = trim($_POST['nome'] ?? '');
$email     = strtolower(trim($_POST['email'] ?? ''));
$senha     = $_POST['senha'] ?? '';
$rep_senha = $_POST['rep_senha'] ?? '';

guardar_antigo(['nome' => $nome, 'email' => $email]);

if (mb_strlen($nome, 'UTF-8') < 5 || count(preg_split('/\s+/', $nome)) < 2) {
    mensagem('falha', 'Digite seu nome e sobrenome.');
    ir('cadastro.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    mensagem('falha', 'Digite um e-mail válido.');
    ir('cadastro.php');
}

if (strlen($senha) < 8) {
    mensagem('falha', 'A senha deve ter no mínimo 8 caracteres.');
    ir('cadastro.php');
}

if ($senha !== $rep_senha) {
    mensagem('falha', 'As senhas não coincidem.');
    ir('cadastro.php');
}

try {
    $stmt = $conn->prepare('SELECT id FROM login WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->fetch()) {
        $stmt->close();
        mensagem('falha', 'Este e-mail já está cadastrado.');
        ir('cadastro.php');
    }
    $stmt->close();

    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO login (nome, email, senha) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $nome, $email, $hash);
    $stmt->execute();
    $id_novo = $stmt->insert_id;
    $stmt->close();

    limpar_antigo();
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id'          => (int) $id_novo,
        'nome'        => $nome,
        'email'       => $email,
        'perfil'      => 'usuario',
        'nivel_admin' => 'N1',
    ];

    mensagem('sucesso', 'Conta NEXUS criada com sucesso!');
    ir('chamado.php');
} catch (mysqli_sql_exception $ex) {
    error_log('Erro no cadastro: ' . $ex->getMessage());
    mensagem('falha', 'Não foi possível concluir o cadastro.');
    ir('cadastro.php');
}
