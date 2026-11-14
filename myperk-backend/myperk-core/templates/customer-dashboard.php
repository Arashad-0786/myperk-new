<?php
/**
 * Customer Dashboard & Stamp Card UI Template.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="myperk-shell">
  <div class="myperk-container">
    <div class="myperk-card">
      <div class="myperk-card-header">
        <h2 class="myperk-card-title">My Loyalty Cards</h2>
        <button class="myperk-btn myperk-btn-primary js-myperk-scan-qr">📷 Scan Shop QR</button>
      </div>
      
      <!-- Loyalty Card Sample -->
      <div class="myperk-card" style="border-left: 4px solid var(--mp-primary);">
        <div style="display:flex; justify-size:space-between; align-items:center;">
          <div>
            <h3 style="margin:0;">Urban Brew Coffee</h3>
            <span class="myperk-badge myperk-badge-active">Active Member</span>
          </div>
          <div style="text-align:right;">
            <span style="font-size:1.5rem; font-weight:bold; color:var(--mp-primary);">4 / 10</span>
            <div style="font-size:0.8rem; color:var(--mp-text-muted);">Stamps Collected</div>
          </div>
        </div>

        <div class="myperk-stamp-grid">
          <div class="myperk-stamp-slot active">✓</div>
          <div class="myperk-stamp-slot active">✓</div>
          <div class="myperk-stamp-slot active">✓</div>
          <div class="myperk-stamp-slot active">✓</div>
          <div class="myperk-stamp-slot">5</div>
          <div class="myperk-stamp-slot">6</div>
          <div class="myperk-stamp-slot">7</div>
          <div class="myperk-stamp-slot">8</div>
          <div class="myperk-stamp-slot">9</div>
          <div class="myperk-stamp-slot">10</div>
        </div>

        <p style="font-size:0.9rem; color:var(--mp-text-muted); margin:0.5rem 0 0 0;">
          🎁 Reward at 10 Stamps: <strong>1 Free Cappuccino or Latte</strong>
        </p>
      </div>
    </div>
  </div>
</div>
