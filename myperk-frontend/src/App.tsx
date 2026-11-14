import React, { useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, NavLink } from 'react-router-dom';
import { useAuth } from './context/AuthContext';

// Public pages (no auth required)
import LoginPage from './pages/public/LoginPage';
import QRScanPage from './pages/public/QRScanPage';
import WelcomePage from './pages/public/WelcomePage';

// Customer pages
import CustomerDashboardPage from './pages/customer/CustomerDashboardPage';
import CustomerCardsPage from './pages/customer/CustomerCardsPage';

// Staff pages
import StaffRequestsPage from './pages/staff/StaffRequestsPage';

// Business owner pages
import BusinessDashboardPage from './pages/business/BusinessDashboardPage';
import BusinessSettingsPage from './pages/business/BusinessSettingsPage';

// Admin pages
import AdminDashboardPage from './pages/admin/AdminDashboardPage';
import BusinessListPage from './pages/admin/BusinessListPage';

function CustomerLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="myperk-shell">
      <div className="myperk-container">
        <nav className="myperk-nav">
          <NavLink to="/customer/dashboard" className="myperk-nav-link">Dashboard</NavLink>
          <NavLink to="/customer/cards" className="myperk-nav-link">My Cards</NavLink>
        </nav>
        <div className="myperk-content">{children}</div>
      </div>
    </div>
  );
}

function StaffLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="myperk-shell">
      <div className="myperk-container">
        <nav className="myperk-nav">
          <NavLink to="/staff/requests" className="myperk-nav-link">Pending Requests</NavLink>
        </nav>
        <div className="myperk-content">{children}</div>
      </div>
    </div>
  );
}

function BusinessLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="myperk-shell">
      <div className="myperk-container">
        <nav className="myperk-nav">
          <NavLink to="/business/dashboard" className="myperk-nav-link">Dashboard</NavLink>
          <NavLink to="/business/settings" className="myperk-nav-link">Settings</NavLink>
        </nav>
        <div className="myperk-content">{children}</div>
      </div>
    </div>
  );
}

function AdminLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="myperk-shell">
      <div className="myperk-container">
        <nav className="myperk-nav">
          <NavLink to="/admin/dashboard" className="myperk-nav-link">Platform Dashboard</NavLink>
          <NavLink to="/admin/businesses" className="myperk-nav-link">Businesses</NavLink>
        </nav>
        <div className="myperk-content">{children}</div>
      </div>
    </div>
  );
}

const App: React.FC = () => {
  const { role, isAuthenticated, logout } = useAuth();

  // Redirect based on auth state
  useEffect(() => {
    if (isAuthenticated && role === 'anonymous') {
      // Shouldn't happen, but safety check
      logout();
    }
  }, [isAuthenticated, role, logout]);

  // Route protection based on role
  const renderRoute = () => {
    // Public routes - accessible to all
    if (!isAuthenticated || role === 'anonymous') {
      return (
        <Routes>
          <Route path="/" element={<WelcomePage />} />
          <Route path="login" element={<LoginPage />} />
          <Route path="/qr-scan" element={<QRScanPage />} />
        </Routes>
      );
    }

    // Customer routes
    if (role === 'customer') {
      return (
        <CustomerLayout>
          <Routes>
            <Route path="/customer/dashboard" element={<CustomerDashboardPage />} />
            <Route path="/customer/cards" element={<CustomerCardsPage />} />
          </Routes>
        </CustomerLayout>
      );
    }

    // Staff routes
    if (role === 'staff') {
      return (
        <StaffLayout>
          <Routes>
            <Route path="/staff/requests" element={<StaffRequestsPage />} />
          </Routes>
        </StaffLayout>
      );
    }

    // Business owner routes
    if (role === 'business_owner') {
      return (
        <BusinessLayout>
          <Routes>
            <Route path="/business/dashboard" element={<BusinessDashboardPage />} />
            <Route path="/business/settings" element={<BusinessSettingsPage />} />
          </Routes>
        </BusinessLayout>
      );
    }

    // Admin routes
    if (role === 'super_admin') {
      return (
        <AdminLayout>
          <Routes>
            <Route path="/admin/dashboard" element={<AdminDashboardPage />} />
            <Route path="/admin/businesses" element={<BusinessListPage />} />
          </Routes>
        </AdminLayout>
      );
    }

    // Fallback - login again
    return (
      <Routes>
        <Route path="/" element={<WelcomePage />} />
        <Route path="login" element={<LoginPage />} />
      </Routes>
    );
  };

  return (
    <Router>
      {renderRoute()}
    </Router>
  );
};

export default App;