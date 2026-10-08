const { chromium } = require('playwright');
const fs = require('fs');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    let report = "PHASE 13 UI ACCEPTANCE REPORT\n==============================\n\n";
    let jsErrors = [];
    
    page.on('pageerror', err => {
        jsErrors.push(err.message);
    });

    const baseUrl = 'http://127.0.0.1/governance';

    // 1. Login
    await page.goto(`${baseUrl}/login.php`);
    await page.fill('input[name="email"]', 'admin@privacyhq.com');
    await page.fill('input[name="password"]', 'Admin123!');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(1000);
    
    if (page.url().includes('dashboard')) {
        report += "[PASS] Login\n";
    } else {
        report += "[FAIL] Login - URL is " + page.url() + "\n";
    }

    const routes = [
        { name: 'Dashboard', url: '?page=dashboard' },
        { name: 'Consent', url: '?page=consent' },
        { name: 'DSR', url: '?page=data-requests' },
        { name: 'Cookie Governance', url: '?page=cookie-governance' },
        { name: 'Assessments (PIA)', url: '?page=assessments' },
        { name: 'Vendor Risk', url: '?page=vendor-risk' },
        { name: 'Incidents', url: '?page=incident-management' },
        { name: 'Risk Register (GRC)', url: '?page=risk-register' },
        { name: 'Compliance Dashboard', url: '?page=compliance-dashboard' },
        { name: 'Frameworks', url: '?page=frameworks' },
        { name: 'Data Discovery (DSPM)', url: '?page=data-discovery' },
        { name: 'Policies', url: '?page=policies' },
        { name: 'Audit Logs', url: '?page=audit-logs' },
        { name: 'Settings', url: '?page=settings' },
        { name: 'More Menu', url: '?page=more' }
    ];

    for (const route of routes) {
        jsErrors = [];
        const fullUrl = `${baseUrl}/index.php${route.url}`;
        await page.goto(fullUrl);
        await page.waitForTimeout(1000);
        
        const content = await page.content();
        
        let status = 'PASS';
        let errors = [];
        
        if (jsErrors.length > 0) {
            status = 'PARTIAL';
            errors.push('JS Error: ' + jsErrors[0]);
        }
        
        if (content.includes('Warning: ') || content.includes('Fatal error: ')) {
            status = 'FAIL';
            errors.push('PHP Error visible');
        }
        
        if (content.includes('Page Not Found')) {
            status = 'FAIL';
            errors.push('Page not found inside wrapper');
        }
        
        if (page.url() !== fullUrl) {
             // Redirect happened (maybe auth error)
             status = 'FAIL';
             errors.push(`Redirected to ${page.url()}`);
        }
        
        report += `[${status}] ${route.name} - ${errors.join(', ')}\n`;
    }

    fs.writeFileSync('test_phase13_ui.md', report);
    console.log('UI tests complete.');
    await browser.close();
})();
