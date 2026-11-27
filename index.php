<?php
require_once 'funcoes.php';
require_once 'conexao.php';

if (usuario_logado()) {
    ir(destino_inicial());
}

$titulo = 'Acesso ao Sistema';
$layout = 'auth';
include 'cabecalho.php';
?>
    <section class="cartao" aria-labelledby="titulo">
        <nav class="abas" aria-label="Acesso">
            <a href="index.php" class="ativa" aria-current="page"><i data-lucide="log-in"></i> Entrar</a>
            <a href="cadastro.php"><i data-lucide="user-plus"></i> Cadastrar</a>
        </nav>

        <h1 id="titulo">Acesse o NEXUS</h1>
        <p class="subtitulo">Autentique-se para gerenciar seus chamados técnicos.</p>

        <?= bloco_mensagem($mensagem) ?>

        <?php if (!existe_admin($conn)): ?>
            <div class="aviso sucesso" role="status">
                <i data-lucide="shield-plus"></i>
                <span>Nenhum administrador cadastrado ainda. <a href="primeiro_acesso.php"><strong>Criar o primeiro Super Admin</strong></a></span>
            </div>
        <?php endif; ?>

        <form action="autenticar.php" method="post" novalidate data-validar>
            <?= csrf_campo() ?>

            <div class="campo">
                <label for="email"><i data-lucide="mail"></i> E-mail Corporativo</label>
                <input id="email" name="email" type="email" autocomplete="email" maxlength="255"
                       placeholder="seu.nome@empresa.com" value="<?= antigo('email') ?>" data-regra="email">
                <span class="erro" id="erro-email" aria-live="polite"></span>
            </div>

            <div class="campo">
                <label for="senha"><i data-lucide="lock"></i> Chave de Acesso</label>
                <div class="entrada">
                    <input id="senha" name="senha" type="password" autocomplete="current-password"
                           placeholder="••••••••" data-regra="senha_login">
                    <button type="button" class="mostrar" data-alvo="senha"><i data-lucide="eye"></i></button>
                </div>
                <span class="erro" id="erro-senha" aria-live="polite"></span>
            </div>

            <button type="submit" class="botao"><i data-lucide="shield-check"></i> ENTRAR NO NEXUS</button>
        </form>

        <p class="rodape">&copy; <?= date('Y') ?> NEXUS Systems. Todos os direitos reservados.</p>
    </section>
<?php
limpar_antigo();
include 'rodape.php';
?>
