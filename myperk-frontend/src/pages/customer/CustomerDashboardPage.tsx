import React, { useEffect, useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { api } from '../../api/client';

const CustomerDashboardPage: React.FC = () => {
  const { userId } = useAuth();
  const [cards, setCards] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadCards = async () => {
      if (!userId) return;
      try {
        const response = await api.getCustomerCards();
        setCards(response.cards || []);
      } catch (err: any) {
        setError(err.message || 'Failed to load cards');
      } finally {
        setLoading(false);
      }
    };
    loadCards();
  }, [userId]);

  const totalStamps = cards.reduce((sum, card) => sum + card.stamp_count, 0);
  const activeCards = cards.filter(card => card.status === 'active').length;

  return (
    <div className="myperk-container myperk-customer-dashboard">
      <header className="myperk-dashboard-header">
        <h2>My Loyalty Dashboard</h2>
        <p>Welcome!</p>
      </header>

      {error && (
        <div className="myperk-alert myperk-alert-error">
          {error}
        </div>
      )}

      <div className="myperk-dashboard-stats">
        <div className="myperk-stat-card">
          <div className="myperk-stat-value">{activeCards}</div>
          <div className="myperk-stat-label">Active Cards</div>
        </div>
        <div className="myperk-stat-card">
          <div className="myperk-stat-value">{totalStamps}</div>
          <div className="myperk-stat-label">Total Stamps</div>
        </div>
      </div>

      <section className="myperk-cards-section">
        <h3>My Loyalty Cards</h3>

        {loading && (
          <p>Loading your cards...</p>
        )}

        {cards.length === 0 && (
          <p className="myperk-empty-state">
            No loyalty cards found. Scan a QR code to join a program.
          </p>
        )}

        <div className="myperk-cards-grid">
          {cards.map((card) => (
            <div key={card.id} className="myperk-card myperk-card-loyalty">
              <div className="myperk-card-header">
                <h4>{card.business_name || 'Loyalty Program'}</h4>
                <span className={`myperk-card-status ${card.status}`}>{card.status}</span>
              </div>
              <div className="myperk-card-body">
                <p className="myperk-card-business">
                  {card.slug ? `${card.business_name} (${card.slug})` : card.business_name || ''}
                </p>
                <div className="myperk-stamp-progress">
                  <div className="myperk-stamp-filled" style={{ width: `${(card.stamp_count / 10) * 100}%` }} />
                  <div className="myperk-stamp-empty" style={{ width: `${100 - (card.stamp_count / 10) * 100}%` }} />
                  <span className="myperk-stamp-count">
                    {card.stamp_count}/10 stamps
                  </span>
                </div>
              </div>
              <div className="myperk-card-footer">
                <button
                  className="myperk-btn myperk-btn-link myperk-btn-sm"
                  onClick={() => window.location.href = `/customer/cards/${card.business_id}`}
                >
                  View Details
                </button>
              </div>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
};

export default CustomerDashboardPage;