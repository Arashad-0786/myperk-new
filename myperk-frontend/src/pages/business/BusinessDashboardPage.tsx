import React, { useEffect, useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { api } from '../../api/client';

const BusinessDashboardPage: React.FC = () => {
  const { businessId } = useAuth();
  const [dashboard, setDashboard] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadDashboard = async () => {
      if (!businessId) return;
      try {
        const response = await api.getBusinessDashboard(businessId);
        setDashboard(response);
      } catch (err: any) {
        setError(err.message || 'Failed to load dashboard data');
      } finally {
        setLoading(false);
      }
    };
    loadDashboard();
  }, [businessId]);

  if (loading) {
    return (
      <div className="myperk-container">
        <p>Loading dashboard...</p>
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
  const activity = dashboard?.activity || [];
  const subscription = dashboard?.subscription;

  return (
    <div className="myperk-container myperk-business-dashboard">
      <header className="myperk-dashboard-header">
        <h2>Business Dashboard</h2>
        <p>{dashboard?.business?.business_name || 'Your Business'}</p>
      </header>

      <div className="myperk-dashboard-grid">
        {/* Key Metrics Cards */}
        <section className="myperk-metrics">
          <div className="myperk-metric-card">
            <div className="myperk-metric-value">
              {metrics.total_members > 0 ? metrics.total_members : 0}
            </div>
            <div className="myperk-metric-label">Total Members</div>
          </div>
          <div className="myperk-metric-card">
            <div className="myperk-metric-value">
              {metrics.stamps_30d > 0 ? metrics.stamps_30d : 0}
            </div>
            <div className="myperk-metric-label">Stamps (30 days)</div>
          </div>
          <div className="myperk-metric-card">
            <div className="myperk-metric-value">
              {metrics.rewards_redeemed_30d > 0 ? metrics.rewards_redeemed_30d : 0}
            </div>
            <div className="myperk-metric-label">Rewards Redeemed</div>
          </div>
          <div className="myperk-metric-card">
            <div className="myperk-metric-value">
              {metrics.pending_requests > 0 ? metrics.pending_requests : 0}
            </div>
            <div className="myperk-metric-label">Pending Requests</div>
          </div>
        </section>

        {/* Recent Activity */}
        <section className="myperk-activity">
          <h3>Recent Activity</h3>
          {activity.length === 0 && (
            <p>No recent activity.</p>
          )}
          <div className="myperk-activity-list">
            {activity.slice(0, 10).map((entry: any) => (
              <div key={entry.id} className="myperk-activity-item">
                <div className="myperk-activity-icon">
                  {entry.transaction_type === 'stamp_approved' ? '📋' : '💳'}
                </div>
                <div className="myperk-activity-details">
                  <p className="myperk-activity-customer">
                    {entry.customer_name || 'Customer'}
                  </p>
                  <p className="myperk-activity-action">
                    {entry.transaction_type}
                  </p>
                  <p className="myperk-activity-time">
                    {new Date(entry.created_at).toLocaleDateString()}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </section>

        {/* Subscription Status */}
        <section className="myperk-subscription">
          <h3>Subscription</h3>
          {subscription && (
            <div className="myperk-subscription-details">
              <div className="myperk-subscription-plan">
                {subscription.plan_code} Plan
              </div>
              <div className="myperk-subscription-status">
                {subscription.status}
              </div>
              {subscription.billing_period && (
                <div>
                  Billing: {subscription.billing_period}
                </div>
              )}
            </div>
          )}
          <button
            className="myperk-btn myperk-btn-primary myperk-btn-block"
            onClick={() => window.location.href = `/business/settings`}
          >
            Manage Subscription
          </button>
        </section>
      </div>
    </div>
  );
};

export default BusinessDashboardPage;