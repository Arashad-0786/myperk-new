import { test, expect } from '@playwright/test';

// Test data constants
const TEST_QR_HASH = 'TC001';
const TEST_BUSINESS_ID = 1;
const BUSINESS_NAME = 'Test Cafe';
const CUSTOMER_PHONE = '+15551234567';

// API Base
const API_BASE = 'http://localhost:8080/myperk/v1';

/**
 * MyPerk API-Centric Browser Testing Suite
 * 
 * Tests the complete workflow using direct API calls where possible,
 * with UI interaction only when necessary.
 */
test.describe('MyPerk Loyalty Platform - API Workflow Tests', () => {
  
  test('0. API Health Check - Backend is Running', async ({ request }) => {
    // Test the QR verification endpoint directly
    const response = await request.post(`${API_BASE}/public/qr/verify`, {
      data: { qr_hash: TEST_QR_HASH },
      headers: { 'Content-Type': 'application/json' }
    });
    
    console.log('Status:', response.status());
    const body = await response.text();
    console.log('Response body:', body);
    
    expect(response.status()).toBe(200);
    
    const data = JSON.parse(body);
    console.log('API Response:', JSON.stringify(data, null, 2));
    
    // Validate expected response structure
    expect(data).toHaveProperty('valid');
    if (data.valid) {
      expect(data).toHaveProperty('business_id', TEST_BUSINESS_ID);
      expect(data).toHaveProperty('business_name', BUSINESS_NAME);
      expect(data).toHaveProperty('branch_id');
    }
  });

  test('1. QR Verification', async ({ request }) => {
    const response = await request.post(`${API_BASE}/public/qr/verify`, {
      data: { qr_hash: TEST_QR_HASH },
      headers: { 'Content-Type': 'application/json' }
    });
    
    expect(response.status()).toBe(200);
    const data = await response.json();
    
    console.log('QR Verify Result:', JSON.stringify(data, null, 2));
    
    expect(data.valid).toBe(true);
    expect(data.business_id).toBe(TEST_BUSINESS_ID);
    expect(data.business_name).toBe(BUSINESS_NAME);
    expect(data.branch_id).toBeDefined();
  });

  test('2. Business Info Endpoint', async ({ request }) => {
    const response = await request.get(`${API_BASE}/public/business/${TEST_BUSINESS_ID}`, {
      headers: { 'Accept': 'application/json' }
    });
    
    expect(response.status()).toBe(200);
    const data = await response.json();
    
    console.log('Business Info:', JSON.stringify(data, null, 2));
    
    // API returns { business: { ... } } wrapper
    const business = data.business || data;
    expect(business.id).toBe(TEST_BUSINESS_ID);
    expect(business.business_name).toBe(BUSINESS_NAME);
    expect(business.slug).toBe('test-cafe');
  });

  test('3. Get Business Branches', async ({ request }) => {
    const response = await request.get(`${API_BASE}/public/business/${TEST_BUSINESS_ID}/branches`, {
      headers: { 'Accept': 'application/json' }
    });
    
    expect(response.status()).toBe(200);
    const data = await response.json();
    
    console.log('Branches:', JSON.stringify(data, null, 2));
    
    // API returns { branches: [...] } wrapper
    const branches = data.branches || data;
    expect(Array.isArray(branches)).toBe(true);
    expect(branches.length).toBeGreaterThan(0);
    expect(branches[0].branch_code).toBe(TEST_QR_HASH);
  });

  test('4. Get Public Rewards', async ({ request }) => {
    // API requires business_id as query parameter
    const response = await request.get(`${API_BASE}/public/rewards?business_id=${TEST_BUSINESS_ID}`, {
      headers: { 
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });
    
    expect(response.status()).toBe(200);
    const data = await response.json();
    
    console.log('Public Rewards:', JSON.stringify(data, null, 2));
    
    // API returns { rewards: [...] } wrapper
    const rewards = data.rewards || data;
    expect(Array.isArray(rewards)).toBe(true);
  });

  test('5. Create Customer Join', async ({ request }) => {
    const response = await request.post(`${API_BASE}/public/customer/join`, {
      data: {
        business_id: TEST_BUSINESS_ID,
        branch_id: 1,
        phone: CUSTOMER_PHONE,
        qr_hash: TEST_QR_HASH
      },
      headers: { 'Content-Type': 'application/json' }
    });
    
    console.log('Join Response Status:', response.status());
    const body = await response.text();
    console.log('Join Response Body:', body);
    
    // Should succeed or return a specific error (customer may already exist)
    expect(response.status()).toBeLessThan(500);
  });

  test('6. Customer Stamp Request', async ({ request }) => {
    // Create stamp request using authenticated customer API key
    // The API will look up customer_id from the authenticated session (phone in API key)
    const stampResponse = await request.post(`${API_BASE}/customer/stamp-request`, {
      data: {
        business_id: TEST_BUSINESS_ID,
        branch_id: 1,
        // customer_id not needed - looked up from auth
        qr_hash: TEST_QR_HASH,
        requested_stamps: 1
      },
      headers: {
        'Content-Type': 'application/json',
        'X-MyPerk-API-Key': 'ck_myperk_customer_test'
      }
    });
    
    console.log('Stamp Request Status:', stampResponse.status());
    const stampBody = await stampResponse.text();
    console.log('Stamp Request Body:', stampBody);
    
    // Should create a pending request
    expect(stampResponse.status()).toBeLessThan(500);
  });

  test('7. Staff Get Pending Requests', async ({ request }) => {
    // business_id is required per API route definition
    const response = await request.get(`${API_BASE}/staff/requests?business_id=${TEST_BUSINESS_ID}`, {
      headers: {
        'Content-Type': 'application/json',
        'X-MyPerk-API-Key': 'wk_myperk_staff_1'
      }
    });
    
    expect(response.status()).toBe(200);
    const data = await response.json();
    
    console.log('Staff Requests:', JSON.stringify(data, null, 2));
    
    // API returns { requests: [...] } wrapper
    const requests = data.requests || data;
    expect(Array.isArray(requests)).toBe(true);
  });

  test('8. Staff Approve Request', async ({ request }) => {
    // First get pending requests
    const listResponse = await request.get(`${API_BASE}/staff/requests?business_id=${TEST_BUSINESS_ID}`, {
      headers: {
        'Content-Type': 'application/json',
        'X-MyPerk-API-Key': 'wk_myperk_staff_1'
      }
    });
    
    const data = await listResponse.json();
    console.log('Pending requests:', JSON.stringify(data, null, 2));
    
    // API returns { requests: [...] } wrapper
    const requests = data.requests || data;
    
    if (requests.length > 0) {
      // Test process endpoint (API uses /process, not /approve)
      const requestId = requests[0].id || requests[0].request_id;
      
      const processResponse = await request.post(`${API_BASE}/staff/requests/${requestId}/process`, {
        data: { decision: 'approve', approved_stamps: 1 },
        headers: {
          'Content-Type': 'application/json',
          'X-MyPerk-API-Key': 'wk_myperk_staff_1'
        }
      });
      
      console.log('Process Response:', processResponse.status());
      expect(processResponse.status()).toBeLessThan(500);
    }
  });

  test('9. Business Dashboard Metrics', async ({ request }) => {
    const response = await request.get(`${API_BASE}/business/${TEST_BUSINESS_ID}/dashboard`, {
      headers: {
        'Content-Type': 'application/json',
        'X-MyPerk-API-Key': 'bk_myperk_business_1'
      }
    });
    
    expect(response.status()).toBe(200);
    const data = await response.json();
    
    console.log('Dashboard Data:', JSON.stringify(data, null, 2));
    
    // Verify response structure
    expect(data).toHaveProperty('metrics');
    expect(data).toHaveProperty('activity');
    expect(data).toHaveProperty('subscription');
    expect(data.metrics).toHaveProperty('total_members');
    expect(data.metrics).toHaveProperty('pending_requests');
  });

  test('10. Customer Cards List', async ({ request }) => {
    const response = await request.get(`${API_BASE}/customer/cards`, {
      headers: {
        'Content-Type': 'application/json',
        'X-MyPerk-API-Key': 'ck_myperk_customer_test'
      }
    });
    
    expect(response.status()).toBe(200);
    const data = await response.json();
    
    console.log('Customer Cards:', JSON.stringify(data, null, 2));
    
    // API returns { cards: [...] } wrapper
    const cards = data.cards || data;
    expect(Array.isArray(cards)).toBe(true);
  });

  test('11. Admin Dashboard', async ({ request }) => {
    const response = await request.get(`${API_BASE}/admin/dashboard`, {
      headers: {
        'Content-Type': 'application/json',
        'X-MyPerk-API-Key': 'sk_myperk_super_admin_123'
      }
    });
    
    expect(response.status()).toBe(200);
    const data = await response.json();
    
    console.log('Admin Dashboard:', JSON.stringify(data, null, 2));
    
    // Verify response structure
    expect(data).toHaveProperty('metrics');
    expect(data.metrics).toHaveProperty('total_businesses');
    expect(data.metrics).toHaveProperty('active_subscriptions');
  });
});