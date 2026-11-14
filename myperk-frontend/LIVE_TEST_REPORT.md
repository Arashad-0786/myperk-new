# MyPerk Live Browser Testing Report

## Test Execution Summary

**Date:** November 13, 2026  
**Environment:** Windows 11 / PHP 8.3 / MySQL 8.4 / Node.js/Vite / React 19  
**Backend Port:** 8080  
**Frontend Port:** 5173  
**Test Framework:** Playwright Test (Headed Mode)  
**Test Data:** Test Cafe (ID: 1, slug: test-cafe), QR Hash: TC001  

---

## Test Results - COMPLETED

### 0. API Health Check
**Status:** ✅ PASSED
- QR verification endpoint responding correctly
- Response structure: `{"valid":true,"business_id":1,"business_name":"Test Cafe","branch_slug":"test-cafe","branch_id":1,"branch_name":"Main","message":"QR verified successfully."}`

### 1. QR Code Verification
**Status:** ✅ PASSED
- Endpoint: `POST /public/qr/verify` with `{"qr_hash":"TC001"}`
- Returns correct business and branch information
- Response matches expected structure

### 2. Business Info Endpoint
**Status:** ✅ PASSED
- Endpoint: `GET /public/business/1`
- **Issue Fixed:** API wraps business data in `{ "business": {...} }` structure
- **Fix Applied:** Tests updated to handle wrapped response
- Response: `{ id: 1, business_name: "Test Cafe", slug: "test-cafe", ... }`

### 3. Get Business Branches
**Status:** ✅ PASSED
- Endpoint: `GET /public/business/1/branches`
- **Issue Fixed:** API wraps branches array in `{ "branches": [...] }` structure
- **Fix Applied:** Tests updated to handle wrapped response
- Response: Array of branch objects with `branch_code`, `branch_name`, etc.

### 4. Get Public Rewards
**Status:** ✅ PASSED
- Endpoint: `GET /public/rewards`
- **Issue Fixed:** Endpoint requires `business_id` query parameter
- **Fix Applied:** Tests updated to include `?business_id=1`
- Response: `{ rewards: [...] }` array of active rewards

### 5. Create Customer Join
**Status:** ✅ PASSED
- Endpoint: `POST /public/customer/join`
- Customer creation working correctly
- Returns customer_id and card_id for new customers

### 6. Customer Stamp Request
**Status:** ✅ PASSED
- Endpoint: `POST /customer/stamp-request`
- Authentication working with `ck_myperk_customer_test` API key
- Stamp requests created successfully for authenticated customers

### 7. Staff Get Pending Requests
**Status:** ✅ PASSED
- Endpoint: `GET /staff/requests?business_id=1`
- **Issue Fixed:** Staff authorization check was failing due to missing `myperk_staff_can_process` filter
- **Fix Applied:** Added `add_filter` implementation in `standalone-server.php`
- **Fix Applied:** Added `business_id` query parameter required by route
- Response: `{ requests: [...] }` array of pending stamp requests

### 8. Staff Process Stamp Request
**Status:** ✅ PASSED
- Endpoint: `POST /staff/requests/{id}/process` (not `/approve`)
- **Issue Fixed:** API uses `/process` endpoint with `decision` and `approved_stamps` params
- **Fix Applied:** Tests updated to use correct endpoint and parameters
- Response: Success with updated stamp counts

### 9. Business Dashboard Metrics
**Status:** ✅ PASSED
- Endpoint: `GET /business/1/dashboard`
- Authentication working with `bk_myperk_business_1` API key
- Response includes: metrics, activity, subscription data
- Returns nested objects with metrics, activity stream, and subscription info

### 10. Customer Cards List
**Status:** ✅ PASSED
- Endpoint: `GET /customer/cards`
- Authentication working with `ck_myperk_customer_test` API key
- **Issue Fixed:** API wraps cards in `{ "cards": [...] }` structure
- **Fix Applied:** Tests updated to handle wrapped response
- Response: `{ cards: [...] }` array of customer cards with stamp counts

### 11. Admin Dashboard
**Status:** ✅ PASSED
- Endpoint: `GET /admin/dashboard`
- Authentication working with `sk_myperk_super_admin_123` API key
- Response includes platform-wide metrics

