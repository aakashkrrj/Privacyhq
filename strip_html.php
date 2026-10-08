<?php
$files = [
    'pages/frameworks.php',
    'pages/edit-profile.php',
    'pages/data-inventory.php',
    'pages/compliance-dashboard.php',
    'pages/change-password.php'
];

foreach ($files as $f) {
    if (!file_exists($f)) continue;
    $content = file_get_contents($f);
    
    // Strip everything from <!DOCTYPE html> or <html... to <body...>
    $content = preg_replace('/<!DOCTYPE html>.*?<body[^>]*>/is', '', $content);
    
    // Strip </body> and </html>
    $content = str_replace(['</body>', '</html>'], '', $content);
    
    // Optional: strip duplicate <header> that has "PrivacyHQ" or similar
    // Actually, it's safer to just let it be, but they have a top app bar.
    // Let's strip <header ...> ... </header> if it's the TopAppBar
    if (strpos($content, '<!-- TopAppBar -->') !== false) {
        $content = preg_replace('/<!-- TopAppBar -->.*?<\/header>/is', '', $content);
    }
    
    // Also remove bottom-nav if it was included in them manually
    $content = preg_replace('/<!-- Bottom Nav -->.*?<\/nav>/is', '', $content);
    $content = preg_replace('/<nav class="fixed bottom-0.*?<\/nav>/is', '', $content);
    
    file_put_contents($f, trim($content));
    echo "Stripped HTML wrapper from $f\n";
}
