import React, { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';

const LoginPage: React.FC = () => {
  const { role, login, isAuthenticated } = useAuth();
  const navigate = useNavigate();

  // If already authenticated, redirect based on role
  useEffect(() => {
    if (isAuthenticated && role !== 'anonymous') {
      // Redirect based on role
      switch (role) {
        case 'customer':
          navigate('/customer/dashboard', { replace: true });
          break;
        case 'staff':
          navigate('/staff/requests', { replace: true });
          break;
        case 'business_owner':
          navigate('/business/dashboard', { replace: true });
          break;
        case 'super_admin':
          navigate('/admin/dashboard', { replace: true });
          break;
        default:
          navigate('/', { replace: true });
      }
    }
  }, [isAuthenticated, role, navigate]);

  const handleLogin = (userRole: 'customer' | 'staff' | 'business_owner' | 'super_admin') => {
    login(userRole);
  };

  return (
    <div className="myperk-container myperk-login-page">
      <div className="myperk-login-card">
        <h2>MyPerk Login</h2>
        <p className="myperk-login-subtitle">Please select your role to continue</p>
        
        <div className="myperk-auth-buttons">
          <button
            className="myperk-btn myperk-btn-primary myperk-btn-large"
            onClick={() => handleLogin('customer')}
          >
            Customer
          </button>
          <button
            className="myperk-btn myperk-btn-outline myperk-btn-large"
            onClick={() => handleLogin('staff')}
          >
            Staff
          </button>
          <button
            className="myperk-btn myperk-btn-outline myperk-btn-large"
            onClick={() => handleLogin('business_owner')}
          >
            Business Owner
          </button>
          <button
            className="myperk-btn myperk-btn-outline myperk-btn-large"
            onClick={() => handleLogin('super_admin')}
          >
            Admin
          </button>
        </div>
        
        <p className="myperk-login-desc">
          MyPerk Loyalty Platform — Multi-business Loyalty & Rewards System
        </p>
      </div>
    </div>
  );
};

export default LoginPage;