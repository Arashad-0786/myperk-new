import React, { createContext, useContext, useState, useEffect } from 'react';
import type { ReactNode } from 'react';

// API keys for different roles (these are provided by the backend for testing)
export const API_KEYS = {
  super_admin: 'sk_myperk_super_admin_123',
  business_owner: 'bk_myperk_business_1',
  staff: 'wk_myperk_staff_1',
  customer: 'ck_myperk_customer_test',
};

// User role context type
export type UserRole = 'customer' | 'staff' | 'business_owner' | 'super_admin' | 'anonymous';

export interface UserContextType {
  role: UserRole;
  userId?: number;
  businessId?: number;
  customerId?: number;
  apiKey?: string;
  login: (role: UserRole, userId?: number, businessId?: number, customerId?: number) => void;
  logout: () => void;
  isAuthenticated: boolean;
}

// Create context with default anonymous state
const AuthContext = createContext<UserContextType | null>(null);

// Custom hook to use context
export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

// Provider component
export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [role, setRole] = useState<UserRole>('anonymous');
  const [userId, setUserId] = useState<number | undefined>(undefined);
  const [businessId, setBusinessId] = useState<number | undefined>(undefined);
  const [customerId, setCustomerId] = useState<number | undefined>(undefined);
  const [apiKey, setApiKey] = useState<string | undefined>(undefined);

  // Initialize from localStorage if available
  useEffect(() => {
    const stored = localStorage.getItem('myperk_user');
    if (stored) {
      const user = JSON.parse(stored);
      setRole(user.role);
      setUserId(user.userId);
      setBusinessId(user.businessId);
      setCustomerId(user.customerId);
    }
  }, []);

  // Persist to localStorage on changes
  useEffect(() => {
    const userData = {
      role,
      userId,
      businessId,
      customerId,
      apiKey: apiKey || null,
    };
    localStorage.setItem('myperk_user', JSON.stringify(userData));
  }, [role, userId, businessId, customerId, apiKey]);

  // Helper to get API key for a role
  const getApiKeyForRole = (userRole: UserRole): string | undefined => {
    return API_KEYS[userRole as keyof typeof API_KEYS];
  };

  const login = (userRole: UserRole, uid?: number, bid?: number, cid?: number) => {
    setRole(userRole);
    setUserId(uid);
    setBusinessId(bid);
    setCustomerId(cid);
    
    // Set the appropriate API key for this role
    const key = getApiKeyForRole(userRole);
    setApiKey(key);
  };

  const logout = () => {
    setRole('anonymous');
    setUserId(undefined);
    setBusinessId(undefined);
    setCustomerId(undefined);
    setApiKey(undefined);
    localStorage.removeItem('myperk_user');
  };

  return (
    <AuthContext.Provider value={{ role, userId, businessId, customerId, apiKey, login, logout, isAuthenticated: role !== 'anonymous' }}>
      {children}
    </AuthContext.Provider>
  );
};

// Export component to get role-based access
export const useUserRole = () => useAuth();

// Export function to get API key directly (for use in API client)
export const getCurrentApiKey = (): string | undefined => {
  // This will be called from the API client
  return undefined; // Will be accessed via context
};