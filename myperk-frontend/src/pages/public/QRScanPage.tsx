import React, { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { api } from '../../api/client';

const QRScanPage: React.FC = () => {
  const { userId, login } = useAuth();
  const [qrHash, setQrHash] = useState<string>('');
  const [isScanning, setIsScanning] = useState(false);
  const [scanResult, setScanResult] = useState<any | null>(null);
  const [showJoinModal, setShowJoinModal] = useState(false);

  // In a real implementation, this would use a Webcam/HMTL5 scanner
  // For now, we'll simulate QR scanning with predefined hashes or manual entry

  const simulateScan = async (hash: string) => {
    setQrHash(hash);
    setIsScanning(true);

    try {
      const result = await api.verifyQR(hash);
      setScanResult(result);

      if (result.valid && result.business_id) {
        // Auto-join the loyalty program after successful QR verification
        await api.customerJoin({
          business_id: result.business_id,
          branch_id: result.branch_id || 1,
          phone: '', // Will be prompted or fetched from user profile
          name: '', // Will be prompted or fetched from user profile
        });

        setShowJoinModal(true);
      } else {
        alert(result.message || 'Invalid QR code. Please try again.');
      }
    } catch (error: any) {
      console.error('QR verification failed:', error.message);
      alert(error.message || 'QR verification failed. Please try again.');
    } finally {
      setIsScanning(false);
    }
  };

  // Manual QR hash entry
  const handleManualEnter = async () => {
    if (!qrHash.trim()) return;
    await simulateScan(qrHash);
  };

  // After joining, show success modal
  const handleContinue = () => {
    setShowJoinModal(false);
    // Navigate to customer dashboard
    login('customer', userId);
  };

  return (
    <div className="myperk-container myperk-qr-page">
      <div className="myperk-qr-card">
        <h2>Scan QR Code</h2>

        {/* QR Code Display Area */}
        <div className="myperk-qr-display">
          <div 
            className={`myperk-qr-box ${isScanning ? 'myperk-qr-box-scanning' : 'myperk-qr-box-idle'}`}
            onClick={handleManualEnter}
          >
            {isScanning ? (
              <div className="myperk-qr-scanning">
                <span>Scanning...</span>
                <span className="myperk-qr-hash">{qrHash || '---'}</span>
              </div>
            ) : (
              <span className="myperk-qr-instruction">Scan a static QR code or enter hash below</span>
            )}
          </div>

          {/* QR Hash Input */}
          <div className="myperk-qr-input-group">
            <input
              type="text"
              value={qrHash}
              onChange={(e) => setQrHash(e.target.value)}
              placeholder="Enter QR hash manually..."
              className="myperk-qr-input"
              disabled={isScanning}
              autoFocus
            />
            <button
              className="myperk-btn myperk-btn-sm myperk-btn-primary"
              onClick={handleManualEnter}
              disabled={isScanning}
            >
              Verify
            </button>
          </div>
        </div>

        {/* Scan Result Preview */}
        {scanResult && (
          <div className="myperk-qr-result">
            <div className={scanResult.valid ? 'myperk-result-badge valid' : 'myperk-result-badge invalid'}>
              {scanResult.valid ? '✓ Valid QR Code' : '✗ Invalid QR Code'}
            </div>
            {scanResult.business_name && (
              <p><strong>Business:</strong> {scanResult.business_name}</p>
            )}
            {scanResult.branch_name && (
              <p><strong>Branch:</strong> {scanResult.branch_name}</p>
            )}
            {!scanResult.valid && (
              <p className="myperk-result-error">{scanResult.message || 'QR verification failed'}</p>
            )}
          </div>
        )}

        {/* Join Modal after successful scan */}
        {scanResult?.valid && showJoinModal && (
          <div className="myperk-modal">
            <div className="myperk-modal-content">
              <h3>Welcome to the Loyalty Program!</h3>
              <p>
                You've been successfully verified for <strong>{scanResult.business_name}</strong>.
              </p>
              <p>Your loyalty card has been created. You can now start collecting stamps.</p>
              <button 
                className="myperk-btn myperk-btn-primary myperk-btn-block"
                onClick={handleContinue}
              >
                Continue to Dashboard
              </button>
              <button 
                className="myperk-btn myperk-btn-outline myperk-btn-block"
                onClick={() => setShowJoinModal(false)}
              >
                Skip
              </button>
            </div>
          </div>
        )}

        {/* Scanning instruction when no result yet */}
        {!scanResult && !showJoinModal && !isScanning && (
          <p className="myperk-qr-instruction">
            Position your QR code within the frame and scan, or manually enter the QR hash.
          </p>
        )}
      </div>
    </div>
  );
};

export default QRScanPage;