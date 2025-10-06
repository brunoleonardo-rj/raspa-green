<?php

class GatewayProprio {
    private $pdo;
    private $apiKey;
    private $baseUrl;
    private $debug;

    public function __construct($pdo, $debug = true) {
        $this->pdo   = $pdo;
        $this->debug = $debug;
        $this->loadCredentials();
    }

    private function loadCredentials() {
        $stmt = $this->pdo->query("SELECT url, api_key FROM gatewayproprio LIMIT 1");
        $credentials = $stmt->fetch();

        if (!$credentials) {
            throw new Exception('Credenciais do Gateway Próprio não encontradas.');
        }

        $this->baseUrl = rtrim($credentials['url'], '/');
        $this->apiKey  = $credentials['api_key'];
    }

    private function logDebug($title, $data) {
        if ($this->debug) {
            $message = "=== DEBUG: {$title} ===\n";
            if (is_array($data) || is_object($data)) {
                $message .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
            } else {
                $message .= $data . "\n\n";
            }
            error_log($message);
        }
    }

    private function makeRequest($endpoint, $payload) {
        $url = $this->baseUrl . $endpoint;
        
        $this->logDebug("Request URL", $url);
        $this->logDebug("Request Payload", $payload);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $this->logDebug("Response HTTP Code", $httpCode);
        $this->logDebug("Raw Response", $response);
        
        if ($curlError) {
            throw new Exception('Erro na requisição cURL: ' . $curlError);
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $responseData['error'] ?? $responseData['message'] ?? 'Erro desconhecido';
            throw new Exception("Erro HTTP $httpCode: $errorMsg");
        }
        
        return $responseData;
    }

    /**
     * Criar depósito PIX (cassiopay)
     */
    public function createDeposit($amount, $cpf, $nome, $email, $callbackUrl, $idempotencyKey, $split = null) {

        $payload = [
            "amount"=> (int)$amount,
            "client"=> [
                "name"=> $nome,
                "document"=> "$cpf",
                "telefone"=> "13999999999",
                "email"=> $email
            ],
            "callbackUrl"=> $callbackUrl, 
            "api-key"=> $this->apiKey
        ];
        
        $responseData = $this->makeRequest('/v1/gateway/', $payload);
        
        return [
            'status'           => 'PENDING',
            'paymentCode'      => $responseData['paymentCode'],
            'idTransaction'    => $responseData['idTransaction'],
            'paymentCodeBase64'=> $responseData['paymentCodeBase64'] ?? null
        ];
    }

    /**
     * Consultar status de uma transação (cassiopay)
     */
    public function checkTransactionStatus($transactionId) {
        $payload = [
            "idTransaction" => $transactionId,
            "api-key"       => $this->apiKey
        ];
        
        return $this->makeRequest('/v1/webhook/', $payload);
    }
    
    /**
     * Realizar pagamento para usuário (cassiopay Cashout)
     */
    public function makePayment($name, $cpf, $pixKey, $amount) {
        $payload = [
            "name"    => $name,
            "cpf"     => preg_replace('/\D/', '', $cpf),
            "keypix"  => $pixKey,
            "amount"  => (float)$amount,
            "api-key" => $this->apiKey
        ];
        
        $responseData = $this->makeRequest('/c1/cashout/', $payload);
        
        return [
            'success' => true,
            'status'  => $responseData['status'] ?? 'PROCESSING'
        ];
    }
}
?>