<?php
// Funções compartilhadas NEXUS - Core Logic & Helpers

date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$empresa = 'NEXUS';

function e($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function ir($destino) {
    header('Location: ' . $destino);
    exit;
}

function mensagem($tipo, $texto) {
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}

function pegar_mensagem() {
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $m = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $m;
}

function bloco_mensagem($m) {
    if (!$m) {
        return '';
    }
    $sucesso = ($m['tipo'] === 'sucesso');
    $icone = $sucesso ? 'check-circle' : 'alert-triangle';
    return '<div class="aviso ' . ($sucesso ? 'sucesso' : 'falha') . '" role="' . ($sucesso ? 'status' : 'alert') . '">'
         . '<i data-lucide="' . $icone . '"></i><span>' . e($m['texto']) . '</span></div>';
}

function guardar_antigo(array $dados) {
    $_SESSION['antigo'] = $dados;
}

function antigo($campo) {
    return e($_SESSION['antigo'][$campo] ?? '');
}

function limpar_antigo() {
    unset($_SESSION['antigo']);
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_campo() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valido() {
    $enviado = $_POST['csrf_token'] ?? '';
    return is_string($enviado) && hash_equals($_SESSION['csrf_token'] ?? '', $enviado);
}

function usuario_logado() {
    return $_SESSION['usuario'] ?? null;
}

function destino_inicial() {
    $u = usuario_logado();
    return ($u && $u['perfil'] === 'admin') ? 'admin.php' : 'chamado.php';
}

function exigir_login() {
    if (!usuario_logado()) {
        mensagem('falha', 'Faça login para continuar.');
        ir('index.php');
    }
}

function exigir_admin() {
    exigir_login();
    if (usuario_logado()['perfil'] !== 'admin') {
        ir('chamado.php');
    }
}

// Apenas o Super Admin (nível "todos") gerencia usuários e backups.
function exigir_super_admin() {
    exigir_admin();
    if ((usuario_logado()['nivel_admin'] ?? 'N1') !== 'todos') {
        mensagem('falha', 'Apenas o Super Admin pode acessar esta área.');
        ir('admin.php');
    }
}

function existe_admin($conn) {
    return $conn->query("SELECT 1 FROM login WHERE perfil = 'admin' LIMIT 1")->num_rows > 0;
}

function apenas_post() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ir('index.php');
    }
}

function codigo_chamado($id) {
    return str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

function obter_filas_atendimento() {
    return [
        'Fila Inicial N1'        => 'Fila Inicial (Triagem N1)',
        'N2 - Hardware & Desktop'=> 'N2 - Hardware & Periféricos',
        'N2 - Sistemas & ERP'    => 'N2 - Sistemas & Softwares',
        'N3 - Infra & Servidores'=> 'N3 - Servidores & Cloud',
        'N3 - Redes & Segurança' => 'N3 - Redes & Segurança',
        'N3 - Banco de Dados'    => 'N3 - Banco de Dados'
    ];
}

function adicionar_historico($conn, $chamado_id, $acao) {
    $stmt = $conn->prepare('SELECT historico FROM chamados WHERE id = ?');
    $stmt->bind_param('i', $chamado_id);
    $stmt->execute();
    $stmt->bind_result($historico_atual);
    $stmt->fetch();
    $stmt->close();

    $data_hora = date('d/m/Y H:i');
    $novo_registro = "[$data_hora] " . $acao;
    $historico_completo = $historico_atual ? $historico_atual . "
" . $novo_registro : $novo_registro;

    $stmt_up = $conn->prepare('UPDATE chamados SET historico = ? WHERE id = ?');
    $stmt_up->bind_param('si', $historico_completo, $chamado_id);
    $stmt_up->execute();
    $stmt_up->close();
}
