<?php

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use Google\Cloud\Compute\V1\SslCertificate;
use GunterChou\GclbAutoSsl\RenewHttpsProxySslCert;

// 載入環境變數
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// 建立憑證資訊
$sslCertificate = new SslCertificate([
    'name' => 'gclb-auto-ssl-' . date('Ymd-His'),
    'certificate' => file_get_contents($_ENV['CERTIFICATE_PATH']),
    'private_key' => file_get_contents($_ENV['PRIVATE_KEY_PATH']),
]);

RenewHttpsProxySslCert::run($_ENV['PROJECT'], $_ENV['TARGET_PROXY'], $sslCertificate);