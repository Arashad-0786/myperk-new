import React, { useEffect, useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { api } from '../../api/client';

const BusinessListPage: React.FC = () => {
  const { userId } = useAuth();
  const [businesses, setBusinesses] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);

  useEffect(() => {
    const loadBusinesses = async () => {
      if (!userId) return;
      try {
        const response = await api.getAllBusinesses(page);
        setBusinesses(response.businesses || []);
        setPage(response.page || 1);
      } catch (err: any) {
        setError(err.message || 'Failed to load businesses');
      } finally {
        setLoading(false);
      }
    };
    loadBusinesses();
  }, [userId, page]);

  return (
    <div className="myperk-container myperk-admin-businesses">
      <header className="myperk-dashboard-header">
        <h2>All Businesses</h2>
        <p>Manage platform businesses</p>
      </header>

      {error && (
        <div className="myperk-alert myperk-alert-error">
          {error}
        </div>
      )}

      <div className="myperk-admin-table">
        {loading && (
          <p>Loading businesses...</p>
        )}

        {businesses.length === 0 && (
          <p>No businesses found.</p>
        )}

        <div className="myperk-table-container">
          <table className="myperk-table">
            <thead>
              <tr>
                <th>Business Name</th>
                <th>Slug</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Members</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {businesses.map((business) => (
                <tr key={business.id} className="myperk-table-row">
                  <td>
                    <div>{business.business_name}</div>
                    <div className="myperk-table-sub">{business.slug}</div>
                  </td>
                  <td>{business.plan_code}</td>
                  <td>
                    <span className={business.sub_status === 'active' ? 'myperk-badge myperk-badge-success' : 'myperk-badge myperk-badge-warning'}>
                      {business.sub_status}
                    </span>
                  </td>
                  <td>{business.member_count}</td>
                  <td>
                    <button
                      className="myperk-btn myperk-btn-link myperk-btn-xs"
                      onClick={() => window.alert(`View details for ${business.business_name}`)}
                    >
                      View
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="myperk-pagination">
          <button
            className="myperk-btn myperk-btn-link"
            onClick={() => setPage((prev) => Math.max(1, prev - 1))}
          >
            Prev
          </button>
          <span>Page {page}</span>
          <button
            className="myperk-btn myperk-btn-link"
            onClick={() => setPage(prev => prev + 1)}
          >
            Next
          </button>
        </div>
      </div>
    </div>
  );
};

export default BusinessListPage;