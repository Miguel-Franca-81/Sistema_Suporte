-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 30-Out-2026 às 01:35
-- Versão do servidor: 10.4.27-MariaDB
-- versão do PHP: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sistema`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome_usuario` varchar(255) DEFAULT NULL,
  `departamento` varchar(140) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `tel_usuario` varchar(11) DEFAULT NULL,
  `desc_nome_eq` varchar(200) DEFAULT NULL,
  `num_eq` varchar(40) DEFAULT NULL,
  `desc_bo` varchar(255) DEFAULT NULL,
  `codigo` varchar(4) DEFAULT NULL,
  `senha` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Acionadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `ts_usuarios_codigo` BEFORE INSERT ON `usuarios` FOR EACH ROW BEGIN
    -- Busca qual será o próximo ID gerado automaticamente
    DECLARE proximo_id INT;
    SELECT AUTO_INCREMENT INTO proximo_id 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios';
    
    -- Grava o código formatado com zeros (ex: '0001')
    SET NEW.codigo = LPAD(proximo_id, 4, '0');
END
$$
DELIMITER ;

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
