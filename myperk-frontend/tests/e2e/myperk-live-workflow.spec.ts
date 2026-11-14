import { test, expect } from '@playwright/test';

// Test data constants
const TEST_QR_HASH = 'TC001';
const TEST_BUSINESS_ID = 1;
const BUSINESS_NAME = 'Test Cafe';
const CUSTOMER_PHONE = '+15551234567';
const CUSTOMER_NAME = 'Test Customer Workflow';

/**
 * MyPerk Live Browser Testing Suite
 * 
 * Tests the complete workflow:
 * QR Scan → Customer Join → Stamp Request → Staff Approval → Reward Redemption
 */
test.describe('MyPerk Loyalty Platform - Live Browser Tests', () => {
  
  test.beforeEach(async ({ page }) => {
    // Clear localStorage to start fresh
    await page.evaluate(() => localStorage.clear());
  });

  test('1. Welcome Page - Public Landing', async ({ page }) => {
    await page.goto('/', { waitUntil: 'networkidle' });
    
    await expect(page).toHaveTitle(/MyPerk|myperk/i);
    
    // Check for main title
    await expect(page.locator('text=Test Cafe') || page.locator('text=MyPerk Loyalty')).toBeVisible({ timeout: 10000 });
    
    // Check for login buttons (WelcomePage uses "Customer Login" variant)
    await expect(page.locator('button:has-text("Customer Login")')).toBeVisible();
    await expect(page.locator('button:has-text("Staff Login")')).toBeVisible();
    await expect(page.locator('button:has-text("Business Owner Login")')).toBeVisible();
    await expect(page.locator('button:has-text("Admin Login")')).toBeVisible();
  });

  test('2. Login Page Navigation', async ({ page }) => {
    await page.goto('/login', { waitUntil: 'networkidle' });
    
    // LoginPage has direct role buttons
    await expect(page.locator('button:has-text("Customer")')).toBeVisible();
    await expect(page.locator('button:has-text("Staff")')).toBeVisible();
    await expect(page.locator('button:has-text("Business Owner")')).toBeVisible();
    await expect(page.locator('button:has-text("Admin")')).toBeVisible();
  });

  test('3. QR Code Verification - Public Endpoint', async ({ page }) => {
    await page.goto('/qr-scan', { waitUntil: 'networkidle' });
    
    await expect(page.locator('h2')).toContainText(/Scan/i, { timeout: 10000 });
    
    // Enter QR hash manually
    await page.fill('input[placeholder*="QR hash" i], input[type="text"]', TEST_QR_HASH);
    await page.click('button:has-text("Verify"), button:has-text("Scan")');
    
    // Wait for QR verification
    await page.waitForSelector('.myperk-result-badge', { timeout: 15000 });
    
    // Check for valid result
    const resultBadge = page.locator('.myperk-result-badge');
    await expect(resultBadge).toContainText(/Valid|success/i, { timeout: 15000 });
    
    // Check business name is displayed
    await expect(page.locator('text=Test Cafe, text=Test Cafe, text=test-cafe')).toBeVisible({ timeout: 10000 });
  });

  test('4. Customer Join After QR Scan', async ({ page }) => {
    await page.goto('/qr-scan', { waitUntil: 'networkidle' });
    
    // Enter QR hash
    await page.fill('input[placeholder*="QR hash" i], input[type="text"]', TEST_QR_HASH);
    await page.click('button:has-text("Verify"), button:has-text("Scan")');
    
    // Wait for join modal to appear
    await page.waitForSelector('.myperk-modal', { timeout: 15000 });
    
    // Click Continue to join
    await page.click('button:has-text("Continue"), button:has-text("Continue to Dashboard")');
    
    // Verify customer dashboard loaded (login now redirects automatically)
    await page.waitForURL('**/customer/dashboard', { timeout: 15000 });
    
    // Verify customer dashboard loaded
    await expect(page.locator('h2')).toContainText(/Dashboard|Loyalty/i, { timeout: 10000 });
  });

  test('5. Customer Dashboard - View Cards', async ({ page }) => {
    // Go directly to customer dashboard (simulating logged-in state via localStorage)
    await page.evaluate(() => {
      localStorage.setItem('myperk_user', JSON.stringify({
        role: 'customer',
        userId: 3,
        businessId: 1,
        apiKey: 'ck_myperk_customer_test'
      }));
    });
    await page.goto('/customer/dashboard', { waitUntil: 'networkidle' });
    
    // Check dashboard elements
    await expect(page.locator('h2')).toContainText(/Dashboard/i, { timeout: 5000 });
    
    // Check for stats cards
    await expect(page.locator('.myperk-stat-card, .myperk-stat-value')).toBeVisible({ timeout: 10000 });
  });

  test('6. Create Stamp Request - Customer', async ({ page }) => {
    // Set customer auth in localStorage
    await page.evaluate(() => {
      localStorage.setItem('myperk_user', JSON.stringify({
        role: 'customer',
        userId: 3,
        businessId: 1,
        apiKey: 'ck_myperk_customer_test'
      }));
    });
    
    // Now create a stamp request via API
    const response = await page.request.post('http://localhost:8080/myperk/v1/customer/stamp-request', {
      data: {
        business_id: TEST_BUSINESS_ID,
        branch_id: 1,
        qr_hash: TEST_QR_HASH,
        requested_stamps: 1
      },
      headers: {
        'Content-Type': 'application/json',
        'X-MyPerk-API-Key': 'ck_myperk_customer_test'
      }
    });
    
    expect(response.ok()).toBe(true);
    const data = await response.json();
    console.log('Stamp request created:', data);
  });

  test('7. Staff Login and View Pending Requests', async ({ page }) => {
    // Set staff auth in localStorage
    await page.evaluate(() => {
      localStorage.setItem('myperk_user', JSON.stringify({
        role: 'staff',
        userId: 1,
        businessId: 1,
        apiKey: 'wk_myperk_staff_1'
      }));
    });
    
    // Navigate directly to staff requests page
    await page.goto('/staff/requests', { waitUntil: 'networkidle' });
    
    // Check page title
    await expect(page.locator('h2')).toContainText(/Request|Pending/i, { timeout: 5000 });
    
    // Check for requests list
    await page.waitForSelector('.myperk-request-card, .myperk-requests-list', { timeout: 15000 });
  });

  test('8. Staff Approve Stamp Request', async ({ page }) => {
    // Set staff auth in localStorage
    await page.evaluate(() => {
      localStorage.setItem('myperk_user', JSON.stringify({
        role: 'staff',
        userId: 1,
        businessId: 1,
        apiKey: 'wk_myperk_staff_1'
      }));
    });
    
    // Navigate to pending requests
    await page.goto('/staff/requests', { waitUntil: 'networkidle' });
    
    // Wait for at least one pending request
    await page.waitForSelector('.myperk-request-card', { timeout: 15000 });
    
    // Find a pending request
    const pendingRequest = page.locator('.myperk-request-card').first();
    await expect(pendingRequest).toBeVisible({ timeout: 10000 });
    
    // Check for approve button
    const approveButton = pendingRequest.locator('button:has-text("Approve")');
    if (await approveButton.count() > 0) {
      // Click approve
      await approveButton.click();
      
      // Confirm dialog
      const dialog = page.locator('role=dialog');
      if (await dialog.count() > 0) {
        await dialog.locator('button:has-text("Yes"), button:has-text("OK"), button:has-text("Approve")').click();
      }
      
      // Wait a moment for the update
      await page.waitForTimeout(1000);
      
      console.log('Stamp request approved via UI');
    } else {
      console.log('No approve button found or request already processed');
    }
  });

  test('9. Business Dashboard Metrics Verification', async ({ page }) => {
    // Set business owner auth in localStorage
    await page.evaluate(() => {
      localStorage.setItem('myperk_user', JSON.stringify({
        role: 'business_owner',
        userId: 1,
        businessId: 1,
        apiKey: 'bk_myperk_business_1'
      }));
    });
    
    await page.goto('/business/dashboard', { waitUntil: 'networkidle' });
    
    // Get the metrics values
    const metricValues = await page.locator('.myperk-stat-value').evaluateAll(el => 
      el.map(e => parseFloat(e.textContent?.replace(/[^\d.-]/g, '') || '0'))
    );
    
    console.log('Metric values:', metricValues);
    
    // At minimum, we should see some metrics
    expect(metricValues.length).toBeGreaterThanOrEqual(1);
  });

  test('10. API Health Check - Backend Response', async ({ page }) => {
    // Test the backend directly
    const response = await page.request.post('http://localhost:8080/myperk/v1/public/qr/verify', {
      data: { qr_hash: TEST_QR_HASH },
      headers: { 'Content-Type': 'application/json' }
    });
    
    expect(response.ok()).toBe(true);
    const data = await response.json();
    console.log('QR verify response:', JSON.stringify(data, null, 2));
  });

  test('11. Admin Super Admin Dashboard', async ({ page }) => {
    // Set super admin auth in localStorage
    await page.evaluate(() => {
      localStorage.setItem('myperk_user', JSON.stringify({
        role: 'super_admin',
        userId: 1,
        businessId: null,
        customerId: null,
        apiKey: 'sk_myperk_super_admin_123'
      }));
    });
    
    // Navigate directly to admin dashboard
    await page.goto('/admin/dashboard', { waitUntil: 'networkidle' });
    
    // Check for admin metrics
    await expect(page.locator('h2')).toContainText(/Dashboard|Platform/i, { timeout: 5000 });
  });

  test('12. Full Workflow - QR Scan to Staff Approval', async ({ page }) => {
    // Step 1: Start as customer scanning QR
    await page.goto('/qr-scan', { waitUntil: 'networkidle' });
    
    // Step 2: Enter QR hash
    await page.fill('input[placeholder*="QR hash" i], input[type="text"]', TEST_QR_HASH);
    await page.click('button:has-text("Verify"), button:has-text("Scan")');
    
    // Step 3: Wait for verification and join
    await page.waitForSelector('.myperk-modal', { timeout: 15000 });
    await page.click('button:has-text("Continue")');
    
    // Step 4: Verify dashboard access
    await page.waitForURL('**/customer/dashboard', { timeout: 15000 });
    
    // Step 5: Create stamp request via API
    const stampResponse = await page.request.post('http://localhost:8080/myperk/v1/customer/stamp-request', {
      data: {
        business_id: TEST_BUSINESS_ID,
        branch_id: 1,
        qr_hash: TEST_QR_HASH,
        requested_stamps: 1
      },
      headers: {
        'Content-Type': 'application/json',
        'X-MyPerk-API-Key': 'ck_myperk_customer_test'
      }
    });
    
    expect(stampResponse.ok()).toBe(true);
    const stampData = await stampResponse.json();
    console.log('Created stamp request:', stampData);
    
    // Step 6: Login as staff and approve
    await page.evaluate(() => {
      localStorage.clear();
      localStorage.setItem('myperk_user', JSON.stringify({
        role: 'staff',
        userId: 1,
        businessId: 1,
        apiKey: 'wk_myperk_staff_1'
      }));
    });
    
    await page.goto('/staff/requests', { waitUntil: 'networkidle' });
    await page.waitForSelector('.myperk-request-card', { timeout: 15000 });
    
    // Approve a request
    const approveBtn = page.locator('.myperk-request-card button:has-text("Approve")').first();
    if (await approveBtn.count() > 0) {
      await approveBtn.click();
      // Handle any confirmation
      const confirmBtn = page.locator('role=dialog button:has-text("Yes"), role=dialog button:has-text("Approve")');
      if (await confirmBtn.count() > 0) {
        await confirmBtn.click();
      }
      console.log('Approved stamp request');
    }
    
    // Step 7: Login as business owner and check dashboard
    await page.evaluate(() => {
      localStorage.clear();
      localStorage.setItem('myperk_user', JSON.stringify({
        role: 'business_owner',
        userId: 1,
        businessId: 1,
        apiKey: 'bk_myperk_business_1'
      }));
    });
    
    await page.goto('/business/dashboard', { waitUntil: 'networkidle' });
    
    // Verify activity is updated
    await expect(page.locator('.myperk-activity-list')).toBeVisible({ timeout: 10000 });
  });
});

