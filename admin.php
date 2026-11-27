<?php
require_once 'funcoes.php';
require_once 'conexao.php';

exigir_admin();

$usuario_admin = usuario_logado();
$nivel_admin_perfil = $usuario_admin['nivel_admin'] ?? 'N1';

$p_nivel = $_GET['nivel'] ?? null;
$p_modo  = $_GET['modo'] ?? 'ativos';

if ($nivel_admin_perfil === 'todos') {
    $nivel_filtro = $p_nivel;
} else {
    $nivel_filtro = $nivel_admin_perfil;
}

$nomesStatus = [
    'aberto'    => 'Aberto',
    'andamento' => 'Em andamento',
    'concluido' => 'Concluído',
];

$listaFilas = obter_filas_atendimento();
$chamados = [];
$falhou = false;

try {
    $condicoes = [];
    $tipos = '';
    $valores = [];

    if ($p_modo === 'historico') {
        $condicoes[] = "c.status = 'concluido'";
    } else {
        $condicoes[] = "c.status IN ('aberto', 'andamento')";
    }

    if ($nivel_filtro !== null && $nivel_filtro !== 'todos') {
        $condicoes[] = "c.nivel = ?";
        $tipos .= 's';
        $valores[] = $nivel_filtro;
    }

    $where_sql = count($condicoes) > 0 ? 'WHERE ' . implode(' AND ', $condicoes) : '';

    $sql = "SELECT c.id, c.criado_em, c.departamento, c.andar, c.telefone, c.equipamento, c.num_eq,
                   c.descricao, c.status, c.nivel, c.fila, c.resposta, c.historico, l.nome, l.email
              FROM chamados c
              JOIN login l ON l.id = c.usuario_id
            $where_sql
             ORDER BY c.id DESC";

    $stmt = $conn->prepare($sql);
    if ($tipos !== '') {
        $stmt->bind_param($tipos, ...$valores);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();
    $chamados = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (mysqli_sql_exception $ex) {
    error_log('Erro ao listar chamados admin: ' . $ex->getMessage());
    $falhou = true;
}

$total   = count($chamados);
$abertos = count(array_filter($chamados, function ($c) { return $c['status'] === 'aberto'; }));

$titulo = 'Painel Operacional - NEXUS';
$layout = 'app';
include 'cabecalho.php';
?>
    <section class="painel">
        <div class="painel-topo">
            <div>
                <h1>
                    <i data-lucide="shield"></i> 
                    <?= $p_modo === 'historico' ? 'HISTÓRICO DE CONCLUÍDOS' : 'FILA DE ATENDIMENTO ATIVA' ?> 
                    <span class="badge-nivel"><?= e($nivel_filtro ?? ($nivel_admin_perfil === 'todos' ? 'TODOS OS NÍVEIS' : $nivel_admin_perfil)) ?></span>
                </h1>
                <p class="subtitulo">
                    <?= $p_modo === 'historico' ? 'Consulta de chamados finalizados e arquivados.' : 'Chamados pendentes e em andamento aguardando resolução.' ?>
                </p>
            </div>
            <dl class="resumo">
                <div><dt>Total Fila</dt><dd><?= $total ?></dd></div>
                <div><dt>Pendentes</dt><dd><?= $abertos ?></dd></div>
            </dl>
        </div>

        <?= bloco_mensagem($mensagem) ?>

        <?php if ($falhou): ?>
            <div class="aviso falha" role="alert"><i data-lucide="alert-triangle"></i> Falha ao carregar registros.</div>
        <?php elseif ($total === 0): ?>
            <div class="vazio">
                <i data-lucide="inbox"></i>
                <p>Nenhum chamado encontrado nesta visão no momento.</p>
            </div>
        <?php else: ?>
            <div class="tabela-area">
                <table class="tabela-expansivel">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Nº ID</th>
                            <th>Solicitante</th>
                            <th>Equipamento</th>
                            <th>Nível</th>
                            <th>Fila</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($chamados as $c): ?>
                        <tr class="linha-resumo">
                            <td>
                                <button type="button" class="btn-expandir" data-target="detalhes-adm-<?= $c['id'] ?>" title="Expandir/Minimizar chamado">
                                    <i data-lucide="chevron-right" class="icon-expand"></i>
                                </button>
                            </td>
                            <td class="nao-quebra">
                                <span class="tag-id">#<?= e(codigo_chamado($c['id'])) ?></span>
                            </td>
                            <td>
                                <strong><?= e($c['nome']) ?></strong>
                            </td>
                            <td>
                                <strong><?= e($c['equipamento']) ?></strong>
                            </td>
                            <td>
                                <span class="status status-nivel-<?= strtolower($c['nivel']) ?>"><?= e($c['nivel']) ?></span>
                            </td>
                            <td>
                                <span class="tag-fila"><i data-lucide="layers"></i> <?= e($c['fila'] ?? 'Fila Inicial N1') ?></span>
                            </td>
                            <td>
                                <span class="status status-<?= e($c['status']) ?>">
                                    <?= e($nomesStatus[$c['status']] ?? $c['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="chat.php?chamado_id=<?= $c['id'] ?>" class="btn-chat-sm"><i data-lucide="message-square"></i> Chat</a>
                            </td>
                        </tr>
                        <tr id="detalhes-adm-<?= $c['id'] ?>" class="linha-detalhes oculta">
                            <td colspan="8">
                                <div class="detalhes-container">
                                    <div class="detalhes-grid">
                                        <div>
                                            <strong><i data-lucide="building-2"></i> Departamento / Local:</strong>
                                            <p><?= e($c['departamento']) ?> (<?= e($c['andar'] ?? 'N/I') ?>)</p>
                                        </div>
                                        <div>
                                            <strong><i data-lucide="phone"></i> Contato / Ramal:</strong>
                                            <p><?= e($c['telefone']) ?></p>
                                        </div>
                                        <div>
                                            <strong><i data-lucide="barcode"></i> Patrimônio / Série:</strong>
                                            <p><?= e($c['num_eq'] ?: 'Não informado') ?></p>
                                        </div>
                                        <div>
                                            <strong><i data-lucide="clock"></i> Data de Abertura:</strong>
                                            <p><?= date('d/m/Y H:i', strtotime($c['criado_em'])) ?></p>
                                        </div>
                                    </div>

                                    <div class="detalhes-secao">
                                        <strong><i data-lucide="align-left"></i> Descrição Completa do Incidente:</strong>
                                        <p class="caixa-texto"><?= nl2br(e($c['descricao'])) ?></p>
                                    </div>

                                    <div class="detalhes-secao caixa-gerenciamento">
                                        <strong><i data-lucide="settings"></i> Gerenciamento & Resposta do Atendimento:</strong>
                                        <form action="atualizar_chamado.php" method="POST" class="form-gerenciamento-exp">
                                            <?= csrf_campo() ?>
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <input type="hidden" name="modo_origem" value="<?= e($p_modo) ?>">
                                            <input type="hidden" name="nivel_origem" value="<?= e($p_nivel ?? '') ?>">
                                            
                                            <div class="linha-inputs">
                                                <div class="campo-grupo-sm">
                                                    <label>Status do Chamado:</label>
                                                    <select name="status" class="input-pequeno">
                                                        <option value="aberto" <?= $c['status'] == 'aberto' ? 'selected' : '' ?>>Aberto</option>
                                                        <option value="andamento" <?= $c['status'] == 'andamento' ? 'selected' : '' ?>>Em andamento</option>
                                                        <option value="concluido" <?= $c['status'] == 'concluido' ? 'selected' : '' ?>>Concluído (Mover p/ Histórico)</option>
                                                    </select>
                                                </div>

                                                <div class="campo-grupo-sm">
                                                    <label>Nível do Atendimento:</label>
                                                    <select name="nivel" class="input-pequeno">
                                                        <option value="N1" <?= $c['nivel'] == 'N1' ? 'selected' : '' ?>>N1 (Triagem)</option>
                                                        <option value="N2" <?= $c['nivel'] == 'N2' ? 'selected' : '' ?>>N2 (Intermediário)</option>
                                                        <option value="N3" <?= $c['nivel'] == 'N3' ? 'selected' : '' ?>>N3 (Especialista)</option>
                                                    </select>
                                                </div>

                                                <div class="campo-grupo-sm">
                                                    <label>Fila de Destino:</label>
                                                    <select name="fila" class="input-pequeno">
                                                        <?php foreach ($listaFilas as $chave => $rotulo): ?>
                                                            <option value="<?= e($chave) ?>" <?= ($c['fila'] ?? '') == $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="campo-grupo-sm" style="margin-top: 10px;">
                                                <label>Resposta do Suporte para o Solicitante:</label>
                                                <textarea name="resposta" placeholder="Digite uma solução ou orientação para o usuário..." rows="3" class="input-pequeno"><?= e($c['resposta']) ?></textarea>
                                            </div>

                                            <button type="submit" class="btn-salvar-sm" style="margin-top:10px;"><i data-lucide="save"></i> Salvar Alterações & Encaminhar</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php include 'rodape.php'; ?>
