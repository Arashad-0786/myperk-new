<?php
/**
 * Business Owner Dashboard Template.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="myperk-shell">
  <div class="myperk-container">
    <h1>Business Dashboard</h1>
    
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
      <div class="myperk-card">
        <div class="myperk-card-header"><h3 class="myperk-card-title" style="font-size:1rem;">Total Active Members</h3></div>
        <div style="font-size:2.5rem; font-weight:700; color:var(--mp-primary);">1,234</div>
      </div>
      <div class="myperk-card">
        <div class="myperk-card-header"><h3 class="myperk-card-title" style="font-size:1rem;">Stamps Issued (30d)</h3></div>
        <div style="font-size:2.5rem; font-weight:700; color:var(--mp-success);">5,678</div>
      </div>
      <div class="myperk-card">
        <div class="myperk-card-header"><h3 class="myperk-card-title" style="font-size:1rem;">Rewards Redeemed</h3></div>
        <div style="font-size:2.5rem; font-weight:700; color:var(--mp-warning);">234</div>
      </div>
      <div class="myperk-card">
        <div class="myperk-card-header"><h3 class="myperk-card-title" style="font-size:1rem;">Subscription Status</h3></div>
        <div style="font-size:1.5rem; font-weight:700;">
          <span class="myperk-badge myperk-badge-active">Active (Growth Plan)</span>
        </div>
      </div>
    </div>

    <div class="myperk-card">
      <div class="myperk-card-header">
        <h2 class="myperk-card-title">Recent Activity</h2>
      </div>
      <div class="myperk-table-responsive">
        <table class="myperk-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Customer</th>
              <th>Branch</th>
              <th>Action</th>
              <th>Stamps</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>2026-11-07</td>
              <td>Rahul Sharma</td>
              <td>Connaught Place</td>
              <td>Stamp Approved</td>
              <td>+1</td>
            </tr>
            <tr>
              <td>2026-11-07</td>
              <td>Priya Mehta</td>
              <td>Cyber Hub</td>
              <td>Reward Redeemed</td>
              <td>-10</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>