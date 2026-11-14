import React, { useEffect, useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { api } from '../../api/client';

const AdminDashboardPage: React.FC = () => {
  const { userId } = useAuth();
  const [dashboard, setDashboard] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadDashboard = async () => {
      if (!userId) return;
      try {
        const response = await api.getAdminDashboard();
        setDashboard(response);
      } catch (err: any) {
        setError(err.message || 'Failed to load admin dashboard');
      } finally {
        setLoading(false);
      }
    };
    loadDashboard();
  }, [userId]);

  if (loading) {
    return (
      <div className="myperk-container">
        <p>Loading admin dashboard...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="myperk-container">
        <p className="myperk-alert myperk-alert-error">{error}</p>
      </div>
    );
  }

  const metrics = dashboard?.metrics || {};

  return (
    <div className="myperk-container myperk-admin-dashboard">
      <header className="myperk-dashboard-header">
        <h2>Platform Dashboard</h2>
        <p>MyPerk SaaS Platform Overview</p>
      </header>

      <div className="myperk-admin-grid">
        {/* Key Platform Metrics */}
        <section className="myperk-admin-metrics">
          <div className="myperk-admin-metric-card">
            <div className="myperk-admin-metric-value">
              {metrics.total_businesses > 0 ? metrics.total_businesses : 0}
            </div>
            <div className="myperk-admin-metric-label">Total Businesses</div>
          </div>
          <div className="myperk-admin-metric-card">
            <div className="myperk-admin-metric-value">
              {metrics.active_subscriptions > 0 ? metrics.active_subscriptions : 0}
            </div>
            <div className="myperk-admin-metric-label">Active Subscriptions</div>
          </div>
          <div className="myperk-admin-metric-card">
            <div className="myperk-admin-metric-value">
              {metrics.trialing_businesses > 0 ? metrics.trialing_businesses : 0}
            </div>
            <div className="myperk-admin-metric-label">Trialing Businesses</div>
          </div>
          <div className="myperk-admin-metric-card">
            <div className="myperk-admin-metric-value">
              {metrics.total_stamps_issued > 0 ? metrics.total_stamps_issued : 0}
            </div>
            <div className="myperk-admin-metric-label">Total Stamps Issued</div>
          </div>
        </section>
      </div>
    </div>
  );
};

export default AdminDashboardPage;