<?php
// Cria o PRIMEIRO Super Admin sem precisar de SQL.
// Só funciona enquanto NÃO existir nenhum administrador no sistema.
require_once 'funcoes.php';
require_once 'conexao.php';

if (existe_admin($conn)) {
    mensagem('falha', 'O sistema já possui um administrador. Peça a um Super Admin para criar ou promover contas.');
    ir(usuario_logado() ? destino_inicial() : 'index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        mensagem('falha', 'Sua sessão expirou. Tente novamente.');
        ir('primeiro_acesso.php');
    }

    $nome      = trim($_POST['nome'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $senha     = $_POST['senha'] ?? '';
    $rep_senha = $_POST['rep_senha'] ?? '';

    guardar_antigo(['nome' => $nome, 'email' => $email]);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        mensagem('falha', 'Digite um e-mail válido.');
        ir('primeiro_acesso.php');
    }
    if (strlen($senha) < 8) {
        mensagem('falha', 'A senha deve ter no mínimo 8 caracteres.');
        ir('primeiro_acesso.php');
    }
    if ($senha !== $rep_senha) {
        mensagem('falha', 'As senhas não coincidem.');
        ir('primeiro_acesso.php');
    }

    try {
        $stmt = $conn->prepare('SELECT id, nome, senha FROM login WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($id_existente, $nome_existente, $hash_existente);
        $achou = $stmt->fetch();
        $stmt->close();

        if ($achou) {
            // Já existe uma conta com esse e-mail: promove (exige a senha dela).
            if (!password_verify($senha, $hash_existente)) {
                mensagem('falha', 'Já existe uma conta com este e-mail, mas a senha não confere.');
                ir('primeiro_acesso.php');
            }
            $id_admin   = (int) $id_existente;
            $nome_admin = $nome_existente;
            $stmt = $conn->prepare("UPDATE login SET perfil = 'admin', nivel_admin = 'todos' WHERE id = ?");
            $stmt->bind_param('i', $id_admin);
            $stmt->execute();
            $stmt->close();
        } else {
            if (mb_strlen($nome, 'UTF-8') < 2 || mb_strlen($nome, 'UTF-8') > 100) {
                mensagem('falha', 'Informe o nome (até 100 caracteres).');
                ir('primeiro_acesso.php');
            }
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO login (nome, email, senha, perfil, nivel_admin) VALUES (?, ?, ?, 'admin', 'todos')");
            $stmt->bind_param('sss', $nome, $email, $hash);
            $stmt->execute();
            $id_admin   = (int) $stmt->insert_id;
            $nome_admin = $nome;
            $stmt->close();
        }

        limpar_antigo();
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id'          => $id_admin,
            'nome'        => $nome_admin,
            'email'       => $email,
            'perfil'      => 'admin',
            'nivel_admin' => 'todos',
        ];

        mensagem('sucesso', 'Super Admin configurado! Em "Usuários" você cria os demais acessos.');
        ir('admin_usuarios.php');
    } catch (mysqli_sql_exception $ex) {
        error_log('Erro no primeiro acesso: ' . $ex->getMessage());
        mensagem('falha', 'Não foi possível concluir a configuração.');
        ir('primeiro_acesso.php');
    }
}

$titulo = 'Primeiro Acesso';
$layout = 'auth';
include 'cabecalho.php';
?>
    <section class="cartao" aria-labelledby="titulo">
        <nav class="abas" aria-label="Acesso">
            <a href="index.php"><i data-lucide="log-in"></i> Entrar</a>
            <a href="primeiro_acesso.php" class="ativa" aria-current="page"><i data-lucide="shield-plus"></i> Primeiro acesso</a>
        </nav>

        <h1 id="titulo">Criar Super Admin</h1>
        <p class="subtitulo">Ainda não há administradores. Esta conta verá todos os chamados e poderá criar os demais usuários. Se o e-mail já tiver cadastro, informe a senha dele para promovê-lo.</p>

        <?= bloco_mensagem($mensagem) ?>

        <form action="primeiro_acesso.php" method="post" novalidate data-validar>
            <?= csrf_campo() ?>

            <div class="campo">
                <label for="nome"><i data-lucide="user"></i> Nome Completo</label>
                <input id="nome" name="nome" type="text" autocomplete="name" maxlength="100"
                       placeholder="Nome do administrador" value="<?= antigo('nome') ?>" data-regra="obrigatorio">
                <span class="erro" id="erro-nome" aria-live="polite"></span>
            </div>

            <div class="campo">
                <label for="email"><i data-lucide="mail"></i> E-mail</label>
                <input id="email" name="email" type="email" autocomplete="email" maxlength="255"
                       placeholder="admin@empresa.com" value="<?= antigo('email') ?>" data-regra="email">
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
                           placeholder="Repita a senha" data-regra="rep_senha">
                    <button type="button" class="mostrar" data-alvo="rep_senha"><i data-lucide="eye"></i></button>
                </div>
                <span class="erro" id="erro-rep_senha" aria-live="polite"></span>
            </div>

            <button type="submit" class="botao"><i data-lucide="shield-check"></i> CRIAR SUPER ADMIN</button>
        </form>
    </section>
<?php
limpar_antigo();
include 'rodape.php';
