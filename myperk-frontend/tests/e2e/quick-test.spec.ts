import { test, expect } from '@playwright/test';

test.describe('Quick MyPerk Tests', () => {
  test('Basic page loads', async ({ page }) => {
    await page.goto('/', { waitUntil: 'networkidle' });
    await expect(page.locator('body')).toBeVisible();
    console.log('Page title:', await page.title());
  });

  test('QR Scan page works', async ({ page }) => {
    await page.goto('/qr-scan', { waitUntil: 'networkidle' });
    await page.fill('input[type="text"]', 'TC001');
    await page.click('button:has-text("Verify"), button:has-text("Scan")');
    await page.waitForTimeout(2000);
    const content = await page.content();
    console.log('QR page content preview:', content.substring(0, 1000));
  });

  test('Login as Customer', async ({ page }) => {
    await page.goto('/login', { waitUntil: 'networkidle' });
    await page.click('button:has-text("Customer")');
    await page.waitForURL('**/customer/dashboard', { timeout: 10000 });
    const title = await page.locator('h1, h2').textContent();
    console.log('Customer dashboard title:', title);
  });

  test('API Health Check', async ({ page }) => {
    const response = await page.request.post('http://localhost:8080/myperk/v1/public/qr/verify', {
      data: { qr_hash: 'TC001' },
      headers: { 'Content-Type': 'application/json' }
    });
    console.log('Backend response status:', response.status());
    const data = await response.json();
    console.log('Backend response data:', JSON.stringify(data, null, 2));
  });
});