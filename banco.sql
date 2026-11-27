-- =====================================================================
-- Banco de dados do sistema NEXUS
-- ATENÇÃO: este arquivo é OPCIONAL. O site cria o banco e as tabelas sozinho
-- no primeiro acesso (veja backup.php). Este script NÃO apaga mais nada:
-- pode ser executado várias vezes sem perder dados.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `sistema`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sistema`;

CREATE TABLE IF NOT EXISTS `login` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome`         VARCHAR(100) NOT NULL,
  `email`        VARCHAR(255) NOT NULL,
  `senha`        VARCHAR(255) NOT NULL,
  `perfil`       ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
  `nivel_admin`  ENUM('N1','N2','N3','todos') NOT NULL DEFAULT 'N1',
  `criado_em`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_login_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chamados` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mensagens_chat` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `chamado_id`   INT UNSIGNED NOT NULL,
  `usuario_id`   INT UNSIGNED NOT NULL,
  `mensagem`     TEXT NOT NULL,
  `criado_em`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chat_chamado` (`chamado_id`),
  CONSTRAINT `fk_chat_chamado` FOREIGN KEY (`chamado_id`)
    REFERENCES `chamados` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `login` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
