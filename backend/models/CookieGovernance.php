<?php
namespace Backend\Models;

class CookieGovernance {
    private $pdo;

    public function __construct(\PDO $pdo = null) {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            // Load globally configured DB connection
            global $pdo;
            $this->pdo = $pdo;
        }
    }

    public function getPlaceholderDataset() {
        // Fallback placeholder mock dataset for backward compatibility / testing
        return [
            'metrics' => [
                'total_cookies' => 148,
                'uncategorized' => 8,
                'opt_in_rate' => '82.4%',
                'configured_banners' => '3 Domains'
            ],
            'categories' => [
                'Necessary' => 42,
                'Analytics' => 28,
                'Preferences' => 18,
                'Advertising' => 12
            ],
            'recent_scan' => [
                'domain' => 'privacyhq.com',
                'status' => 'Completed',
                'cookies_found' => 148,
                'last_scan' => 'Today 11:45 AM'
            ],
            'inventory' => [
                [
                    'name' => '_ga',
                    'domain' => 'example.com',
                    'category' => 'Analytics',
                    'type' => 'First-Party',
                    'duration' => '2 Years'
                ],
                [
                    'name' => '_fbp',
                    'domain' => 'example.com',
                    'category' => 'Advertising',
                    'type' => 'Third-Party',
                    'duration' => '90 Days'
                ]
            ]
        ];
    }
}
