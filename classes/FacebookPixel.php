<?php
class FacebookPixel {
    private $pdo;
    private $pixelId;
    private $accessToken;
    private $enabled = false;
    private $testEventCode = null; // se quiser ativar testes

    public function __construct($pdo, $testEventCode = null) {
        $this->pdo = $pdo;
        $this->testEventCode = $testEventCode; // passar se quiser modo teste
        $this->loadCredentials();
    }

    private function loadCredentials() {
        $stmt = $this->pdo->query("SELECT pixel_id, access_token FROM fb_pixels WHERE ativo=1 LIMIT 1");
        $creds = $stmt->fetch();

        if ($creds && !empty($creds['pixel_id']) && !empty($creds['access_token'])) {
            $this->pixelId     = $creds['pixel_id'];
            $this->accessToken = $creds['access_token'];
            $this->enabled     = true;
        }
    }

    public function isEnabled() {
        return $this->enabled;
    }

    public function sendEvent($eventName, $userData, $eventId,$fbp,$fbc,$ip_address,$user_agent) {
        if (!$this->enabled) {
            return "Facebook Pixel não configurado — evento ignorado.";
        }

        $url = "https://graph.facebook.com/v18.0/{$this->pixelId}/events";

        // Captura fbp e fbc dos cookies se existirem
        

        $payload = [
            "data" => [[
                "event_name"     => $eventName,
                "event_time"     => time(),
                "event_id"       => $eventId,
                "action_source"  => "website",
                "user_data"      => array_filter([
                    "em" => [hash('sha256', strtolower(trim($userData['email'] ?? '')))],
                    "ph" => [hash('sha256', preg_replace('/\D/', '', $userData['phone'] ?? ''))],
                    "client_ip_address" => $ip_address,
                    "client_user_agent" => $user_agent,
                    "fbp" => $fbp,
                    "fbc" => $fbc
                ]),
                "custom_data"    => [
                    "currency" => "BRL",
                    "value"    => $userData['amount'] ?? 0
                ]
            ]],
            "access_token" => $this->accessToken
        ];

        // Se estiver em modo de teste, adiciona test_event_code
        if ($this->testEventCode) {
            $payload["test_event_code"] = $this->testEventCode;
        }

        // Log para debug
        $this->logFacebook("Enviando evento", $payload);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ["Content-Type: application/json"]
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->logFacebook("Resposta API", [
            "http_code" => $httpCode,
            "response"  => $response,
            "error"     => $error
        ]);

        return $error ?: $response;
    }

    private function logFacebook($message, $context = []) {
        $logFile = __DIR__ . "/fb_pixel.log";
        $date    = date("Y-m-d H:i:s");
        $line    = "[$date] $message " . (!empty($context) ? json_encode($context) : "") . PHP_EOL;
        file_put_contents($logFile, $line, FILE_APPEND);
    }
}
