<?php
/**
 * Staff Approval Dashboard Template.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="myperk-shell">
  <div class="myperk-container">
    <h1>Staff Dashboard</h1>
    <div class="myperk-card">
      <div class="myperk-card-header">
        <h2 class="myperk-card-title">Pending Stamp Requests</h2>
      </div>

      <div class="myperk-table-responsive">
        <table class="myperk-table">
          <thead>
            <tr>
              <th>Customer</th>
              <th>Branch</th>
              <th>Requested Stamps</th>
              <th>Approve</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Rahul Sharma</td>
              <td>Urban Brew - Connaught Place</td>
              <td>
                <input type="number" class="js-stamp-count" min="1" max="5" value="1" style="width:60px;">
              </td>
              <td>1 Stamp</td>
              <td>
                <button class="myperk-btn myperk-btn-success js-myperk-approve-request" data-request-id="123">Approve</button>
                <button class="myperk-btn myperk-btn-danger" style="margin-left:0.5rem;">Decline</button>
              </td>
            </tr>
            <tr>
              <td>Priya Mehta</td>
              <td>Urban Brew - Cyber Hub</td>
              <td>
                <input type="number" class="js-stamp-count" min="1" max="5" value="2" style="width:60px;">
              </td>
              <td>2 Stamps</td>
              <td>
                <button class="myperk-btn myperk-btn-success js-myperk-approve-request" data-request-id="124">Approve</button>
                <button class="myperk-btn myperk-btn-danger" style="margin-left:0.5rem;">Decline</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>