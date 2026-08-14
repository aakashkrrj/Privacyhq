<?php
namespace Backend\Services;

interface CookieScannerInterface {
    /**
     * Scan a domain and return discovered tracking technologies.
     * 
     * @param string $domain The domain or URL to scan.
     * @return array Array of discovered tracking technologies.
     */
    public function scan(string $domain): array;
}

class ProductionScanner implements CookieScannerInterface {
    public function scan(string $domain): array {
        $results = [];
        $url = $domain;
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) PrivacyHQ-Scanner/1.0');

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headersText = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        curl_close($ch);

        if (!$response) {
            throw new \Exception("Could not reach host: " . $domain);
        }

        // 1. Scan for Cookies in Set-Cookie headers
        preg_match_all('/^Set-Cookie:\s*([^;=]+)=([^;]*)/mi', $headersText, $matches, PREG_SET_ORDER);
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? $domain;

        foreach ($matches as $match) {
            $name = trim($match[1]);
            // Determine if first-party or third-party based on domain (simplified)
            $party = 'First-Party';
            $category = 'Essential';
            if (preg_match('/(_ga|_gid|utm_)/i', $name)) {
                $category = 'Analytics';
            } elseif (preg_match('/(ads|remarketing|pixel|fb)/i', $name)) {
                $category = 'Advertising';
            }

            $results[] = [
                'name' => $name,
                'domain_source' => $host,
                'category' => $category,
                'party_type' => $party,
                'technology_type' => 'cookie',
                'description' => 'Session identification cookie.',
                'expiry' => 'Session'
            ];
        }

        // 2. Scan body HTML for script tags / pixels
        // Search for Google Analytics, Facebook Pixel, Google Tag Manager, etc.
        if (strpos($body, 'google-analytics.com') !== false || strpos($body, 'googletagmanager.com') !== false || strpos($body, 'gtag') !== false) {
            $results[] = [
                'name' => 'Google Tag Manager',
                'domain_source' => 'googletagmanager.com',
                'category' => 'Analytics',
                'party_type' => 'Third-Party',
                'technology_type' => 'tag',
                'description' => 'Google tags system tag loader.',
                'expiry' => 'Persistent'
            ];
            $results[] = [
                'name' => '_ga',
                'domain_source' => $host,
                'category' => 'Analytics',
                'party_type' => 'First-Party',
                'technology_type' => 'cookie',
                'description' => 'Google Analytics tracker.',
                'expiry' => '2 Years'
            ];
        }

        if (strpos($body, 'connect.facebook.net') !== false || strpos($body, 'fbq') !== false) {
            $results[] = [
                'name' => 'Facebook Pixel',
                'domain_source' => 'connect.facebook.net',
                'category' => 'Advertising',
                'party_type' => 'Third-Party',
                'technology_type' => 'pixel',
                'description' => 'Facebook conversion tracking pixel.',
                'expiry' => 'Persistent'
            ];
            $results[] = [
                'name' => '_fbp',
                'domain_source' => $host,
                'category' => 'Advertising',
                'party_type' => 'First-Party',
                'technology_type' => 'cookie',
                'description' => 'Facebook tracking cookie.',
                'expiry' => '90 Days'
            ];
        }

        // Default essential cookie if none discovered
        if (empty($results)) {
            $results[] = [
                'name' => 'PHPSESSID',
                'domain_source' => $host,
                'category' => 'Essential',
                'party_type' => 'First-Party',
                'technology_type' => 'cookie',
                'description' => 'PHP Session Identifier.',
                'expiry' => 'Session'
            ];
        }

        return $results;
    }
}

class DevelopmentScanner implements CookieScannerInterface {
    public function scan(string $domain): array {
        // Return deterministic test data
        return [
            [
                'name' => 'PHPSESSID',
                'domain_source' => $domain,
                'category' => 'Essential',
                'party_type' => 'First-Party',
                'technology_type' => 'cookie',
                'description' => 'Provides core session identity for user requests.',
                'expiry' => 'Session'
            ],
            [
                'name' => '_ga',
                'domain_source' => $domain,
                'category' => 'Analytics',
                'party_type' => 'First-Party',
                'technology_type' => 'cookie',
                'description' => 'Google Analytics usage tracking identifier.',
                'expiry' => '2 Years'
            ],
            [
                'name' => 'Facebook Pixel',
                'domain_source' => 'connect.facebook.net',
                'category' => 'Advertising',
                'party_type' => 'Third-Party',
                'technology_type' => 'pixel',
                'description' => 'Conversion tracking and retargeting pixel.',
                'expiry' => 'Session'
            ],
            [
                'name' => 'Google Tag Loader',
                'domain_source' => 'googletagmanager.com',
                'category' => 'Functional',
                'party_type' => 'Third-Party',
                'technology_type' => 'tag',
                'description' => 'Manages secondary website functional elements.',
                'expiry' => 'Persistent'
            ]
        ];
    }
}

class ScannerFactory {
    public static function getScanner(): CookieScannerInterface {
        // Switch scanner based on server host environment (localhost/127.0.0.1 uses dev mock)
        $isLocal = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1' || $_SERVER['HTTP_HOST'] === '127.0.0.1:8000' || $_SERVER['HTTP_HOST'] === 'localhost:8000');
        if ($isLocal) {
            return new DevelopmentScanner();
        }
        return new ProductionScanner();
    }
}
