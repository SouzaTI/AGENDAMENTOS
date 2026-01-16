-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 14/07/2025 às 09:30
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `recebimento`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `agendamentos`
--

CREATE TABLE `agendamentos` (
  `id` int(11) NOT NULL,
  `data_agendamento` date NOT NULL,
  `tipo_caminhao` varchar(50) NOT NULL,
  `tipo_carga` varchar(50) NOT NULL,
  `tipo_mercadoria` varchar(100) NOT NULL,
  `fornecedor` varchar(100) NOT NULL,
  `quantidade_paletes` int(11) NOT NULL,
  `quantidade_volumes` int(11) NOT NULL,
  `placa` varchar(10) NOT NULL,
  `status` enum('','Chegada NF','Em Analise','Liberado','Recebendo','Recebido','Recusado') NOT NULL,
  `comprador` varchar(100) NOT NULL,
  `nome_motorista` varchar(100) DEFAULT NULL,
  `cpf_motorista` varchar(14) DEFAULT NULL,
  `numero_contato` varchar(20) DEFAULT NULL,
  `tipo_recebimento` varchar(30) DEFAULT NULL,
  `local_recebimento` varchar(255) DEFAULT NULL,
  `senha` int(11) DEFAULT NULL,
  `nome_responsavel` varchar(255) DEFAULT NULL,
  `data_em_analise` datetime DEFAULT NULL,
  `data_recebendo` datetime DEFAULT NULL,
  `data_liberado` datetime DEFAULT NULL,
  `tempo` time DEFAULT NULL,
  `chegada_nf` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

-- Estrutura para tabela `chamadas`

CREATE TABLE `chamadas` (
  `agendamento_id` int(11) NOT NULL,
  `chamada_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

-- Estrutura para tabela `conferencias_recebimento`

CREATE TABLE `conferencias_recebimento` (
  `id` int(11) NOT NULL,
  `agendamento_id` int(11) NOT NULL,
  `senha` int(11) NOT NULL,
  `paletes_recebidos` int(11) DEFAULT 0,
  `volumes_recebidos` int(11) DEFAULT 0,
  `observacoes` varchar(500) DEFAULT NULL,
  `nome_conferente` varchar(100) DEFAULT NULL,
  `data_conferencia` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Estrutura para tabela `dias_bloqueados`

CREATE TABLE `dias_bloqueados` (
  `id` int(11) NOT NULL,
  `data` date NOT NULL,
  `motivo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

-- Estrutura para tabela `limites_agendamentos`

CREATE TABLE `limites_agendamentos` (
  `id` int(11) NOT NULL,
  `tipo_caminhao` varchar(50) DEFAULT NULL,
  `limite` int(11) DEFAULT NULL,
  `data` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

-- Estrutura para tabela `usuarios`

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nome_completo` varchar(100) NOT NULL,
  `tipo` enum('admin','usuario','operacional') DEFAULT 'usuario'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

-- Estrutura para tabela `visitas`

CREATE TABLE `visitas` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `regiao` varchar(100) DEFAULT NULL,
  `pais` varchar(10) DEFAULT NULL,
  `navegador` text DEFAULT NULL,
  `data_hora` datetime DEFAULT current_timestamp(),
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Índices de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `chamadas`
--
ALTER TABLE `chamadas`
  ADD PRIMARY KEY (`agendamento_id`);

--
-- Índices de tabela `conferencias_recebimento`
--
ALTER TABLE `conferencias_recebimento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agendamento_id` (`agendamento_id`);

--
-- Índices de tabela `dias_bloqueados`
--
ALTER TABLE `dias_bloqueados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `data` (`data`);

--
-- Índices de tabela `limites_agendamentos`
--
ALTER TABLE `limites_agendamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tipo_caminhao` (`tipo_caminhao`),
  ADD UNIQUE KEY `unica_data_tipo` (`data`,`tipo_caminhao`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Índices de tabela `visitas`
--
ALTER TABLE `visitas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=253;

--
-- AUTO_INCREMENT de tabela `conferencias_recebimento`
--
ALTER TABLE `conferencias_recebimento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT de tabela `dias_bloqueados`
--
ALTER TABLE `dias_bloqueados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `limites_agendamentos`
--
ALTER TABLE `limites_agendamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `visitas`
--
ALTER TABLE `visitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `conferencias_recebimento`
--
ALTER TABLE `conferencias_recebimento`
  ADD CONSTRAINT `conferencias_recebimento_ibfk_1` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
