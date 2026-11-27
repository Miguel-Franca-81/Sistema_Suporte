<?php
// NEXUS - Persistência: criação automática do banco + backup/restauração em arquivo.
//
// Como funciona:
//  1. Toda vez que o site salva algo (POST), uma cópia completa dos dados é gravada
//     em  dados/backup.dados.php  (dentro da pasta do site).
//  2. Se o banco MySQL estiver vazio (site recém-baixado/instalado em outro PC),
//     os dados são restaurados sozinhos a partir desse arquivo.
//  Resultado: basta levar a pasta do site inteira (incluindo "dados") para não perder nada.

const NEXUS_TABELAS   = ['login', 'chamados', 'mensagens_chat']; // ordem respeita as chaves estrangeiras
const NEXUS_PASTA     = __DIR__ . '/dados';
const NEXUS_ARQUIVO   = NEXUS_PASTA . '/backup.dados.php';
// A 1ª linha impede que o arquivo seja lido pelo navegador, mesmo sem .htaccess.
const NEXUS_PREFIXO   = "<?php http_response_code(404); exit; ?>\n";

/* ------------------------------------------------------------------ */
/*  Esquema (equivale ao banco.sql, mas sem apagar nada)               */
/* ------------------------------------------------------------------ */

function criar_esquema($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `login` (
      `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `nome`        VARCHAR(100) NOT NULL,
      `email`       VARCHAR(255) NOT NULL,
      `senha`       VARCHAR(255) NOT NULL,
      `perfil`      ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
      `nivel_admin` ENUM('N1','N2','N3','todos') NOT NULL DEFAULT 'N1',
      `criado_em`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_login_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS `chamados` (
      `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `usuario_id`   INT UNSIGNED NOT NULL,
      `departamento` VARCHAR(140) NOT NULL,
      `andar`        VARCHAR(50)  NOT NULL,
      `telefone`     VARCHAR(11)  NOT NULL,
      `equipamento`  VARCHAR(200) NOT NULL,
      `num_eq`       VARCHAR(40)  DEFAULT NULL,
      `descricao`    TEXT NOT NULL,
      `status`       ENUM('aberto','andamento','concluido') NOT NULL DEFAULT 'aberto',
      `nivel`        ENUM('N1','N2','N3') NOT NULL DEFAULT 'N1',
      `fila`         VARCHAR(100) NOT NULL DEFAULT 'Fila Inicial N1',
      `resposta`     TEXT DEFAULT NULL,
      `historico`    TEXT DEFAULT NULL,
      `criado_em`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_chamados_usuario` (`usuario_id`),
      CONSTRAINT `fk_chamados_login` FOREIGN KEY (`usuario_id`)
        REFERENCES `login` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS `mensagens_chat` (
      `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `chamado_id` INT UNSIGNED NOT NULL,
      `usuario_id` INT UNSIGNED NOT NULL,
      `mensagem`   TEXT NOT NULL,
      `criado_em`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_chat_chamado` (`chamado_id`),
      CONSTRAINT `fk_chat_chamado` FOREIGN KEY (`chamado_id`)
        REFERENCES `chamados` (`id`) ON DELETE CASCADE,
      CONSTRAINT `fk_chat_usuario` FOREIGN KEY (`usuario_id`)
        REFERENCES `login` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Atualiza bancos antigos (substitui o "ALTER TABLE" manual).
function garantir_coluna($conn, $tabela, $coluna, $definicao) {
    $r = $conn->query("SHOW COLUMNS FROM `$tabela` LIKE '$coluna'");
    if ($r->num_rows === 0) {
        $conn->query("ALTER TABLE `$tabela` ADD COLUMN `$coluna` $definicao");
    }
}

function preparar_banco($conn) {
    static $feito = false;
    if ($feito) {
        return;
    }
    $feito = true;

    criar_esquema($conn);
    garantir_coluna($conn, 'login', 'nivel_admin', "ENUM('N1','N2','N3','todos') NOT NULL DEFAULT 'N1' AFTER `perfil`");
    garantir_coluna($conn, 'chamados', 'nivel', "ENUM('N1','N2','N3') NOT NULL DEFAULT 'N1'");
    garantir_coluna($conn, 'chamados', 'fila', "VARCHAR(100) NOT NULL DEFAULT 'Fila Inicial N1'");
    garantir_coluna($conn, 'chamados', 'resposta', 'TEXT DEFAULT NULL');
    garantir_coluna($conn, 'chamados', 'historico', 'TEXT DEFAULT NULL');

    // Banco vazio + existe backup na pasta do site => restaura sozinho.
    $total = (int) $conn->query('SELECT COUNT(*) FROM `login`')->fetch_row()[0];
    if ($total === 0) {
        $dados = ler_arquivo_backup(NEXUS_ARQUIVO);
        if ($dados !== null) {
            try {
                restaurar_backup($conn, $dados);
            } catch (Throwable $ex) {
                error_log('NEXUS: falha ao restaurar backup automático: ' . $ex->getMessage());
            }
        }
    }
}

/* ------------------------------------------------------------------ */
/*  Exportar / salvar                                                  */
/* ------------------------------------------------------------------ */

function exportar_backup($conn) {
    $dados = [
        'sistema'   => 'NEXUS',
        'versao'    => 1,
        'gerado_em' => date('c'),
        'tabelas'   => [],
    ];
    foreach (NEXUS_TABELAS as $t) {
        $dados['tabelas'][$t] = $conn->query("SELECT * FROM `$t` ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    }
    return $dados;
}

function json_backup(array $dados) {
    // JSON_HEX_TAG troca < e > por \u003C/\u003E: o arquivo nunca contém "<?php" literal.
    return json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE);
}

function preparar_pasta_dados() {
    if (!is_dir(NEXUS_PASTA)) {
        @mkdir(NEXUS_PASTA, 0775, true);
    }
    $ht = NEXUS_PASTA . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n");
    }
    $idx = NEXUS_PASTA . '/index.html';
    if (!is_file($idx)) {
        @file_put_contents($idx, '');
    }
}

// Grava o backup no arquivo (por padrão, o arquivo principal da pasta "dados").
function salvar_backup($conn, $destino = NEXUS_ARQUIVO) {
    try {
        preparar_pasta_dados();
        $conteudo = NEXUS_PREFIXO . json_backup(exportar_backup($conn));
        $tmp = $destino . '.tmp';
        if (file_put_contents($tmp, $conteudo, LOCK_EX) === false) {
            error_log('NEXUS: não foi possível gravar ' . $tmp . ' (verifique permissão de escrita na pasta dados).');
            return false;
        }
        return rename($tmp, $destino);
    } catch (Throwable $ex) {
        error_log('NEXUS: falha ao salvar backup: ' . $ex->getMessage());
        return false;
    }
}

/* ------------------------------------------------------------------ */
/*  Ler / restaurar                                                    */
/* ------------------------------------------------------------------ */

function decodificar_backup($texto) {
    if (strpos($texto, NEXUS_PREFIXO) === 0) {
        $texto = substr($texto, strlen(NEXUS_PREFIXO));
    }
    $dados = json_decode($texto, true);
    if (!is_array($dados) || !isset($dados['tabelas']) || !is_array($dados['tabelas'])) {
        return null;
    }
    return $dados;
}

function ler_arquivo_backup($caminho) {
    if (!is_file($caminho) || !is_readable($caminho)) {
        return null;
    }
    return decodificar_backup((string) file_get_contents($caminho));
}

// SUBSTITUI todos os dados atuais pelos do backup (tudo ou nada: usa transação).
function restaurar_backup($conn, array $dados) {
    $conn->begin_transaction();
    try {
        $conn->query('SET FOREIGN_KEY_CHECKS = 0');

        foreach (array_reverse(NEXUS_TABELAS) as $t) {
            $conn->query("DELETE FROM `$t`");
        }

        foreach (NEXUS_TABELAS as $t) {
            $linhas = $dados['tabelas'][$t] ?? [];
            if (!is_array($linhas) || count($linhas) === 0) {
                continue;
            }

            // Só aceita colunas que realmente existem na tabela (o arquivo pode ser enviado por upload).
            $reais = [];
            foreach ($conn->query("SHOW COLUMNS FROM `$t`")->fetch_all(MYSQLI_ASSOC) as $col) {
                $reais[] = $col['Field'];
            }
            $colunas = array_values(array_intersect(array_keys($linhas[0]), $reais));
            if (count($colunas) === 0) {
                continue;
            }

            $lista = '`' . implode('`,`', $colunas) . '`';
            $marcas = implode(',', array_fill(0, count($colunas), '?'));
            $stmt = $conn->prepare("INSERT INTO `$t` ($lista) VALUES ($marcas)");
            $tipos = str_repeat('s', count($colunas));

            foreach ($linhas as $linha) {
                $valores = [];
                foreach ($colunas as $c) {
                    $valores[] = $linha[$c] ?? null;
                }
                $stmt->bind_param($tipos, ...$valores);
                $stmt->execute();
            }
            $stmt->close();
        }

        $conn->query('SET FOREIGN_KEY_CHECKS = 1');
        $conn->commit();
    } catch (Throwable $ex) {
        $conn->rollback();
        try { $conn->query('SET FOREIGN_KEY_CHECKS = 1'); } catch (Throwable $ignorar) {}
        throw $ex;
    }
}

function resumo_backup(array $dados) {
    $r = [];
    foreach (NEXUS_TABELAS as $t) {
        $r[$t] = count($dados['tabelas'][$t] ?? []);
    }
    return $r;
}