---

## Fixes Applied to Test File

1. **Tests 2, 3:** Updated to handle API response wrappers (`{ business: {...} }`, `{ branches: [...] }`, `{ cards: [...] }`, `{ rewards: [...] }`)
2. **Test 4:** Added `business_id` query parameter required by `/public/rewards` endpoint
3. **Tests 7, 8:** 
   - Added `business_id` query parameter to staff requests endpoint
   - Changed `/approve` to `/process` endpoint
   - Updated request body to use `decision` and `approved_stamps` parameters

---

## Fixes Applied to Backend

1. **Added `add_filter()` function** in `standalone-server.php` (line 46-52) - was missing, causing filter registration to fail
2. **Added `myperk_staff_can_process` filter** in `standalone-server.php` (line 559-580) - implements staff authorization check for standalone mode
3. **Updated filter to use object method** - `MyPerk_Standalone_Auth::get_instance()` returns object, not array

---

## API Response Format Reference

| Endpoint | Response Wrapper |
|----------|------------------|
| `/public/business/{id}` | `{ business: { ... } }` |
| `/public/business/{id}/branches` | `{ branches: [...] }` |
| `/public/rewards` | `{ rewards: [...] }` |
| `/customer/cards` | `{ cards: [...] }` |
| `/staff/requests` | `{ requests: [...] }` |
| `/business/{id}/dashboard` | `{ metrics, activity, subscription }` |
| `/admin/dashboard` | `{ metrics, subscriptions, ... }` |
| `/public/qr/verify` | Direct response (no wrapper) |

---

## Test Summary

**Total Tests:** 12  
**Passed:** 12 ✅  
**Failed:** 0  
**Execution Time:** 5.5s  

### Test Execution Results

```
  ok  1 [MyPerk Live Tests] › tests\e2e\myperk-api-workflow.spec.ts:20:3 › 0. API Health Check
  ok  2 [MyPerk Live Tests] › tests\e2e\myperk-api-workflow.spec.ts:45:3 › 1. QR Verification
  ok  3 [MyPerk Live Tests] › tests\e2e\myperk-api-workflow.spec.ts:62:3 › 2. Business Info Endpoint
  ok  4 [MyPerk Live Tests] › tests\e2e\myperk-api-workflow.spec.ts:79:3 › 3. Get Business Branches
  ok  5 [MyPerk Live Tests] › tests\e2e\myperk-api-workflow.spec.ts:96:3 › 4. Get Public Rewards
  ok  6 [MyPerk Live Tests] › tests\e2e\myperk-api-workflow.spec.ts:115:3 › 5. Create Customer Join
  ok  7 [MyPerk Live Tests] › tests\e2e\myperk-api-workflow.spec.ts:134:3 › 6. Customer Stamp Request
  ok  8 [MyPerk Live Tests] › tests\e2e\myperk-api-workflow.spec.ts:159:3 › 7. Staff Get Pending Requests
  ok  9 [MyPerk Live Tests] › tests\e2e\myperk-api-workflow.spec.ts:178:3 › 8. Staff Process Stamp Request
  ok 10 [MyPerk Live Tests] › tests\e2e\myperk-api-workflow.spec.ts:210:3 › 9. Business Dashboard Metrics
  ok  11 [MyPerk Live Tests] › tests\e2e\myperk-api-workflow.spec.ts:224:3 › 10. Customer Cards List
  ok  12 [MyPerk Live Tests] › tests\e2e\myperk-api-workflow.spec.ts:242:3 › 11. Admin Dashboard
```

---

## Test Data

| Entity | ID | Identifier | Notes |
|--------|-----|------------|-------|
| Business | 1 | Test Cafe | slug: test-cafe, branch_code: TC001 |
| Customer | 2 | Test User | Phone: +1234567890 |
| Pending Requests | 24 | Various | Available for staff approval |
| Staff | (via API key) | wk_myperk_staff_1 | Can approve requests |
| Business Owner | (via API key) | bk_myperk_business_1 | Can view dashboard |
| Admin | (via API key) | sk_myperk_super_admin_123 | Full access |