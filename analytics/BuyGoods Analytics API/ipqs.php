<?php
/**
 * IPQualityScore API Integration
 * Analyzes customer IP addresses for fraud detection
 */

class IPQS {
    private $apiKey;
    private $baseUrl = 'https://www.ipqualityscore.com/api/json/ip/';

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    /**
     * Analyze an IP address for fraud indicators
     *
     * @param string $ipAddress The IP address to analyze
     * @param array $options Optional parameters (user_agent, strictness)
     * @return array|null Analysis results or null on failure
     */
    public function analyzeIP($ipAddress, $options = []) {
        // Skip private/local IPs
        if ($this->isPrivateIP($ipAddress)) {
            return [
                'success' => true,
                'country_code' => 'LOCAL',
                'city' => 'Local Network',
                'region' => 'N/A',
                'proxy' => false,
                'tor' => false,
                'fraud_score' => 0,
                'is_local' => true
            ];
        }

        // Build API URL
        $url = $this->baseUrl . $this->apiKey . '/' . urlencode($ipAddress);

        // Add optional parameters
        $params = [];
        if (!empty($options['user_agent'])) {
            $params['user_agent'] = $options['user_agent'];
        }
        if (!empty($options['strictness'])) {
            $params['strictness'] = $options['strictness'];
        } else {
            $params['strictness'] = 1; // Default medium strictness
        }

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        // Make API request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json']
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            error_log("IPQS API Error: $error (HTTP $httpCode)");
            return null;
        }

        $data = json_decode($response, true);

        if (!$data || !isset($data['success'])) {
            error_log("IPQS API: Invalid response");
            return null;
        }

        return $data;
    }

    /**
     * Extract the key fraud indicators we need
     *
     * @param array $data Full API response
     * @return array Simplified fraud data
     */
    public function extractFraudData($data) {
        if (!$data) {
            return null;
        }

        return [
            'country' => $data['country_code'] ?? null,
            'city' => $data['city'] ?? null,
            'region' => $data['region'] ?? null,
            'proxy' => $data['proxy'] ?? false,
            'tor' => $data['tor'] ?? false,
            'fraud_score' => $data['fraud_score'] ?? 0,
            'vpn' => $data['vpn'] ?? false,
            'bot_status' => $data['bot_status'] ?? false,
            'recent_abuse' => $data['recent_abuse'] ?? false,
            'isp' => $data['ISP'] ?? null
        ];
    }

    /**
     * Get fraud risk level based on score
     *
     * @param int $score Fraud score (0-100)
     * @return string Risk level
     */
    public static function getRiskLevel($score) {
        if ($score >= 90) return 'high';
        if ($score >= 85) return 'risky';
        if ($score >= 75) return 'suspicious';
        return 'low';
    }

    /**
     * Check if IP is private/local
     */
    private function isPrivateIP($ip) {
        // Check for IPv6 localhost
        if ($ip === '::1') return true;

        // Check for private IPv4 ranges
        $privateRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8'
        ];

        $ipLong = ip2long($ip);
        if ($ipLong === false) return false; // Invalid IP or IPv6

        foreach ($privateRanges as $range) {
            list($subnet, $mask) = explode('/', $range);
            $subnetLong = ip2long($subnet);
            $maskLong = ~((1 << (32 - $mask)) - 1);

            if (($ipLong & $maskLong) === ($subnetLong & $maskLong)) {
                return true;
            }
        }

        return false;
    }
}
