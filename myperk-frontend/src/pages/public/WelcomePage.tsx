import React from 'react';
import { useAuth } from '../../context/AuthContext';

const WelcomePage: React.FC = () => {
  const { login, role } = useAuth();

  // If already logged in, redirect implicitly via routing
  if (role !== 'anonymous') {
    return null; // Will be rendered by App routing which will redirect
  }

  const handleCustomerLogin = () => {
    login('customer');
  };

  const handleStaffLogin = () => {
    login('staff');
  };

  const handleBusinessOwnerLogin = () => {
    login('business_owner');
  };

  const handleAdminLogin = () => {
    login('super_admin');
  };

  return (
    <div className="myperk-container myperk-welcome-page">
      <h1 className="myperk-title">MyPerk Loyalty Platform</h1>
      <p className="myperk-subtitle">Multi-business Loyalty & Rewards System</p>
      <div className="myperk-auth-buttons">
        <button 
          className="myperk-btn myperk-btn-primary myperk-btn-large"
          onClick={handleCustomerLogin}
        >
          Customer Login
        </button>
        <button 
          className="myperk-btn myperk-btn-outline"
          onClick={handleStaffLogin}
        >
          Staff Login
        </button>
        <button 
          className="myperk-btn myperk-btn-outline"
          onClick={handleBusinessOwnerLogin}
        >
          Business Owner Login
        </button>
        <button 
          className="myperk-btn myperk-btn-outline"
          onClick={handleAdminLogin}
        >
          Admin Login
        </button>
      </div>
      <p className="myperk-desc">
        Scanning QR code to join loyalty programs,<br />
        requesting stamps, and managing rewards.
      </p>
    </div>
  );
};

export default WelcomePage;