-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 27-Ago-2025 às 10:59
-- Versão do servidor: 5.7.44-log
-- versão do PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `raspa-green`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `banners`
--

CREATE TABLE `banners` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `banner_img` varchar(255) NOT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `ordem` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `banners`
--

INSERT INTO `banners` (`id`, `banner_img`, `ativo`, `ordem`) VALUES
(1, '/assets/banners/banner_687cd3a026c04.png', 1, 2),
(2, '/assets/banners/banner_687cd3bbbc2cb.png', 1, 1),
(3, '/assets/banners/banner_687cd323dee39.png', 1, 3);

-- --------------------------------------------------------

--
-- Estrutura da tabela `config`
--

CREATE TABLE `config` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome_site` varchar(255) DEFAULT 'Raspadinha',
  `logo` varchar(255) DEFAULT NULL,
  `deposito_min` float NOT NULL DEFAULT '0',
  `saque_min` float NOT NULL DEFAULT '0',
  `cpa_padrao` float NOT NULL DEFAULT '0',
  `revshare_padrao` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `config`
--

INSERT INTO `config` (`id`, `nome_site`, `logo`, `deposito_min`, `saque_min`, `cpa_padrao`, `revshare_padrao`) VALUES
(1, 'RaspaGreen', '/assets/upload/68a283dfa9c4b.png', 5, 5, 0, 10);

-- --------------------------------------------------------

--
-- Estrutura da tabela `depositos`
--

CREATE TABLE `depositos` (
  `id` int(11) NOT NULL,
  `transactionId` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `status` enum('PENDING','PAID','WAITING_FOR_APPROVAL','PAID_OUT','success') DEFAULT 'PENDING',
  `qrcode` text,
  `idempotency_key` varchar(255) DEFAULT NULL,
  `gateway` enum('pixup','digitopay','gatewayproprio') NOT NULL,
  `webhook_data` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--
-- Estrutura da tabela `gateway`
--

CREATE TABLE `gateway` (
  `id` int(10) UNSIGNED NOT NULL,
  `active` enum('gatewayproprio') DEFAULT 'gatewayproprio',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `gateway`
--

INSERT INTO `gateway` (`id`, `active`, `created_at`, `updated_at`) VALUES
(1, 'gatewayproprio', '2025-07-11 13:39:55', '2025-08-17 01:12:10');

-- --------------------------------------------------------

--
-- Estrutura da tabela `gatewayproprio`
--

CREATE TABLE `gatewayproprio` (
  `id` int(10) UNSIGNED NOT NULL,
  `url` varchar(255) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `gatewayproprio`
--

INSERT INTO `gatewayproprio` (`id`, `url`, `api_key`, `created_at`, `updated_at`) VALUES
(1, 'https://api.cassiopay.com.br', 'API_KEY', '2025-07-24 21:26:43', '2025-07-24 21:41:09');

-- --------------------------------------------------------

--
-- Estrutura da tabela `historico_revshare`
--

CREATE TABLE `historico_revshare` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `afiliado_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `valor_apostado` decimal(10,2) NOT NULL,
  `valor_revshare` decimal(10,2) NOT NULL,
  `percentual` float NOT NULL,
  `tipo` enum('perda_usuario','ganho_usuario') NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `raspadinha_id` int(10) UNSIGNED NOT NULL,
  `status` tinyint(1) DEFAULT '0',
  `resultado` enum('loss','gain') DEFAULT NULL,
  `valor_ganho` decimal(10,2) DEFAULT '0.00',
  `premios_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `raspadinhas`
--

CREATE TABLE `raspadinhas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(120) NOT NULL,
  `descricao` text,
  `banner` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `raspadinhas`
--

