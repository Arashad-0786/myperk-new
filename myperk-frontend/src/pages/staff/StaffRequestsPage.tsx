import React, { useEffect, useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { api } from '../../api/client';

const StaffRequestsPage: React.FC = () => {
  const { userId, businessId } = useAuth();
  const [requests, setRequests] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadRequests = async () => {
      if (!userId || !businessId) return;
      try {
        const response = await api.getPendingRequests(businessId);
        setRequests(response.requests || []);
      } catch (err: any) {
        setError(err.message || 'Failed to load requests');
      } finally {
        setLoading(false);
      }
    };
    loadRequests();
  }, [userId, businessId]);

  const handleApprove = async (requestId: number) => {
    if (!window.confirm('Approve this stamp request?')) return;
    try {
      await api.processStampRequest(requestId, 'approve');
      // Refresh requests after approval
      setRequests((prev) => prev.filter((r) => r.id !== requestId));
      alert('Request approved successfully!');
    } catch (err: any) {
      alert(err.message || 'Failed to process request');
    }
  };

  const handleReject = async (requestId: number) => {
    if (!window.confirm('Reject this stamp request?')) return;
    try {
      await api.processStampRequest(requestId, 'reject');
      setRequests((prev) => prev.filter((r) => r.id !== requestId));
      alert('Request rejected successfully!');
    } catch (err: any) {
      alert(err.message || 'Failed to process request');
    }
  };

  return (
    <div className="myperk-container myperk-staff-dashboard">
      <header className="myperk-dashboard-header">
        <h2>Pending Stamp Requests</h2>
        <p>Requests awaiting staff approval</p>
      </header>

      {error && (
        <div className="myperk-alert myperk-alert-error">
          {error}
        </div>
      )}

      <div className="myperk-requests-summary">
        <div className="myperk-stat-card">
          <div className="myperk-stat-value">{requests.length}</div>
          <div className="myperk-stat-label">Pending Requests</div>
        </div>
      </div>

      {loading && (
        <p>Loading requests...</p>
      )}

      {requests.length === 0 && (
        <p className="myperk-empty-state">
          No pending requests. All customers have been processed.
        </p>
      )}

      <section className="myperk-requests-list">
        {requests.map((request) => (
          <div key={request.id} className="myperk-request-card">
            <div className="myperk-request-header">
              <span className="myperk-request-customer">
                {request.customer_name || 'Customer'}
              </span>
              <span className="myperk-request-stamps">
                {request.requested_stamps} stamps requested
              </span>
            </div>

            <div className="myperk-request-details">
              <p><strong>Branch:</strong> {request.branch_name || 'All Branches'}</p>
              <p><strong>Customer Phone:</strong> {request.customer_phone || 'Not provided'}</p>
              <p className="myperk-request-notes">
                {request.notes || 'No notes'}
              </p>
            </div>

            <div className="myperk-request-actions">
              {request.status === 'pending' && (
                <div className="myperk-actions-group">
                  <button
                    className="myperk-btn myperk-btn-success myperk-btn-small"
                    onClick={() => handleApprove(request.id)}
                  >
                    Approve
                  </button>
                  <button
                    className="myperk-btn myperk-btn-danger myperk-btn-small"
                    onClick={() => handleReject(request.id)}
                  >
                    Reject
                  </button>
                </div>
              )}
              {request.status !== 'pending' && (
                <span className="myperk-request-status">
                  {request.status}
                </span>
              )}
            </div>
          </div>
        ))}
      </section>
    </div>
  );
};

export default StaffRequestsPage;