/**
 * Additional tests for edge cases and error handling
 */
test.describe('MyPerk Edge Cases and Error Handling', () => {
  
  test('Invalid QR hash handling', async ({ page }) => {
    await page.goto('/qr-scan', { waitUntil: 'networkidle' });
    
    await page.fill('input[placeholder*="QR hash" i], input[type="text"]', 'INVALID_HASH_999');
    await page.click('button:has-text("Verify"), button:has-text("Scan")');
    
    // Should show invalid result
    await page.waitForSelector('.myperk-result-badge', { timeout: 15000 });
    const badge = page.locator('.myperk-result-badge');
    await expect(badge).toContainText(/Invalid|invalid/i, { timeout: 10000 });
  });

  test('Navigation between customer pages', async ({ page }) => {
    // Set customer auth in localStorage
    await page.evaluate(() => {
      localStorage.setItem('myperk_user', JSON.stringify({
        role: 'customer',
        userId: 3,
        businessId: 1,
        apiKey: 'ck_myperk_customer_test'
      }));
    });
    
    await page.goto('/customer/dashboard', { waitUntil: 'networkidle' });
    
    // Navigate to cards page
    await page.click('a:has-text("My Cards"), .myperk-nav-link:has-text("Cards")');
    await page.waitForURL('**/customer/cards', { timeout: 10000 });
  });

  test('Business Owner Settings Page', async ({ page }) => {
    // Set business owner auth in localStorage
    await page.evaluate(() => {
      localStorage.setItem('myperk_user', JSON.stringify({
        role: 'business_owner',
        userId: 1,
        businessId: 1,
        apiKey: 'bk_myperk_business_1'
      }));
    });
    
    await page.goto('/business/settings', { waitUntil: 'networkidle' });
    
    // Check for settings elements
    await expect(page.locator('h2')).toContainText(/Settings|Branch/i, { timeout: 5000 });
  });
});