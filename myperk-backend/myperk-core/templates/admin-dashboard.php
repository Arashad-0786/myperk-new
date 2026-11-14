<?php
/**
 * Platform Admin Dashboard Template.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="myperk-shell">
  <div class="myperk-container">
    <h1>Platform Admin</h1>
    
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
      <div class="myperk-card">
        <div class="myperk-card-header"><h3 class="myperk-card-title" style="font-size:1rem;">Total Businesses</h3></div>
        <div style="font-size:2.5rem; font-weight:700; color:var(--mp-primary);">89</div>
      </div>
      <div class="myperk-card">
        <div class="myperk-card-header"><h3 class="myperk-card-title" style="font-size:1rem;">Active Subscriptions</h3></div>
        <div style="font-size:2.5rem; font-weight:700; color:var(--mp-success);">67</div>
      </div>
      <div class="myperk-card">
        <div class="myperk-card-header"><h3 class="myperk-card-title" style="font-size:1rem;">Trial / Grace</h3></div>
        <div style="font-size:2.5rem; font-weight:700; color:var(--mp-warning);">22</div>
      </div>
      <div class="myperk-card">
        <div class="myperk-card-header"><h3 class="myperk-card-title" style="font-size:1rem;">Total Stamps Issued</h3></div>
        <div style="font-size:2.5rem; font-weight:700;">124,567</div>
      </div>
    </div>

    <div class="myperk-card">
      <div class="myperk-card-header">
        <h2 class="myperk-card-title">Business Management</h2>
      </div>
      <div class="myperk-table-responsive">
        <table class="myperk-table">
          <thead>
            <tr>
              <th>Business</th>
              <th>Owner</th>
              <th>Plan</th>
              <th>Status</th>
              <th>Members</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Urban Brew Coffee</td>
              <td>Rajesh Kumar</td>
              <td>Growth (Yearly)</td>
              <td><span class="myperk-badge myperk-badge-active">Active</span></td>
              <td>1,234</td>
              <td>
                <button class="myperk-btn myperk-btn-primary" style="margin-right:0.5rem;">View</button>
                <button class="myperk-btn" style="background:#F3F4F6;">Settings</button>
              </td>
            </tr>
            <tr>
              <td>The Green Kitchen</td>
              <td>Anita Desai</td>
              <td>Starter (Monthly)</td>
              <td><span class="myperk-badge myperk-badge-pending">Trialing</span></td>
              <td>234</td>
              <td>
                <button class="myperk-btn myperk-btn-primary" style="margin-right:0.5rem;">View</button>
                <button class="myperk-btn" style="background:#F3F4F6;">Settings</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>