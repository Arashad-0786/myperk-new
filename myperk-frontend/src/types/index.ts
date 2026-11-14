// TypeScript Interfaces for MyPerk SaaS API

export interface Business {
  id: number;
  business_name: string;
  slug: string;
  legal_name?: string;
  gstin?: string;
  contact_email: string;
  contact_phone?: string;
  address?: string;
  timezone?: string;
  currency?: string;
  logo_url?: string;
  brand_color?: string;
  status: 'trialing' | 'active' | 'grace' | 'suspended' | 'cancelled';
  created_at: string;
  updated_at: string;
}

export interface Branch {
  id: number;
  business_id: number;
  branch_name: string;
  branch_code: string;
  address?: string;
  phone?: string;
  status: 'active' | 'inactive';
  created_at: string;
}

export interface Customer {
  id: number;
  wp_user_id?: number;
  phone: string;
  full_name?: string;
  email?: string;
  status: 'active' | 'blocked';
  created_at: string;
}

export interface LoyaltyCard {
  id: number;
  business_id: number;
  customer_id: number;
  stamp_count: number;
  rewards_redeemed_count: number;
  status: 'active' | 'revoked';
  joined_at: string;
  business_name?: string;
  slug?: string;
  logo_url?: string;
  brand_color?: string;
}

export interface StampRequest {
  id: number;
  business_id: number;
  branch_id: number;
  customer_id: number;
  staff_id?: number;
  requested_stamps: number;
  approved_stamps: number;
  status: 'pending' | 'approved' | 'rejected' | 'expired';
  qr_hash: string;
  notes?: string;
  created_at: string;
  processed_at?: string;
  customer_name?: string;
  customer_phone?: string;
  branch_name?: string;
}

export interface LedgerEntry {
  id: number;
  business_id: number;
  customer_id: number;
  card_id: number;
  branch_id?: number;
  actor_id: number;
  actor_type: 'customer' | 'staff' | 'system' | 'admin';
  delta: number;
  balance_after: number;
  transaction_type: 'stamp_approved' | 'reward_redeemed' | 'manual_adjust';
  reference_id?: number;
  notes?: string;
  created_at: string;
  customer_name?: string;
  branch_name?: string;
}

export interface Subscription {
  id: number;
  business_id: number;
  plan_code: 'starter' | 'growth' | 'pro';
  status: 'trialing' | 'active' | 'grace' | 'cancelled';
  billing_period: 'month' | 'year';
  provider_subscription_id?: string;
  current_period_start: string;
  current_period_end: string;
}

export interface Invoice {
  id: number;
  public_id: string;
  business_id: number;
  subscription_id: number;
  invoice_number: string;
  amount_paise: number;
  tax_paise: number;
  currency: string;
  status: 'issued' | 'paid' | 'failed';
  issued_at: string;
  paid_at?: string;
}

export interface QRVerifyResult {
  valid: boolean;
  business_id?: number;
  branch_id?: number;
  business_name?: string;
  branch_name?: string;
  logo_url?: string;
  brand_color?: string;
  message?: string;
}

export interface APIResponse<T> {
  success?: boolean;
  message?: string;
  data?: T;
}
