<?php
require_once 'funcoes.php';
require_once 'conexao.php';

exigir_super_admin();

$meu_id  = (int) usuario_logado()['id'];
$PERFIS  = ['usuario', 'admin'];
$NIVEIS  = ['N1', 'N2', 'N3', 'todos'];
$rotulo_nivel = [
    'N1'    => 'Atendente N1 (Triagem)',
    'N2'    => 'Atendente N2 (Intermediário)',
    'N3'    => 'Atendente N3 (Especialista)',
    'todos' => 'Super Admin (Visão Global)',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        mensagem('falha', 'Sessão expirada. Tente novamente.');
        ir('admin_usuarios.php');
    }

    $acao   = $_POST['acao'] ?? '';
    $alvo   = (int) ($_POST['usuario_id'] ?? 0);
    $perfil = $_POST['perfil'] ?? 'usuario';
    $nivel  = $_POST['nivel_admin'] ?? 'N1';
    if (!in_array($perfil, $PERFIS, true)) { $perfil = 'usuario'; }
    if (!in_array($nivel, $NIVEIS, true))  { $nivel = 'N1'; }
    if ($perfil === 'usuario')             { $nivel = 'N1'; }

    try {
        if ($acao === 'criar') {
            $nome  = trim($_POST['nome'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $senha = $_POST['senha'] ?? '';
            guardar_antigo(['nome' => $nome, 'email' => $email]);

            if ($nome === '' || mb_strlen($nome, 'UTF-8') > 100) {
                mensagem('falha', 'Informe o nome (até 100 caracteres).');
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                mensagem('falha', 'Digite um e-mail válido.');
            } elseif (strlen($senha) < 8) {
                mensagem('falha', 'A senha deve ter no mínimo 8 caracteres.');
            } else {
                $stmt = $conn->prepare('SELECT id FROM login WHERE email = ? LIMIT 1');
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $ja_existe = $stmt->get_result()->num_rows > 0;
                $stmt->close();

                if ($ja_existe) {
                    mensagem('falha', 'Já existe um usuário com este e-mail.');
                } else {
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('INSERT INTO login (nome, email, senha, perfil, nivel_admin) VALUES (?, ?, ?, ?, ?)');
                    $stmt->bind_param('sssss', $nome, $email, $hash, $perfil, $nivel);
                    $stmt->execute();
                    $stmt->close();
                    limpar_antigo();
                    mensagem('sucesso', 'Usuário criado: ' . $nome . ' (' . ($perfil === 'admin' ? $rotulo_nivel[$nivel] : 'Usuário solicitante') . ').');
                }
            }

        } elseif ($acao === 'atualizar') {
            if ($alvo === $meu_id) {
                mensagem('falha', 'Você não pode alterar as suas próprias permissões (evita ficar sem Super Admin).');
            } else {
                $stmt = $conn->prepare('UPDATE login SET perfil = ?, nivel_admin = ? WHERE id = ?');
                $stmt->bind_param('ssi', $perfil, $nivel, $alvo);
                $stmt->execute();
                $stmt->close();
                mensagem('sucesso', 'Permissões atualizadas. O usuário precisa entrar novamente para valer.');
            }

        } elseif ($acao === 'senha') {
            $nova = $_POST['nova_senha'] ?? '';
            if (strlen($nova) < 8) {
                mensagem('falha', 'A nova senha deve ter no mínimo 8 caracteres.');
            } else {
                $hash = password_hash($nova, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE login SET senha = ? WHERE id = ?');
                $stmt->bind_param('si', $hash, $alvo);
                $stmt->execute();
                $stmt->close();
                mensagem('sucesso', 'Senha redefinida.');
            }

        } elseif ($acao === 'excluir') {
            if ($alvo === $meu_id) {
                mensagem('falha', 'Você não pode excluir a própria conta.');
            } else {
                $stmt = $conn->prepare('DELETE FROM login WHERE id = ?');
                $stmt->bind_param('i', $alvo);
                $stmt->execute();
                $stmt->close();
                mensagem('sucesso', 'Usuário excluído (e os chamados dele também).');
            }
        }
    } catch (mysqli_sql_exception $ex) {
        error_log('Erro em admin_usuarios: ' . $ex->getMessage());
        mensagem('falha', 'Não foi possível concluir a operação.');
    }

    ir('admin_usuarios.php');
}

$usuarios = $conn->query('SELECT id, nome, email, perfil, nivel_admin, criado_em FROM login ORDER BY id DESC')->fetch_all(MYSQLI_ASSOC);

$titulo = 'Gerenciamento de Usuários - NEXUS';
$layout = 'app';
include 'cabecalho.php';
?>
<section class="painel">
    <div class="painel-topo">
        <div>
            <h1><i data-lucide="shield-check"></i> USUÁRIOS E PERMISSÕES</h1>
            <p class="subtitulo">Crie contas, defina quem é usuário solicitante, atendente N1/N2/N3 ou Super Admin.</p>
        </div>
        <dl class="resumo">
            <div><dt>Usuários</dt><dd><?= count($usuarios) ?></dd></div>
        </dl>
    </div>

    <?= bloco_mensagem($mensagem) ?>

    <div class="detalhes-secao caixa-gerenciamento" style="margin-bottom: 28px;">
        <strong><i data-lucide="user-plus"></i> Adicionar novo usuário</strong>
        <form action="admin_usuarios.php" method="POST" class="form-gerenciamento-exp" style="margin-top: 12px;">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="criar">

            <div class="linha-inputs">
                <div class="campo-grupo-sm">
                    <label for="novo_nome">Nome completo</label>
                    <input id="novo_nome" name="nome" type="text" class="input-pequeno" maxlength="100" required
                           placeholder="Nome e sobrenome" value="<?= antigo('nome') ?>">
                </div>
                <div class="campo-grupo-sm">
                    <label for="novo_email">E-mail</label>
                    <input id="novo_email" name="email" type="email" class="input-pequeno" maxlength="255" required
                           placeholder="pessoa@empresa.com" value="<?= antigo('email') ?>">
                </div>
                <div class="campo-grupo-sm">
                    <label for="novo_senha">Senha inicial (mín. 8)</label>
                    <input id="novo_senha" name="senha" type="text" class="input-pequeno" minlength="8" required
                           autocomplete="off" placeholder="Passe para a pessoa trocar depois">
                </div>
                <div class="campo-grupo-sm">
                    <label for="novo_perfil">Tipo de conta</label>
                    <select id="novo_perfil" name="perfil" class="input-pequeno">
                        <option value="usuario">Usuário Solicitante</option>
                        <option value="admin">Administrador / Suporte</option>
                    </select>
                </div>
                <div class="campo-grupo-sm">
                    <label for="novo_nivel">Nível (só para administradores)</label>
                    <select id="novo_nivel" name="nivel_admin" class="input-pequeno" disabled>
                        <?php foreach ($rotulo_nivel as $chave => $rotulo): ?>
                            <option value="<?= e($chave) ?>"><?= e($rotulo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-salvar-sm" style="margin-top: 12px;"><i data-lucide="user-plus"></i> Criar usuário</button>
        </form>
    </div>
    <?php limpar_antigo(); ?>

    <div class="tabela-area">
        <table class="tabela-expansivel">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>ID</th>
                    <th>Usuário / E-mail</th>
                    <th>Tipo de conta</th>
                    <th>Nível</th>
                    <th>Criado em</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <?php $sou_eu = ((int) $u['id'] === $meu_id); $eh_admin = ($u['perfil'] === 'admin'); ?>
                <tr class="linha-resumo">
                    <td>
                        <button type="button" class="btn-expandir" data-target="usuario-<?= (int) $u['id'] ?>" title="Editar usuário">
                            <i data-lucide="chevron-right" class="icon-expand"></i>
                        </button>
                    </td>
                    <td><span class="tag-id">#<?= (int) $u['id'] ?></span></td>
                    <td>
                        <strong><?= e($u['nome']) ?></strong><?= $sou_eu ? ' <small class="suave">(você)</small>' : '' ?><br>
                        <small class="suave"><?= e($u['email']) ?></small>
                    </td>
                    <td><?= $eh_admin ? 'Administrador / Suporte' : 'Usuário Solicitante' ?></td>
                    <td>
                        <?php if ($eh_admin): ?>
                            <span class="status status-nivel-<?= e(strtolower($u['nivel_admin'])) ?>"><?= e($rotulo_nivel[$u['nivel_admin']] ?? $u['nivel_admin']) ?></span>
                        <?php else: ?>
                            <span class="suave">&mdash;</span>
                        <?php endif; ?>
                    </td>
                    <td class="nao-quebra"><?= e(date('d/m/Y H:i', strtotime($u['criado_em']))) ?></td>
                </tr>
                <tr id="usuario-<?= (int) $u['id'] ?>" class="linha-detalhes oculta">
                    <td colspan="6">
                        <div class="detalhes-container">
                            <div class="detalhes-secao caixa-gerenciamento">
                                <strong><i data-lucide="settings"></i> Permissões</strong>
                                <?php if ($sou_eu): ?>
                                    <p class="suave" style="margin-top: 8px;">Você não pode alterar ou excluir a própria conta aqui.</p>
                                <?php else: ?>
                                    <form action="admin_usuarios.php" method="POST" class="form-gerenciamento-exp" style="margin-top: 10px;">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="acao" value="atualizar">
                                        <input type="hidden" name="usuario_id" value="<?= (int) $u['id'] ?>">
                                        <div class="linha-inputs">
                                            <div class="campo-grupo-sm">
                                                <label>Tipo de conta</label>
                                                <select name="perfil" class="input-pequeno">
                                                    <option value="usuario" <?= !$eh_admin ? 'selected' : '' ?>>Usuário Solicitante</option>
                                                    <option value="admin" <?= $eh_admin ? 'selected' : '' ?>>Administrador / Suporte</option>
                                                </select>
                                            </div>
                                            <div class="campo-grupo-sm">
                                                <label>Nível (só para administradores)</label>
                                                <select name="nivel_admin" class="input-pequeno">
                                                    <?php foreach ($rotulo_nivel as $chave => $rotulo): ?>
                                                        <option value="<?= e($chave) ?>" <?= $u['nivel_admin'] === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn-salvar-sm" style="margin-top: 10px;"><i data-lucide="check"></i> Salvar permissões</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <div class="detalhes-secao caixa-gerenciamento">
                                <strong><i data-lucide="key-round"></i> Redefinir senha</strong>
                                <form action="admin_usuarios.php" method="POST" class="form-gerenciamento-exp" style="margin-top: 10px;">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="acao" value="senha">
                                    <input type="hidden" name="usuario_id" value="<?= (int) $u['id'] ?>">
                                    <div class="linha-inputs">
                                        <div class="campo-grupo-sm">
                                            <label>Nova senha (mín. 8 caracteres)</label>
                                            <input name="nova_senha" type="text" class="input-pequeno" minlength="8" required autocomplete="off">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn-salvar-sm" style="margin-top: 10px;"><i data-lucide="save"></i> Redefinir senha</button>
                                </form>
                            </div>

                            <?php if (!$sou_eu): ?>
                            <div class="detalhes-secao caixa-gerenciamento">
                                <strong><i data-lucide="trash-2"></i> Excluir usuário</strong>
                                <p class="suave" style="margin-top: 8px;">Remove a conta e também todos os chamados e mensagens dela.</p>
                                <form action="admin_usuarios.php" method="POST"
                                      onsubmit="return confirm('Excluir <?= e(addslashes($u['nome'])) ?> e todos os chamados dessa conta? Esta ação não pode ser desfeita.');">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="usuario_id" value="<?= (int) $u['id'] ?>">
                                    <button type="submit" class="btn-secundario" style="margin-top: 10px;"><i data-lucide="trash-2"></i> Excluir usuário</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<script>
    // No formulário "novo usuário", o nível só vale para administradores.
    (function () {
        var perfil = document.getElementById('novo_perfil');
        var nivel = document.getElementById('novo_nivel');
        function sincronizar() { nivel.disabled = perfil.value !== 'admin'; }
        perfil.addEventListener('change', sincronizar);
        sincronizar();
    })();
</script>
<?php include 'rodape.php'; ?>