INSERT INTO `raspadinhas` (`id`, `nome`, `descricao`, `banner`, `valor`, `created_at`) VALUES
(1, 'SONHO PREMIADO - R$ 2,00 - PRÊMIOS DE ATÉ R$5.000,00 ', 'Com só R$2, você raspa e pode levar prêmios exclusivos, gadgets, ou R$5000 na conta.', '/assets/img/banners/687ce7f33afe8.png', '2.00', '2025-07-11 21:55:04'),
(2, 'MEGA RASPADA BLACK 🖤💰 - R$10,00 - PRÊMIOS DE ATÉ R$20.000,00', 'Com R$10 na raspada você ativa a chance de faturar uma bolada até R$20.000. Prêmio bruto, imediato.', '/assets/img/banners/687ce824a04ed.png', '10.00', '2025-07-11 21:55:04'),
(3, '🔥 PIX TURBINADO - R$ 1,00 - PRÊMIOS DE ATÉ R$2.500,00', 'Raspa por apenas R$1 e pode explodir até R$2500 direto no PIX.', '/assets/img/banners/687ce7af59f64.png', '1.00', '2025-07-16 19:19:31'),
(4, 'OSTENTAÇÃO INSTANTÂNEA 💎 - R$5,00 - PRÊMIOS DE ATÉ R$10.000,00', 'R$5 pra raspar e a chance real de garantir eletrônicos top ou até R$10.000 em PIX.', '/assets/img/banners/687cea40caafd.png', '5.00', '2025-07-19 18:07:00');

-- --------------------------------------------------------

--
-- Estrutura da tabela `raspadinha_premios`
--

