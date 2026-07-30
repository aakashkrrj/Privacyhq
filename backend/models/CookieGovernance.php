<?php
namespace Backend\Models;

/*
 Placeholder implementation.

 The current application contains no database schema
 for Cookie Governance.

 Replace this model with database queries when
 cookie management is implemented.
*/
class CookieGovernance {
    public function getPlaceholderDataset() {
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
