<?php
require_once 'funcoes.php';
require_once 'conexao.php';

exigir_login();

$chamado_id = (int) ($_GET['chamado_id'] ?? 0);
$usuario = usuario_logado();

$stmt = $conn->prepare('SELECT c.*, l.nome as solicitante FROM chamados c JOIN login l ON l.id = c.usuario_id WHERE c.id = ? LIMIT 1');
$stmt->bind_param('i', $chamado_id);
$stmt->execute();
$chamado = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$chamado) {
    mensagem('falha', 'Chamado não localizado.');
    ir(destino_inicial());
}

if ($usuario['perfil'] !== 'admin' && $chamado['usuario_id'] != $usuario['id']) {
    mensagem('falha', 'Acesso não autorizado a esta sessão de chat.');
    ir('chamado.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensagem'])) {
    if (csrf_valido()) {
        $texto_msg = trim($_POST['mensagem'] ?? '');
        if ($texto_msg !== '') {
            $stmt_ins = $conn->prepare('INSERT INTO mensagens_chat (chamado_id, usuario_id, mensagem) VALUES (?, ?, ?)');
            $stmt_ins->bind_param('iis', $chamado_id, $usuario['id'], $texto_msg);
            $stmt_ins->execute();
            $stmt_ins->close();
            ir("chat.php?chamado_id=$chamado_id");
        }
    }
}

$stmt_msg = $conn->prepare('SELECT m.*, l.nome, l.perfil FROM mensagens_chat m JOIN login l ON l.id = m.usuario_id WHERE m.chamado_id = ? ORDER BY m.id ASC');
$stmt_msg->bind_param('i', $chamado_id);
$stmt_msg->execute();
$mensagens = $stmt_msg->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_msg->close();

$titulo = 'Chat Chamado #' . codigo_chamado($chamado['id']);
$layout = 'app';
include 'cabecalho.php';
?>
<section class="cartao cartao-largo" style="max-width:900px;">
    <div class="chat-header">
        <div>
            <h2><i data-lucide="message-square"></i> CHAT SUPORTE - #<?= e(codigo_chamado($chamado['id'])) ?></h2>
            <p class="subtitulo" style="margin:0;"><?= e($chamado['equipamento']) ?> | Solicitante: <strong><?= e($chamado['solicitante']) ?></strong></p>
        </div>
        <a href="<?= $usuario['perfil'] === 'admin' ? 'admin.php' : 'chamado.php' ?>" class="btn-secundario"><i data-lucide="arrow-left"></i> Voltar Painel</a>
    </div>

    <div class="chat-box">
        <?php if (empty($mensagens)): ?>
            <div class="chat-empty">
                <i data-lucide="bot"></i>
                <p>Canal criptografado de suporte ativado. Escreva uma mensagem para iniciar o atendimento.</p>
            </div>
        <?php else: ?>
            <?php foreach ($mensagens as $m): ?>
                <div class="chat-msg <?= $m['usuario_id'] == $usuario['id'] ? 'minha-msg' : 'outra-msg' ?>">
                    <div class="chat-autor">
                        <?= e($m['nome']) ?> <?= $m['perfil'] === 'admin' ? '🛡️ (Suporte)' : '' ?>
                        <span><?= date('d/m H:i', strtotime($m['criado_em'])) ?></span>
                    </div>
                    <div class="chat-texto"><?= nl2br(e($m['mensagem'])) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form action="chat.php?chamado_id=<?= $chamado_id ?>" method="POST" class="chat-form">
        <?= csrf_campo() ?>
        <input type="text" name="mensagem" placeholder="Digite sua mensagem de suporte..." required autocomplete="off">
        <button type="submit" class="botao" style="width:auto; margin-top:0;"><i data-lucide="send"></i> Enviar</button>
    </form>
</section>
<?php include 'rodape.php'; ?>
