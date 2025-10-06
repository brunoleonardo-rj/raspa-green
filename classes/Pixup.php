<?php
class Pixup
{
    private $pdo;
    private $url;
    private $clientId;
    private $clientSecret;
    private $accessToken;
    private $debug;

    public function __construct($pdo, $debug = true)
    {
        $this->pdo = $pdo;
        $this->debug = $debug;
        $this->loadCredentials();
        $this->authenticate();
    }

    private function writeLog($msg)
    {
        $file = __DIR__ . '/../logs_pixup.txt';
        file_put_contents($file, date('d/m/Y H:i:s') . " - " . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private $backendUrl;

    private function loadCredentials()
    {
        $stmt = $this->pdo->query("
        SELECT pixup_base_url, pixup_client_id, pixup_client_secret, pixup_backend_url
        FROM gateway
        WHERE active='pixup'
        LIMIT 1
    ");
        $credentials = $stmt->fetch();

        if (!$credentials) {
            throw new Exception("Credenciais PixUp não encontradas no banco.");
        }

        $this->url = rtrim($credentials['pixup_base_url'], '/');
        $this->clientId = $credentials['pixup_client_id'];
        $this->clientSecret = $credentials['pixup_client_secret'];
        $this->backendUrl = rtrim($credentials['pixup_backend_url'], '/');

        $this->writeLog("Credenciais carregadas: URL={$this->url}, BACKEND_URL={$this->backendUrl}");
    }


    private function authenticate()
    {
        $credentials = $this->clientId . ':' . $this->clientSecret;
        $base64 = base64_encode($credentials);
        $header = "Authorization: Basic " . $base64;

        $ch = curl_init("{$this->url}/oauth/token");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                "grant_type" => "client_credentials"
            ]),
            CURLOPT_HTTPHEADER => [
                $header,
                "Content-Type: application/x-www-form-urlencoded"
            ],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $this->writeLog("Auth Response HTTP {$httpCode}: {$response}");

        if ($error) {
            throw new Exception("Erro ao autenticar PixUp: {$error}");
        }

        $data = json_decode($response, true);

        if ($httpCode >= 400 || !isset($data['access_token'])) {
            throw new Exception("Falha na autenticação PixUp: " . $response);
        }

        $this->accessToken = $data['access_token'];
        $this->writeLog("Token obtido com sucesso.");
    }


    public function createPixPayment($params)
    {
        $postbackUrl = $this->backendUrl . "/callback/pixup.php";

        $payload = [
            "amount" => $params["amount"],
            "payerQuestion" => $params["metadata"]["description"] ?? "Depósito via Pix",
            "external_id" => $params["metadata"]["depositId"] ?? uniqid("dep_"),
            "postbackUrl" => $postbackUrl,
            "payer" => [
                "name" => $params["customerData"]["name"] ?? "Cliente",
                "document" => $params["customerData"]["document"] ?? null,
                "email" => $params["customerData"]["email"] ?? null
            ]
        ];

        // 👇 aqui a gente loga o postbackUrl e o payload
        $this->writeLog("Postback URL final: {$postbackUrl}");
        $this->writeLog("Payload Enviado: " . json_encode($payload, JSON_UNESCAPED_UNICODE));

        $ch = curl_init("{$this->url}/pix/qrcode");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->accessToken}",
                "Content-Type: application/json"
            ],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $this->writeLog("Create QRCode HTTP {$httpCode}: {$response}");

        if ($error) {
            return ["success" => false, "error" => "Erro cURL: {$error}"];
        }

        $data = json_decode($response, true);

        if ($httpCode >= 400 || (empty($data['emv']) && empty($data['qrcode']))) {
            return ["success" => false, "error" => $data['message'] ?? "Erro ao gerar QRCode PixUp"];
        }

        return [
            "success" => true,
            "transactionId" => $data['id'] ?? $data['transactionId'] ?? null,
            "qrCode" => $data['emv'] ?? $data['qrcode'] ?? null,
            "qrCodeBase64" => $data['base64Image'] ?? null,
            "status" => $data['status'] ?? 'PENDING'
        ];

    }

    public function processWebhook($data)
    {
        $this->writeLog("Webhook recebido: " . json_encode($data, JSON_UNESCAPED_UNICODE));

        // Se o payload vier aninhado em requestBody, usa ele
        if (isset($data['requestBody']) && is_array($data['requestBody'])) {
            $payload = $data['requestBody'];
        } else {
            $payload = $data;
        }

        return [
            "transactionId" => $payload["transactionId"] ?? null,
            "externalId" => $payload["external_id"] ?? null,
            "status" => $payload["status"] ?? null,
            "amount" => $payload["amount"] ?? null,
            "paidAt" => $payload["dateApproval"] ?? null,
            "payerEmail" => $payload["creditParty"]["email"] ?? null,
            "payerName" => $payload["creditParty"]["name"] ?? null,
            "payerDocument" => $payload["creditParty"]["taxId"] ?? null,
        ];
    }


    public function getBackendUrl()
    {
        return $this->backendUrl;
    }
}
