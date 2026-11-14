import React, { useEffect, useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { api } from '../../api/client';

const CustomerCardsPage: React.FC = () => {
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

  return (
    <div className="myperk-container myperk-customer-dashboard">
      <header className="myperk-dashboard-header">
        <h2>My Loyalty Cards</h2>
        <p>Your active loyalty cards</p>
      </header>

      {error && (
        <div className="myperk-alert myperk-alert-error">
          {error}
        </div>
      )}

      <div className="myperk-cards-section">
        {loading && (
          <p>Loading your cards...</p>
        )}

        {cards.length === 0 && (
          <p className="myperk-empty-state">
            No loyalty cards found. <br />
            Scan a QR code to join a loyalty program.
          </p>
        )}

        <div className="myperk-cards-grid">
          {cards.map((card) => (
            <div key={card.id} className="myperk-card myperk-card-loyalty">
              <div className="myperk-card-header">
                <h4>{card.business_name || 'Loyalty Program'}</h4>
                <span className="myperk-card-status">{card.status}</span>
              </div>
              <div className="myperk-card-body">
                <p className="myperk-card-business">
                  Branch: {card.branch_name || 'All Branches'}
                </p>
                <div className="myperk-stamp-progress">
                  <div className="myperk-stamp-filled" 
                       style={{ width: `${(card.stamp_count / 12) * 100}%` }} />
                  <div className="myperk-stamp-empty" 
                       style={{ width: `${100 - (card.stamp_count / 12) * 100}%` }} />
                  <span className="myperk-stamp-count">
                    {card.stamp_count}/12 stamps collected
                  </span>
                </div>
                <p className="myperk-card-rewards">
                  Rewards redeemed: {card.rewards_redeemed_count}
                </p>
              </div>
              <div className="myperk-card-footer">
                <button
                  className="myperk-btn myperk-btn-link myperk-btn-sm"
                  onClick={() => window.location.href = `/customer/dashboard`}
                >
                  Back to Dashboard
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default CustomerCardsPage;