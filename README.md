# gclb-auto-ssl

半自動更換 GCLB HTTPS SSL 憑證

## 事前準備

### GCP 權限

- compute.sslCertificates.*
- compute.targetHttpsProxies.*
- compute.targetSslProxies.*

### Let's Encrypt

- [Official website](https://letsencrypt.org/)
- 設定完成自動簽發憑證

```bash
# 安裝 certbot
sudo snap install --classic certbot
sudo ln -s /snap/bin/certbot /usr/bin/certbot
sudo snap set certbot trust-plugin-with-root=ok

# 簽發憑證
certbot certonly \
  --email "YOUR_EMAIL_ADDRESS" \
  --agree-tos \
  -d *.domain.com \
  -d domain.com
```

## 修改環境變數

- 複製專案中 .env.example 將檔案命名為 .env
- 修改內容參數
- 執行程式確認是否正常運行

```bash
# 執行更換程式
php run.php

# 無權限讀取 /etc/letsencrypt/live 時，可嘗試使用以下指令
sudo php run.php
```

## 註冊排程

```cron
# 每月1日 00:00 更新憑證
0 0 1 * *   root    /snap/bin/certbot renew --force-renew --quiet --renew-hook "/usr/sbin/nginx -s reload"
# 每月1日 01:00 更換憑證
0 1 1 * *   root    /usr/bin/php /path/to/repo_dir/run.php
```