CREATE TABLE `raspadinha_premios` (
  `id` int(10) UNSIGNED NOT NULL,
  `raspadinha_id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(120) NOT NULL,
  `icone` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `probabilidade` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `raspadinha_premios`
--

INSERT INTO `raspadinha_premios` (`id`, `raspadinha_id`, `nome`, `icone`, `valor`, `probabilidade`) VALUES
(29, 4, 'NADA 😬', '/assets/img/icons/687c106fb01ac.png', '0.00', '30.00'),
(30, 4, 'R$1,00 NO PIX', '/assets/img/icons/687c09ddc2027.png', '0.50', '18.00'),
(31, 4, 'R$5,00 NO PIX', '/assets/img/icons/687c09f749f8b.png', '5.00', '12.00'),
(32, 4, 'R$10,00 NO PIX', '/assets/img/icons/687c0a1e0b378.png', '10.00', '8.00'),
(33, 4, 'R$15,00 NO PIX', '/assets/img/icons/687c24d23eed0.png', '15.00', '6.00'),
(34, 4, 'R$20,00 NO PIX', '/assets/img/icons/687c0b01a04a4.png', '20.00', '4.50'),
(35, 4, 'R$50,00 NO PIX', '/assets/img/icons/687c0b433da67.png', '50.00', '4.00'),
(36, 4, 'R$100,00 NO PIX', '/assets/img/icons/687c0dbbb87e4.png', '100.00', '3.00'),
(37, 4, 'R$150,00 NO PIX', '/assets/img/icons/687c263842548.png', '150.00', '2.20'),
(38, 4, 'R$200,00 NO PIX', '/assets/img/icons/687c0c3f09c6d.png', '200.00', '2.10'),
(39, 4, 'Cafeteira Expresso Dolce Gusto', '/assets/img/icons/687c0c9a1f22a.png', '500.00', '2.00'),
(40, 4, 'Lava e Seca Samsung', '/assets/img/icons/687c0cc6bb984.png', '3500.00', '0.40'),
(41, 4, 'Notebook Gamer ', '/assets/img/icons/687cd625b0136.png', '4000.00', '1.50'),
(42, 4, 'Smart TV Samsung 70\"', '/assets/img/icons/687c0d36c8044.png', '5000.00', '1.40'),
(43, 4, 'R$1.000,00 NO PIX', '/assets/img/icons/687c0f4e1f147.png', '1000.00', '1.90'),
(44, 4, 'R$3.000,00 NO PIX', '/assets/img/icons/687c0f6ac9a5e.png', '3000.00', '1.60'),
(45, 4, 'iPhone 15 PRO MAX', '/assets/img/icons/687c0fe6b612a.png', '6000.00', '0.75'),
(46, 4, 'R$10.000,00 NO PIX', '/assets/img/icons/687c1030df2ef.png', '10000.00', '0.30'),
(47, 3, 'NADA 😬', '/assets/img/icons/687c0254729ef.png', '0.00', '25.00'),
(48, 3, 'R$1,00 NO PIX', '/assets/img/icons/687be92f11610.png', '0.50', '15.00'),
(49, 3, 'R$2,00 NO PIX', '/assets/img/icons/687bea587e903.png', '2.00', '11.00'),
(50, 3, 'R$5,00 NO PIX', '/assets/img/icons/687bfdd13689e.png', '5.00', '9.00'),
(51, 3, 'R$10,00 NO PIX', '/assets/img/icons/687beabea5f53.png', '10.00', '7.70'),
(52, 3, 'R$20,00 NO PIX', '/assets/img/icons/687beaf761686.png', '20.00', '6.00'),
(53, 3, 'R$15,00 NO PIX', '/assets/img/icons/687c248f70bc8.png', '15.00', '7.50'),
(54, 3, 'R$50,00 NO PIX', '/assets/img/icons/687bfad6bca49.png', '50.00', '5.30'),
(55, 3, 'TV 32 polegadas Smart', '/assets/img/icons/687be97e55304.png', '1000.00', '1.80'),
(56, 3, 'JBL BOOMBOX 3', '/assets/img/icons/687bfb8a5b1c6.png', '2000.00', '0.20'),
(57, 3, 'R$1.500,00 NO PIX', '/assets/img/icons/687be9cb1abad.png', '1500.00', '1.50'),
(58, 3, 'R$2.500,00 NO PIX', '/assets/img/icons/687bfc8ee5723.png', '2500.00', '0.10'),
(59, 1, 'NADA 😬', '/assets/img/icons/687c0272d42cc.png', '0.00', '30.00'),
(60, 1, 'R$1,00 NO PIX', '/assets/img/icons/687c029628796.png', '0.50', '15.00'),
(61, 1, 'R$5,00 NO PIX', '/assets/img/icons/687c036f22866.png', '5.00', '10.00'),
(62, 1, 'R$10,00 NO PIX', '/assets/img/icons/687c072e05d74.png', '10.00', '8.00'),
(63, 1, 'R$15,00 NO PIX', '/assets/img/icons/687c24eeda1dd.png', '15.00', '7.00'),
(64, 1, 'R$20,00 NO PIX', '/assets/img/icons/687cfac0cda45.png', '20.00', '6.00'),
(65, 1, 'R$50,00 NO PIX', '/assets/img/icons/687c032bd36c5.png', '50.00', '4.00'),
(66, 1, 'Air Fryer Britânia', '/assets/img/icons/687c03ea8c3b5.png', '400.00', '2.00'),
(67, 1, 'Microondas', '/assets/img/icons/687c041d18e2f.png', '500.00', '2.00'),
(68, 1, 'R$500,00 NO PIX', '/assets/img/icons/687c07b350a5b.png', '500.00', '4.00'),
(69, 1, 'Bicicleta Caloi', '/assets/img/icons/687c046b401b4.png', '800.00', '2.00'),
(70, 1, 'Xbox Series S', '/assets/img/icons/687c04dea9970.png', '2000.00', '2.50'),
(71, 1, 'R$1.200,00 NO PIX', '/assets/img/icons/687c050c8fc53.png', '1200.00', '2.00'),
(72, 1, 'R$2.000,00 NO PIX', '/assets/img/icons/687c055b21ca9.png', '2000.00', '1.50'),
(73, 1, 'Shineray PT2X', '/assets/img/icons/687c0598a13d0.png', '5000.00', '1.00'),
(74, 2, 'NADA 😬', '/assets/img/icons/687c10c6b1667.png', '0.00', '30.00'),
(77, 2, 'R$5,00 NO PIX', '/assets/img/icons/687c114fee310.png', '5.00', '13.00'),
(78, 2, 'R$20,00 NO PIX', '/assets/img/icons/687c11ee2bc98.png', '20.00', '6.50'),
(79, 2, 'R$15,00 NO PIX', '/assets/img/icons/687c251dd30ab.png', '15.00', '8.00'),
(80, 2, 'R$50,00 NO PIX', '/assets/img/icons/687c124f3477d.png', '50.00', '6.00'),
(81, 2, 'R$100,00 NO PIX', '/assets/img/icons/687c127d17125.png', '100.00', '3.50'),
(82, 2, 'R$200,00 NO PIX', '/assets/img/icons/687c12c9570a1.png', '200.00', '2.50'),
(83, 2, 'R$300,00 NO PIX', '/assets/img/icons/687c2d8e3beef.png', '300.00', '2.00'),
(84, 2, 'R$500,00 NO PIX', '/assets/img/icons/687c14d2bfc79.png', '500.00', '2.00'),
(85, 2, 'R$700,00 NO PIX', '/assets/img/icons/687c169784b00.png', '700.00', '1.80'),
(86, 2, 'R$1.000,00 NO PIX', '/assets/img/icons/687c16bf8d4f9.png', '1000.00', '1.40'),
(87, 2, 'R$3.000,00 NO PIX', '/assets/img/icons/687c1499d7b9f.png', '3000.00', '1.00'),
(88, 2, 'R$5.000,00 NO PIX', '/assets/img/icons/687c17441f4e7.png', '10.00', '0.80'),
(89, 2, 'Geladeira Smart LG', '/assets/img/icons/687c17c36902a.png', '9000.00', '0.60'),
(90, 2, 'iPhone 16 Pro Max ', '/assets/img/icons/687c17f0a903b.png', '7500.00', '0.00'),
(91, 2, 'Moto Honda Pop 110i zero km', '/assets/img/icons/687c1814b5ef1.png', '12500.00', '0.00'),
(92, 2, 'MacBook Pro Apple 14\" M4', '/assets/img/icons/687c184b06fd6.png', '14000.00', '0.00'),
(93, 2, 'Honda PCX 2025 ', '/assets/img/icons/687c18722f07a.png', '20000.00', '0.00');

-- --------------------------------------------------------

--
-- Estrutura da tabela `saques`
--

CREATE TABLE `saques` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transactionId` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `transaction_id_digitopay` varchar(255) DEFAULT NULL,
  `idempotency_key` varchar(255) DEFAULT NULL,
  `digitopay_idempotency_key` varchar(255) DEFAULT NULL,
  `gateway` varchar(50) DEFAULT 'pixup',
  `webhook_data` text,
  `status` enum('PENDING','PAID','CANCELLED','FAILED','PROCESSING','EM PROCESSAMENTO','ANALISE','REALIZADO') DEFAULT 'PENDING',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `transacoes`
--

CREATE TABLE `transacoes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tipo` enum('DEPOSIT','WITHDRAW','REFUND') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `saldo_anterior` decimal(10,2) NOT NULL,
  `saldo_posterior` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL,
  `referencia` varchar(255) DEFAULT NULL,
  `gateway` varchar(50) DEFAULT NULL,
  `descricao` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `transacoes`
--

INSERT INTO `transacoes` (`id`, `user_id`, `tipo`, `valor`, `saldo_anterior`, `saldo_posterior`, `status`, `referencia`, `gateway`, `descricao`, `created_at`) VALUES
(157, 4, 'REFUND', '5.00', '68.00', '73.00', 'COMPLETED', NULL, NULL, 'Estorno de saque reprovado', '2025-08-19 01:40:27'),
(158, 4, 'REFUND', '6.00', '67.00', '73.00', 'COMPLETED', NULL, NULL, 'Estorno de saque reprovado', '2025-08-19 01:42:38');

-- --------------------------------------------------------

--
-- Estrutura da tabela `transacoes_afiliados`
--

CREATE TABLE `transacoes_afiliados` (
  `id` int(11) NOT NULL,
  `afiliado_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `deposito_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `saldo` decimal(10,2) DEFAULT '0.00',
  `indicacao` varchar(100) DEFAULT NULL,
  `comissao_cpa` float DEFAULT '0',
  `comissao_revshare` float DEFAULT '0',
  `banido` tinyint(1) DEFAULT '0',
  `admin` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `influencer` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `telefone`, `email`, `senha`, `saldo`, `indicacao`, `comissao_cpa`, `comissao_revshare`, `banido`, `admin`, `created_at`, `updated_at`, `influencer`) VALUES
(4, 'Administrador', '(85) 986151858', 'admin@admin.com', '$2y$10$teqo8ZeOikaGjmw2///m3O/tfAh4mgH2ZGJMT4QVvxzJmozr2EJxm', '14.00', '', 0, 10, 0, 1, '2025-07-19 19:30:32', '2025-08-27 02:37:45', 0);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_banners_ativo_ordem` (`ativo`,`ordem`);

--
-- Índices para tabela `config`
--
ALTER TABLE `config`
  ADD UNIQUE KEY `id` (`id`);

--
-- Índices para tabela `depositos`
--
ALTER TABLE `depositos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idempotency_key` (`idempotency_key`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices para tabela `gateway`
--
ALTER TABLE `gateway`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `gatewayproprio`
--
ALTER TABLE `gatewayproprio`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `historico_revshare`
--
ALTER TABLE `historico_revshare`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `raspadinhas`
--
ALTER TABLE `raspadinhas`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `raspadinha_premios`
--
ALTER TABLE `raspadinha_premios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `raspadinha_id` (`raspadinha_id`);

--
-- Índices para tabela `saques`
--
ALTER TABLE `saques`
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `idx_saques_transaction_id` (`transaction_id`),
  ADD KEY `idx_saques_idempotency_key` (`idempotency_key`),
  ADD KEY `idx_saques_gateway` (`gateway`);

--
-- Índices para tabela `transacoes`
--
ALTER TABLE `transacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `transacoes_afiliados`
--
ALTER TABLE `transacoes_afiliados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `afiliado_id` (`afiliado_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `deposito_id` (`deposito_id`);

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `banners`
--
ALTER TABLE `banners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `config`
--
ALTER TABLE `config`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `depositos`
--
ALTER TABLE `depositos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `historico_revshare`
--
ALTER TABLE `historico_revshare`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9520;

--
-- AUTO_INCREMENT de tabela `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT de tabela `raspadinhas`
--
ALTER TABLE `raspadinhas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `raspadinha_premios`
--
ALTER TABLE `raspadinha_premios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT de tabela `saques`
--
ALTER TABLE `saques`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `transacoes`
--
ALTER TABLE `transacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT de tabela `transacoes_afiliados`
--
ALTER TABLE `transacoes_afiliados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=476;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `raspadinha_premios`
--
ALTER TABLE `raspadinha_premios`
  ADD CONSTRAINT `raspadinha_premios_ibfk_1` FOREIGN KEY (`raspadinha_id`) REFERENCES `raspadinhas` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `transacoes_afiliados`
--
ALTER TABLE `transacoes_afiliados`
  ADD CONSTRAINT `transacoes_afiliados_ibfk_1` FOREIGN KEY (`afiliado_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `transacoes_afiliados_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `transacoes_afiliados_ibfk_3` FOREIGN KEY (`deposito_id`) REFERENCES `depositos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
