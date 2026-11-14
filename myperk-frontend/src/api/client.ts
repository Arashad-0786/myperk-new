import axios from 'axios';

import type { 
  QRVerifyResult, 
  LoyaltyCard, 
  StampRequest, 
  Business, 
  Branch,
  Subscription,
  LedgerEntry
} from '../types';

// Standalone MyPerk REST API Client connecting to PHP backend/MySQL 8.4
const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost:8080/myperk/v1';

const apiClient = axios.create({
  baseURL: API_BASE,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor to add API key from localStorage
apiClient.interceptors.request.use(
  (config) => {
    // Read API key from localStorage (set by AuthContext)
    const stored = localStorage.getItem('myperk_user');
    if (stored) {
      try {
        const user = JSON.parse(stored);
        if (user.apiKey) {
          config.headers['X-MyPerk-API-Key'] = user.apiKey;
        }
      } catch (e) {
        // Ignore parse errors
      }
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor for unified error handling
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const message = error.response?.data?.message || 'An unexpected error occurred.';
    return Promise.reject(new Error(message));
  }
);

export const api = {
  // ==========================================
  // PUBLIC ENDPOINTS
  // ==========================================

  /** Verify Static QR Code Hash POST /myperk/v1/public/qr/verify */
  verifyQR: async (qrHash: string): Promise<QRVerifyResult> => {
    const response = await apiClient.post<QRVerifyResult>('/public/qr/verify', {
      qr_hash: qrHash,
    });
    return response.data;
  },

  /** Join Loyalty Program after scanning QR POST /myperk/v1/public/customer/join */
  customerJoin: async (data: {
    business_id: number;
    branch_id: number;
    phone: string;
    name?: string;
  }): Promise<{ success: boolean; customer_id: number; card_id: number; message: string }> => {
    const response = await apiClient.post('/public/customer/join', data);
    return response.data;
  },

  // ==========================================
  // CUSTOMER ENDPOINTS
  // ==========================================

  /** Create a Stamp Request (Customer side) POST /myperk/v1/customer/stamp-request */
  createStampRequest: async (data: {
    business_id: number;
    branch_id: number;
    qr_hash: string;
    requested_stamps?: number;
  }): Promise<{ success: boolean; request_id: number; status: string; message: string }> => {
    const response = await apiClient.post('/customer/stamp-request', data);
    return response.data;
  },

  /** Get Customer's Loyalty Cards GET /myperk/v1/customer/cards */
  getCustomerCards: async (): Promise<{ cards: LoyaltyCard[] }> => {
    const response = await apiClient.get('/customer/cards');
    return response.data;
  },

  /** Get Customer's Specific Card Details GET /myperk/v1/customer/cards/{business_id} */
  getCardDetails: async (businessId: number): Promise<LoyaltyCard> => {
    const response = await apiClient.get(`/customer/cards/${businessId}`);
    return response.data;
  },

  // ==========================================
  // STAFF ENDPOINTS
  // ==========================================

  /** Get Pending Stamp Requests for Staff Dashboard GET /myperk/v1/staff/requests */
  getPendingRequests: async (
    businessId: number, 
    branchId?: number, 
    limit = 50
  ): Promise<{ requests: StampRequest[] }> => {
    const response = await apiClient.get('/staff/requests', {
      params: { business_id: businessId, branch_id: branchId, limit },
    });
    return response.data;
  },

  /** Process (Approve/Reject) Stamp Request (Staff side) POST /myperk/v1/staff/requests/{id}/process */
  processStampRequest: async (
    requestId: number, 
    decision: 'approve' | 'reject', 
    approvedStamps = 1
  ): Promise<{ success: boolean; request_id: number; status: string; message: string }> => {
    const response = await apiClient.post(`/staff/requests/${requestId}/process`, {
      id: requestId,
      decision,
      approved_stamps: approvedStamps,
    });
    return response.data;
  },

  // ==========================================
  // BUSINESS OWNER ENDPOINTS
  // ==========================================

  /** Get Business Owner Dashboard Data GET /myperk/v1/business/{id}/dashboard */
  getBusinessDashboard: async (businessId: number): Promise<{
    metrics: {
      total_members: number;
      stamps_30d: number;
      rewards_redeemed_30d: number;
      pending_requests: number;
    };
    activity: LedgerEntry[];
    subscription: Subscription;
  }> => {
    const response = await apiClient.get(`/business/${businessId}/dashboard`);
    return response.data;
  },

  /** Get Business Settings & Branches GET /myperk/v1/business/{id}/settings */
  getBusinessSettings: async (businessId: number): Promise<{
    business: Business;
    branches: Branch[];
  }> => {
    const response = await apiClient.get(`/business/${businessId}/settings`);
    return response.data;
  },

  /** Update Business Settings POST /myperk/v1/business/{id}/settings */
  updateBusinessSettings: async (
    businessId: number, 
    settings: Partial<Business>
  ): Promise<{ success: boolean }> => {
    const response = await apiClient.post(`/business/${businessId}/settings`, settings);
    return response.data;
  },

  /** Create New Branch POST /myperk/v1/business/{id}/branches */
  createBranch: async (
    businessId: number, 
    branchData: {
      branch_name: string;
      branch_code: string;
      address?: string;
      phone?: string;
    }
  ): Promise<{ success: boolean; branch_id: number }> => {
    const response = await apiClient.post(`/business/${businessId}/branches`, branchData);
    return response.data;
  },

  // ==========================================
  // SUPER ADMIN ENDPOINTS
  // ==========================================

  /** Get Super Admin Platform Dashboard Metrics GET /myperk/v1/admin/dashboard */
  getAdminDashboard: async (): Promise<{
    metrics: {
      total_businesses: number;
      active_subscriptions: number;
      trialing_businesses: number;
      total_stamps_issued: number;
    };
  }> => {
    const response = await apiClient.get('/admin/dashboard');
    return response.data;
  },

  /** Get All Businesses Listing (Admin) GET /myperk/v1/admin/businesses */
  getAllBusinesses: async (page = 1): Promise<{
    businesses: Array<Business & { sub_status: string; plan_code: string; member_count: number }>;
    page: number;
  }> => {
    const response = await apiClient.get('/admin/businesses', { params: { page } });
    return response.data;
  },
};

export default api;