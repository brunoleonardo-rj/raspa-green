<?php
$host = '109.199.116.80';
$db   = 'raspoujalevou';
$user = 'raspoujalevou';
$pass = 'YKeNkNjJ8hTp7DCF';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$site = $pdo->query("SELECT nome_site, logo, deposito_min, saque_min, cpa_padrao, revshare_padrao FROM config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$nomeSite = $site['nome_site'] ?? ''; 
$logoSite = $site['logo'] ?? '';
$depositoMin = $site['deposito_min'] ?? 10;
$saqueMin = $site['saque_min'] ?? 50;
$cpaPadrao = $site['cpa_padrao'] ?? 10;
$revshare_padrao = $site['revshare_padrao'] ?? 10;