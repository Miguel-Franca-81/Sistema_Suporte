<?php
require_once 'funcoes.php';
require_once 'conexao.php';

exigir_login();

if (usuario_logado()['perfil'] === 'admin') {
    ir('admin.php');
}

$titulo = 'Central do Cliente';
$layout = 'app';

$usuario_id = usuario_logado()['id'];
$meus_chamados = [];
try {
    $stmt = $conn->prepare('SELECT id, criado_em, departamento, andar, telefone, equipamento, num_eq, descricao, status, nivel, fila, resposta, historico FROM chamados WHERE usuario_id = ? ORDER BY id DESC');
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $meus_chamados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {}

include 'cabecalho.php';
?>
    <section class="cartao cartao-largo">
        <div class="cartao-header">
            <div>
                <h1><i data-lucide="plus-circle"></i> NOVO CHAMADO DE SUPORTE</h1>
                <p class="subtitulo">Preencha o formulário para acionar a equipe NEXUS.</p>
            </div>
            <div class="solicitante-badge">
                <i data-lucide="user"></i>
                <div>
                    <strong><?= e($usuario['nome']) ?></strong>
                    <small><?= e($usuario['email']) ?></small>
                </div>
            </div>
        </div>

        <?= bloco_mensagem($mensagem) ?>

        <form action="salvar_chamado.php" method="post" novalidate data-validar class="form-grid">
            <?= csrf_campo() ?>

            <div class="linha">
                <div class="campo">
                    <label for="departamento"><i data-lucide="building-2"></i> Departamento</label>
                    <input id="departamento" name="departamento" type="text" maxlength="140"
                           placeholder="Ex.: Financeiro / T.I." value="<?= antigo('departamento') ?>" data-regra="obrigatorio">
                    <span class="erro" id="erro-departamento" aria-live="polite"></span>
                </div>

                <div class="campo">
                    <label for="andar"><i data-lucide="map-pin"></i> Andar / Localização</label>
                    <input id="andar" name="andar" type="text" maxlength="50" placeholder="Ex.: 3º Andar / Bloco B"
                           value="<?= antigo('andar') ?>" data-regra="obrigatorio">
                    <span class="erro" id="erro-andar" aria-live="polite"></span>
                </div>
            </div>

            <div class="linha">
                <div class="campo">
                    <label for="telefone"><i data-lucide="phone"></i> Telefone / Ramal</label>
                    <input id="telefone" name="telefone" type="tel" inputmode="tel" maxlength="20"
                           placeholder="(11) 99999-9999 ou Ramal 102" autocomplete="tel" value="<?= antigo('telefone') ?>" data-regra="telefone">
                    <span class="erro" id="erro-telefone" aria-live="polite"></span>
                </div>

                <div class="campo">
                    <label for="equipamento"><i data-lucide="monitor"></i> Equipamento / Ativo</label>
                    <input id="equipamento" name="equipamento" type="text" maxlength="200"
                           placeholder="Ex.: Notebook Dell / Impressora HP" value="<?= antigo('equipamento') ?>" data-regra="obrigatorio">
                    <span class="erro" id="erro-equipamento" aria-live="polite"></span>
                </div>
            </div>

            <div class="campo">
                <label for="num_eq"><i data-lucide="barcode"></i> Nº de Patrimônio / Série (Opcional)</label>
                <input id="num_eq" name="num_eq" type="text" maxlength="40"
                       placeholder="Ex.: PAT-994821" value="<?= antigo('num_eq') ?>">
            </div>

            <div class="campo">
                <label for="descricao"><i data-lucide="file-text"></i> Detalhes do Incidente</label>
                <textarea id="descricao" name="descricao" rows="4" maxlength="2000"
                          placeholder="Descreva o problema com o máximo de detalhes..." data-regra="descricao"><?= antigo('descricao') ?></textarea>
                <span class="erro" id="erro-descricao" aria-live="polite"></span>
            </div>

            <button type="submit" class="botao"><i data-lucide="send"></i> TRANSMITIR CHAMADO</button>
        </form>

        <div class="secao-divisor">
            <h2><i data-lucide="history"></i> MEU HISTÓRICO DE SOLICITAÇÕES</h2>
        </div>

        <div class="tabela-area">
            <table class="tabela-expansivel">
                <thead>
                    <tr>
                        <th style="width: 50px;"></th>
                        <th>Nº ID</th>
                        <th>Data Abertura</th>
                        <th>Equipamento</th>
                        <th>Nível</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($meus_chamados)): ?>
                        <tr><td colspan="7" class="sem-dados">Nenhum chamado aberto até o momento.</td></tr>
                    <?php else: ?>
                        <?php foreach ($meus_chamados as $mc): ?>
                            <tr class="linha-resumo">
                                <td>
                                    <button type="button" class="btn-expandir" data-target="detalhes-usr-<?= $mc['id'] ?>" title="Expandir/Minimizar">
                                        <i data-lucide="chevron-right" class="icon-expand"></i>
                                    </button>
                                </td>
                                <td><span class="tag-id">#<?= e(codigo_chamado($mc['id'])) ?></span></td>
                                <td><?= e(date('d/m/Y H:i', strtotime($mc['criado_em']))) ?></td>
                                <td><strong><?= e($mc['equipamento']) ?></strong></td>
                                <td><span class="status status-nivel-<?= strtolower($mc['nivel']) ?>"><?= e($mc['nivel']) ?></span></td>
                                <td><span class="status status-<?= e($mc['status']) ?>"><?= e($mc['status']) ?></span></td>
                                <td>
                                    <a href="chat.php?chamado_id=<?= $mc['id'] ?>" class="btn-chat-sm"><i data-lucide="message-square"></i> Chat</a>
                                </td>
                            </tr>
                            <tr id="detalhes-usr-<?= $mc['id'] ?>" class="linha-detalhes oculta">
                                <td colspan="7">
                                    <div class="detalhes-container">
                                        <div class="detalhes-grid">
                                            <div>
                                                <strong><i data-lucide="building-2"></i> Departamento / Local:</strong>
                                                <p><?= e($mc['departamento']) ?> - <?= e($mc['andar']) ?></p>
                                            </div>
                                            <div>
                                                <strong><i data-lucide="phone"></i> Contato:</strong>
                                                <p><?= e($mc['telefone']) ?></p>
                                            </div>
                                            <div>
                                                <strong><i data-lucide="barcode"></i> Patrimônio:</strong>
                                                <p><?= e($mc['num_eq'] ?: 'Não informado') ?></p>
                                            </div>
                                            <div>
                                                <strong><i data-lucide="layers"></i> Fila Atual:</strong>
                                                <p><?= e($mc['fila']) ?></p>
                                            </div>
                                        </div>
                                        <div class="detalhes-secao">
                                            <strong><i data-lucide="align-left"></i> Descrição do Incidente:</strong>
                                            <p class="caixa-texto"><?= nl2br(e($mc['descricao'])) ?></p>
                                        </div>
                                        <?php if ($mc['resposta']): ?>
                                            <div class="detalhes-secao resposta-admin">
                                                <strong><i data-lucide="check-circle"></i> Resposta do Suporte:</strong>
                                                <p><?= nl2br(e($mc['resposta'])) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php
limpar_antigo();
include 'rodape.php';
