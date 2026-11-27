<?php
require_once __DIR__ . '/funcoes.php';

$mensagem = pegar_mensagem();
$usuario  = usuario_logado();

if ($layout === 'app') {
    header('Cache-Control: no-store');
}

$p_nivel = $_GET['nivel'] ?? null;
$p_modo  = $_GET['modo'] ?? 'ativos';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> | NEXUS Systems</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="estilo.css">
    <style id="lateral">
/* ===== Layout com barra lateral (menu à esquerda) ===== */
body.app {
    display: grid;
    grid-template-columns: 270px minmax(0, 1fr);
    align-items: start;
}

.lateral {
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 24px;
    padding: 24px 16px;
    background: var(--fundo-card);
    backdrop-filter: blur(12px);
    border-right: 1px solid var(--borda-glow);
    z-index: 100;
}

.lateral .marca { font-size: 1.7rem; padding: 0 6px; }

.lateral .menu-admin {
    flex-direction: column;
    gap: 4px;
    padding: 8px;
}

.lateral .menu-admin a {
    padding: 11px 14px;
    border: 1px solid transparent;
}

.menu-titulo {
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--texto-suave);
    padding: 10px 14px 4px;
}
.menu-titulo:first-child { padding-top: 4px; }

.lateral-rodape {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.lateral-rodape .user-chip { border-radius: 10px; word-break: break-word; }

.lateral-acoes { display: flex; align-items: center; gap: 10px; }
.lateral-acoes form { flex: 1; margin: 0; }
.lateral-acoes .sair { width: 100%; justify-content: center; }

@media (max-width: 900px) {
    body.app { display: block; }
    .lateral { position: static; height: auto; gap: 16px; border-right: 0; border-bottom: 1px solid var(--borda-glow); }
    .lateral .menu-admin { flex-direction: row; flex-wrap: wrap; }
    .menu-titulo { width: 100%; }
    .lateral-rodape { margin-top: 0; }
    .principal-app { padding: 20px; }
}
    </style>
</head>
<body class="<?= e($layout) ?>">

<?php if ($layout === 'auth'): ?>
<aside class="institucional">
    <div class="header-auth-top">
        <div class="marca">
            <div class="logo-icon"><i data-lucide="cpu"></i></div>
            NEXUS
        </div>
        <button id="theme-toggle" class="btn-theme-toggle" title="Alternar Modo Escuro / Claro">
            <i data-lucide="sun" class="icon-sun"></i>
            <i data-lucide="moon" class="icon-moon"></i>
        </button>
    </div>
    <div class="hero-quote">
        <p>PLATAFORMA DE SUPORTE E TELEMETRIA DE ALTA PERFORMANCE</p>
        <small>Arquitetura Cyber-Resiliente & Gestão Inteligente de Chamados</small>
    </div>
</aside>
<main class="principal-auth">
<?php else: ?>
<aside class="lateral">
    <div class="marca">
        <div class="logo-icon"><i data-lucide="zap"></i></div>
        NEXUS
    </div>

    <nav class="menu-admin" aria-label="Navegação principal">
    <?php if (($usuario['perfil'] ?? '') === 'admin'): ?>
        <?php $na = $usuario['nivel_admin'] ?? 'N1'; $pagina = basename($_SERVER['PHP_SELF']); ?>
        <?php if ($na === 'todos'): ?>
            <span class="menu-titulo">Atendimento</span>
            <a href="admin.php?modo=ativos" class="<?= (!isset($_GET['nivel']) && $p_modo === 'ativos' && $pagina === 'admin.php') ? 'ativo' : '' ?>"><i data-lucide="layout-dashboard"></i> Painel Ativos</a>
            <a href="admin.php?nivel=N1&modo=ativos" class="<?= ($p_nivel === 'N1' && $p_modo === 'ativos') ? 'ativo' : '' ?>"><i data-lucide="shield-alert"></i> Fila N1</a>
            <a href="admin.php?nivel=N2&modo=ativos" class="<?= ($p_nivel === 'N2' && $p_modo === 'ativos') ? 'ativo' : '' ?>"><i data-lucide="terminal"></i> Fila N2</a>
            <a href="admin.php?nivel=N3&modo=ativos" class="<?= ($p_nivel === 'N3' && $p_modo === 'ativos') ? 'ativo' : '' ?>"><i data-lucide="server"></i> Fila N3</a>
            <a href="admin.php?modo=historico" class="<?= ($p_modo === 'historico' && $pagina === 'admin.php') ? 'ativo' : '' ?>"><i data-lucide="archive"></i> Histórico Concluídos</a>
            <span class="menu-titulo">Administração</span>
            <a href="admin_usuarios.php" class="<?= $pagina === 'admin_usuarios.php' ? 'ativo' : '' ?>"><i data-lucide="users"></i> Usuários</a>
            <a href="admin_backup.php" class="<?= $pagina === 'admin_backup.php' ? 'ativo' : '' ?>"><i data-lucide="database-backup"></i> Backup</a>
        <?php else: ?>
            <span class="menu-titulo">Atendimento</span>
            <a href="admin.php?modo=ativos" class="<?= ($p_modo === 'ativos' && $pagina === 'admin.php') ? 'ativo' : '' ?>"><i data-lucide="activity"></i> Chamados Ativos (<?= e($na) ?>)</a>
            <a href="admin.php?modo=historico" class="<?= ($p_modo === 'historico' && $pagina === 'admin.php') ? 'ativo' : '' ?>"><i data-lucide="history"></i> Histórico Concluídos (<?= e($na) ?>)</a>
        <?php endif; ?>
    <?php else: ?>
        <span class="menu-titulo">Suporte</span>
        <a href="chamado.php" class="<?= basename($_SERVER['PHP_SELF']) === 'chamado.php' ? 'ativo' : '' ?>"><i data-lucide="life-buoy"></i> Meus Chamados</a>
    <?php endif; ?>
    </nav>

    <div class="lateral-rodape">
        <div class="user-chip">
            <i data-lucide="user-check"></i>
            <span><?= e($usuario['nome'] ?? '') ?> <strong>(<?= ($usuario['perfil'] ?? '') === 'admin' ? e($usuario['nivel_admin'] ?? 'N1') : 'Cliente' ?>)</strong></span>
        </div>
        <div class="lateral-acoes">
            <button id="theme-toggle" class="btn-theme-toggle" title="Alternar Modo Escuro / Claro">
                <i data-lucide="sun" class="icon-sun"></i>
                <i data-lucide="moon" class="icon-moon"></i>
            </button>
            <form action="sair.php" method="post">
                <?= csrf_campo() ?>
                <button type="submit" class="sair"><i data-lucide="log-out"></i> Sair</button>
            </form>
        </div>
    </div>
</aside>
<main class="principal-app">
<?php endif; ?>
