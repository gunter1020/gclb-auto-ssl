<?php

namespace GunterChou\GclbAutoSsl;

use Google\Cloud\Compute\V1\SslCertificate;
use Google\Cloud\Compute\V1\SslCertificatesClient;
use Google\Cloud\Compute\V1\TargetHttpsProxiesClient;
use Google\Cloud\Compute\V1\TargetHttpsProxy;

/**
 * 半自動更換 GCLB HTTPS SSL 憑證 主程式
 *
 * @author Gunter Chou <abcd2221925@gmail.com>
 */
class RenewHttpsProxySslCert
{
    /**
     * 執行更換憑證流程
     *
     * @param  string         $project          專案 ID
     * @param  string         $targetHttpsProxy 目標 Proxy
     * @param  SslCertificate $sslCertificate   憑證資訊
     * @return void
     */
    public static function run(string $project, string $targetHttpsProxy, SslCertificate $sslCertificate)
    {
        self::_insertSslCertificate($project, $sslCertificate, function (SslCertificate $sslCertificate) use ($project, $targetHttpsProxy) {
            self::_patchTargetHttpsProxy($project, $targetHttpsProxy, [
                $sslCertificate->getSelfLink(),
            ]);
        });
    }

    /**
     * 建立憑證
     *
     * @param  string         $project        專案 ID
     * @param  SslCertificate $sslCertificate 憑證資訊
     * @param  callable       $success        成功調用方法
     * @return void
     */
    private static function _insertSslCertificate(string $project, SslCertificate $sslCertificate, callable $success)
    {
        $sslCertificatesClient = new SslCertificatesClient();

        try {
            $operationResponse = $sslCertificatesClient->insert($project, $sslCertificate);

            $operationResponse->pollUntilComplete();

            if ($operationResponse->operationSucceeded()) {
                echo '🗹 憑證新增 - 成功' . PHP_EOL;

                // 成功調用方法
                $success($sslCertificatesClient->get($project, $sslCertificate->getName()));
            } else {
                $error = $operationResponse->getError();
                echo "🗵 憑證新增 - 失敗: {$error}" . PHP_EOL;
            }
        } finally {
            $sslCertificatesClient->close();
        }
    }

    /**
     * 修改目標 Proxy 使用憑證
     *
     * @param  string $project          專案 ID
     * @param  string $targetHttpsProxy 目標 Proxy
     * @param  array  $sslCertsSelfLink 憑證資源網址
     * @return void
     */
    private static function _patchTargetHttpsProxy(string $project, string $targetHttpsProxy, array $sslCertsSelfLink)
    {
        $targetHttpsProxiesClient = new TargetHttpsProxiesClient();

        // 取得 目標 Proxy 基本資訊
        $targetHttpsProxies = $targetHttpsProxiesClient->get($project, $targetHttpsProxy);

        // 建立 目標 Proxy 修改請求內容
        $targetHttpsProxyResource = new TargetHttpsProxy([
            'fingerprint' => $targetHttpsProxies->getFingerprint(),
            'ssl_certificates' => $sslCertsSelfLink,
        ]);

        try {
            $operationResponse = $targetHttpsProxiesClient->patch($project, $targetHttpsProxy, $targetHttpsProxyResource);

            $operationResponse->pollUntilComplete();

            if ($operationResponse->operationSucceeded()) {
                echo '🗹 憑證更換 - 成功' . PHP_EOL;
            } else {
                $error = $operationResponse->getError();
                echo "🗵 憑證更換 - 失敗: {$error}" . PHP_EOL;
            }
        } finally {
            $targetHttpsProxiesClient->close();
        }
    }
}
