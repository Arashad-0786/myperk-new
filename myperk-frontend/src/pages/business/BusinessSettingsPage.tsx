import React, { useEffect, useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { api } from '../../api/client';

interface FormData {
  business_name: string;
  contact_email: string;
  contact_phone: string;
  address: string;
  timezone: string;
  currency: string;
  brand_color: string;
}

const BusinessSettingsPage: React.FC = () => {
  const { businessId } = useAuth();
  const [business, setBusiness] = useState<any | null>(null);
  const [branches, setBranches] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState<FormData>({
    business_name: '',
    contact_email: '',
    contact_phone: '',
    address: '',
    timezone: 'Asia/Kolkata',
    currency: 'INR',
    brand_color: '#4F46E5',
  });

  useEffect(() => {
    const loadData = async () => {
      if (!businessId) return;
      try {
        const response = await api.getBusinessSettings(businessId);
        setBusiness(response.business);
        setBranches(response.branches || []);
        setForm({
          business_name: response.business?.business_name || '',
          contact_email: response.business?.contact_email || '',
          contact_phone: response.business?.contact_phone || '',
          address: response.business?.address || '',
          timezone: response.business?.timezone || 'Asia/Kolkata',
          currency: response.business?.currency || 'INR',
          brand_color: response.business?.brand_color || '#4F46E5',
        });
      } catch (err: any) {
        setError(err.message || 'Failed to load settings');
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, [businessId]);

  const handleSave = async () => {
    if (!businessId || !form.business_name) return;
    try {
      const result = await api.updateBusinessSettings(businessId, form);
      if (result.success) {
        alert('Business settings updated successfully!');
        setBusiness((prev: any) => prev ? { ...prev, ...form } : null);
      }
    } catch (err: any) {
      alert(err.message || 'Failed to update settings');
    }
  };

  return (
    <div className="myperk-container myperk-business-settings">
      <header className="myperk-dashboard-header">
        <h2>Business Settings</h2>
        <p>Configure your business profile</p>
      </header>

      {error && (
        <div className="myperk-alert myperk-alert-error">
          {error}
        </div>
      )}

      {loading && (
        <div className="myperk-container">
          <p>Loading settings...</p>
        </div>
      )}

      {business && !loading && (
        <form className="myperk-settings-form" onSubmit={(e: React.FormEvent) => {
          e.preventDefault();
          handleSave();
        }}>
          <div className="myperk-form-section">
            <h3>Business Profile</h3>
            <div className="myperk-form-group">
              <label>Business Name</label>
              <input
                type="text"
                value={form.business_name}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                  setForm((prev: FormData) => ({ ...prev, business_name: e.target.value }))
                }
                required
                className="myperk-input"
                placeholder="e.g. Cafe Delight"
              />
            </div>
            <div className="myperk-form-group">
              <label>Contact Email</label>
              <input
                type="email"
                value={form.contact_email}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                  setForm((prev: FormData) => ({ ...prev, contact_email: e.target.value }))
                }
                className="myperk-input"
                placeholder="contact@business.com"
              />
            </div>
            <div className="myperk-form-group">
              <label>Contact Phone</label>
              <input
                type="tel"
                value={form.contact_phone}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                  setForm((prev: FormData) => ({ ...prev, contact_phone: e.target.value }))
                }
                className="myperk-input"
                placeholder="+91 98765 43210"
              />
            </div>
            <div className="myperk-form-group">
              <label>Address</label>
              <textarea
                value={form.address}
                onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => 
                  setForm((prev: FormData) => ({ ...prev, address: e.target.value }))
                }
                className="myperk-textarea"
                rows={3}
                placeholder="Business address"
              >{form.address}</textarea>
            </div>
            <div className="myperk-form-group">
              <label>Timezone</label>
              <select
                value={form.timezone}
                onChange={(e: React.ChangeEvent<HTMLSelectElement>) => 
                  setForm((prev: FormData) => ({ ...prev, timezone: e.target.value }))
                }
                className="myperk-select"
              >
                <option value="Asia/Kolkata">Asia/Kolkata (IST)</option>
                <option value="UTC">UTC</option>
                <option value="America/New_York">America/New York</option>
                <option value="Europe/London">Europe/London</option>
              </select>
            </div>
            <div className="myperk-form-group">
              <label>Currency</label>
              <select
                value={form.currency}
                onChange={(e: React.ChangeEvent<HTMLSelectElement>) => 
                  setForm((prev: FormData) => ({ ...prev, currency: e.target.value }))
                }
                className="myperk-select"
              >
                <option value="INR">INR (Indian Rupee)</option>
                <option value="USD">USD (US Dollar)</option>
                <option value="EUR">EUR (Euro)</option>
              </select>
            </div>
            <div className="myperk-form-group">
              <label>Brand Color</label>
              <input
                type="color"
                value={form.brand_color}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                  setForm((prev: FormData) => ({ ...prev, brand_color: e.target.value }))
                }
                className="myperk-color-input"
              />
              <span 
                className="myperk-color-preview" 
                style={{ backgroundColor: form.brand_color } as React.CSSProperties}
              ></span>
              <small>#RRGGBB format</small>
            </div>
          </div>

          {/* Branches Section */}
          <div className="myperk-form-section">
            <h3>Branch Management</h3>
            {branches.length > 0 ? (
              <div className="myperk-branches-list">
                {branches.map((branch: any) => (
                  <div key={branch.id} className="myperk-branch-item">
                    <span>{branch.branch_name} ({branch.branch_code})</span>
                    <button
                      className="myperk-btn myperk-btn-link myperk-btn-xs"
                      onClick={() => window.alert(`Edit branch: ${branch.branch_name}`)}
                    >
                      Edit
                    </button>
                  </div>
                ))}
              </div>
            ) : (
              <p>No branches yet. <a href="/business/dashboard" style={{ color: '#4F46E5' }}>Add first branch</a></p>
            )}

            <button
              className="myperk-btn myperk-btn-primary myperk-btn-sm myperk-btn-add-branch"
              onClick={() => window.location.href = `/business/dashboard`}
            >
              Add Branch
            </button>
          </div>

          <button type="submit" className="myperk-btn myperk-btn-primary myperk-btn-block">
            Save Changes
          </button>
        </form>
      )}
    </div>
  );
};

export default BusinessSettingsPage;