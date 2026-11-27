<?php
require_once 'funcoes.php';
require_once 'conexao.php';

exigir_super_admin();

// ---- Download do backup (arquivo .json com tudo) ----
if (($_GET['baixar'] ?? '') === '1') {
    $nome_arquivo = 'nexus-backup-' . date('Ymd-His') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
    header('Cache-Control: no-store');
    echo json_backup(exportar_backup($conn));
    exit;
}

// ---- Ações ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        mensagem('falha', 'Sessão expirada. Tente novamente.');
        ir('admin_backup.php');
    }

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        if (salvar_backup($conn)) {
            mensagem('sucesso', 'Backup salvo na pasta "dados" do site.');
        } else {
            mensagem('falha', 'Não foi possível gravar na pasta "dados". Verifique a permissão de escrita.');
        }

    } elseif ($acao === 'restaurar') {
        $arq = $_FILES['arquivo'] ?? null;

        if (empty($_POST['confirmo'])) {
            mensagem('falha', 'Marque a confirmação para restaurar (os dados atuais serão substituídos).');
        } elseif (!$arq || $arq['error'] !== UPLOAD_ERR_OK) {
            mensagem('falha', 'Selecione o arquivo de backup (.json).');
        } elseif ($arq['size'] > 20 * 1024 * 1024) {
            mensagem('falha', 'Arquivo grande demais (máximo 20 MB).');
        } else {
            $dados = decodificar_backup((string) file_get_contents($arq['tmp_name']));
            if ($dados === null) {
                mensagem('falha', 'Arquivo inválido: não parece um backup do NEXUS.');
            } else {
                // Guarda uma cópia do estado atual antes de substituir.
                salvar_backup($conn, NEXUS_PASTA . '/antes-da-restauracao-' . date('Ymd-His') . '.dados.php');

                $restaurou = false;
                try {
                    restaurar_backup($conn, $dados);
                    $restaurou = true;
                } catch (Throwable $ex) {
                    error_log('NEXUS: falha ao restaurar upload: ' . $ex->getMessage());
                    mensagem('falha', 'Falha ao restaurar. Nenhum dado foi alterado.');
                }

                if ($restaurou) {
                    // As contas mudaram: a sessão atual pode não existir mais.
                    unset($_SESSION['usuario']);
                    mensagem('sucesso', 'Backup restaurado com sucesso. Entre novamente.');
                    ir('index.php');
                }
            }
        }
    }

    ir('admin_backup.php');
}

// ---- Situação atual ----
preparar_pasta_dados();
$arquivo_existe = is_file(NEXUS_ARQUIVO);
$dados_arquivo  = $arquivo_existe ? ler_arquivo_backup(NEXUS_ARQUIVO) : null;
$gravavel       = is_dir(NEXUS_PASTA) && is_writable(NEXUS_PASTA);

$agora_db = [];
foreach (NEXUS_TABELAS as $t) {
    $agora_db[$t] = (int) $conn->query("SELECT COUNT(*) FROM `$t`")->fetch_row()[0];
}
$no_arquivo = $dados_arquivo ? resumo_backup($dados_arquivo) : null;

$nomes_tabela = [
    'login'          => 'Usuários',
    'chamados'       => 'Chamados',
    'mensagens_chat' => 'Mensagens de chat',
];

$titulo = 'Backup e Dados - NEXUS';
$layout = 'app';
include 'cabecalho.php';
?>
<section class="painel">
    <div class="painel-topo">
        <div>
            <h1><i data-lucide="database-backup"></i> BACKUP E DADOS</h1>
            <p class="subtitulo">Tudo o que for salvo no site é copiado automaticamente para a pasta <strong>dados</strong>.</p>
        </div>
    </div>

    <?= bloco_mensagem($mensagem) ?>

    <?php if (!$gravavel): ?>
        <div class="aviso falha" role="alert">
            <i data-lucide="alert-triangle"></i>
            <span>A pasta "dados" não tem permissão de escrita, então o salvamento automático não está funcionando.</span>
        </div>
    <?php endif; ?>

    <div class="detalhes-secao caixa-gerenciamento">
        <strong><i data-lucide="hard-drive"></i> Situação do salvamento automático</strong>
        <p style="margin: 8px 0;">
            <?php if ($arquivo_existe): ?>
                Último salvamento: <strong><?= e(date('d/m/Y H:i:s', filemtime(NEXUS_ARQUIVO))) ?></strong>
                (<?= e(number_format(filesize(NEXUS_ARQUIVO) / 1024, 1, ',', '.')) ?> KB)
            <?php else: ?>
                Ainda não há arquivo salvo. Ele é criado na primeira vez que alguém cadastrar ou alterar algo, ou ao clicar em "Salvar agora".
            <?php endif; ?>
        </p>
        <div class="tabela-area">
            <table>
                <thead><tr><th>Informação</th><th>No banco agora</th><th>No arquivo salvo</th></tr></thead>
                <tbody>
                <?php foreach ($nomes_tabela as $t => $rotulo): ?>
                    <tr>
                        <td><?= e($rotulo) ?></td>
                        <td><?= $agora_db[$t] ?></td>
                        <td><?= $no_arquivo ? (int) $no_arquivo[$t] : '&mdash;' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form action="admin_backup.php" method="POST" style="margin-top: 12px;">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="salvar">
            <button type="submit" class="btn-salvar-sm"><i data-lucide="save"></i> Salvar agora</button>
        </form>
    </div>

    <div class="detalhes-secao caixa-gerenciamento" style="margin-top: 20px;">
        <strong><i data-lucide="download"></i> Baixar uma cópia</strong>
        <p class="suave" style="margin: 8px 0;">Gera um arquivo .json com usuários, chamados e conversas. Guarde em local seguro: ele contém dados pessoais e senhas criptografadas.</p>
        <a href="admin_backup.php?baixar=1" class="btn-secundario"><i data-lucide="download"></i> Baixar backup</a>
    </div>

    <div class="detalhes-secao caixa-gerenciamento" style="margin-top: 20px;">
        <strong><i data-lucide="upload"></i> Restaurar de um arquivo</strong>
        <p class="suave" style="margin: 8px 0;">Substitui TODOS os dados atuais pelos do arquivo. Antes, o estado atual é guardado na pasta "dados" (antes-da-restauracao-...).</p>
        <form action="admin_backup.php" method="POST" enctype="multipart/form-data">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="restaurar">
            <div class="campo-grupo-sm">
                <label for="arquivo">Arquivo de backup (.json)</label>
                <input id="arquivo" name="arquivo" type="file" accept=".json,application/json" class="input-pequeno" required>
            </div>
            <label style="display: block; margin-top: 10px;">
                <input type="checkbox" name="confirmo" value="1" style="width: auto;">
                Entendo que os dados atuais serão substituídos
            </label>
            <button type="submit" class="btn-secundario" style="margin-top: 10px;"
                    onclick="return confirm('Substituir todos os dados atuais pelos do arquivo?');"><i data-lucide="upload"></i> Restaurar backup</button>
        </form>
    </div>

    <div class="detalhes-secao" style="margin-top: 20px;">
        <strong><i data-lucide="info"></i> Como levar o site com tudo salvo</strong>
        <p class="suave" style="margin-top: 8px;">
            Copie a <strong>pasta inteira do site, incluindo "dados"</strong>. No novo computador, ao abrir o site pela primeira vez com o MySQL vazio,
            o banco é criado e os dados voltam sozinhos. Para começar do zero, apague os arquivos dentro de "dados".
        </p>
    </div>
</section>
<?php include 'rodape.php'; ?>
