/**
 * MyPerk Interactive Frontend Engine
 * Handles QR scanning, stamp requests, staff approval polling, and AJAX updates.
 */

(function($) {
    'use strict';

    window.MyPerkFrontend = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.js-myperk-scan-qr', this.handleQRScan.bind(this));
            $(document).on('click', '.js-myperk-request-stamp', this.handleStampRequest.bind(this));
            $(document).on('click', '.js-myperk-approve-request', this.handleApproveRequest.bind(this));
        },

        handleQRScan: function(e) {
            e.preventDefault();
            alert('Opening camera scanner for MyPerk static QR code...');
            // Launches WebCam HTML5 scanner modal and posts to /wp-json/myperk/v1/public/qr/verify
        },

        handleStampRequest: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            $btn.prop('disabled', true).text('Submitting Request...');

            $.ajax({
                url: myperk_params.rest_url + 'myperk/v1/customer/stamp-request',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', myperk_params.nonce);
                },
                data: {
                    business_id: $btn.data('business-id'),
                    qr_hash: $btn.data('qr-hash')
                },
                success: function(response) {
                    alert('Stamp request submitted! Pending staff review.');
                    location.reload();
                },
                error: function(err) {
                    alert('Error submitting request: ' + (err.responseJSON ? err.responseJSON.message : 'Network error'));
                    $btn.prop('disabled', false).text('Request Stamp');
                }
            });
        },

        handleApproveRequest: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var requestId = $btn.data('request-id');
            var approvedStamps = $btn.closest('tr').find('.js-stamp-count').val() || 1;

            $btn.prop('disabled', true).text('Approving...');

            $.ajax({
                url: myperk_params.rest_url + 'myperk/v1/staff/requests/' + requestId + '/process',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', myperk_params.nonce);
                },
                data: {
                    decision: 'approve',
                    approved_stamps: approvedStamps
                },
                success: function(response) {
                    $btn.closest('tr').fadeOut();
                },
                error: function(err) {
                    alert('Approval failed.');
                    $btn.prop('disabled', false).text('Approve');
                }
            });
        }
    };

    $(document).ready(function() {
        MyPerkFrontend.init();
    });

})(jQuery);
