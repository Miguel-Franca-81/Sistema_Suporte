<?php
require_once 'funcoes.php';

if (usuario_logado()) {
    ir(destino_inicial());
}

$titulo = 'Criar Conta';
$layout = 'auth';
include 'cabecalho.php';
?>
    <section class="cartao" aria-labelledby="titulo">
        <nav class="abas" aria-label="Acesso">
            <a href="index.php"><i data-lucide="log-in"></i> Entrar</a>
            <a href="cadastro.php" class="ativa" aria-current="page"><i data-lucide="user-plus"></i> Cadastrar</a>
        </nav>

        <h1 id="titulo">Novo Registro NEXUS</h1>
        <p class="subtitulo">Preencha seus dados para habilitar abertura de suporte.</p>

        <?= bloco_mensagem($mensagem) ?>

        <form action="processa.php" method="post" novalidate data-validar>
            <?= csrf_campo() ?>

            <div class="campo">
                <label for="nome"><i data-lucide="user"></i> Nome Completo</label>
                <input id="nome" name="nome" type="text" autocomplete="name" maxlength="100"
                       placeholder="Seu Nome e Sobrenome" value="<?= antigo('nome') ?>" data-regra="nome">
                <span class="erro" id="erro-nome" aria-live="polite"></span>
            </div>

            <div class="campo">
                <label for="email"><i data-lucide="mail"></i> E-mail Corporativo</label>
                <input id="email" name="email" type="email" autocomplete="email" maxlength="255"
                       placeholder="seu.nome@empresa.com" value="<?= antigo('email') ?>" data-regra="email">
                <span class="erro" id="erro-email" aria-live="polite"></span>
            </div>

            <div class="campo">
                <label for="senha"><i data-lucide="key-round"></i> Senha</label>
                <div class="entrada">
                    <input id="senha" name="senha" type="password" autocomplete="new-password"
                           placeholder="Mínimo 8 caracteres" data-regra="senha">
                    <button type="button" class="mostrar" data-alvo="senha"><i data-lucide="eye"></i></button>
                </div>
                <span class="erro" id="erro-senha" aria-live="polite"></span>
            </div>

            <div class="campo">
                <label for="rep_senha"><i data-lucide="check-check"></i> Confirmar Senha</label>
                <div class="entrada">
                    <input id="rep_senha" name="rep_senha" type="password" autocomplete="new-password"
                           placeholder="Repita sua senha" data-regra="rep_senha">
                    <button type="button" class="mostrar" data-alvo="rep_senha"><i data-lucide="eye"></i></button>
                </div>
                <span class="erro" id="erro-rep_senha" aria-live="polite"></span>
            </div>

            <button type="submit" class="botao"><i data-lucide="sparkles"></i> CRIAR CONTA NEXUS</button>
        </form>

        <p class="rodape">&copy; <?= date('Y') ?> NEXUS Systems. Todos os direitos reservados.</p>
    </section>
<?php
limpar_antigo();
include 'rodape.php';
