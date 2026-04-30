/**
 * Listing Manager Pro - Main JavaScript
 */
(function($) {
    'use strict';

    function removeCardFromDom(id) {
        ['.mlf-elementor-card', '.mlf-listing-card', '.mlf-user-card'].forEach(function(selector) {
            $(selector + '[data-id="' + id + '"]').remove();
        });
    }
    
    // Global function for opening detail modal
    window.mlfOpenDetail = function(id) {
        var modal = document.getElementById('mlf-detail-modal');
        var body = document.getElementById('mlf-modal-body');
        var title = document.getElementById('mlf-modal-title');
        
        if (!modal || !body || !title) {
            console.error('MLF: Modal elements not found');
            return;
        }
        
        body.innerHTML = '<div class="mlf-loading"><div class="mlf-spinner"></div></div>';
        modal.classList.add('active');
        
        // Fetch listing data via AJAX
        $.ajax({
            url: mlf_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'mlf_get_detail',
                id: id,
                nonce: mlf_vars.nonce
            },
            success: function(response) {
                if(response.success) {
                    var data = response.data;
                    title.textContent = data.title;
                    body.innerHTML = data.detail_html || '<div class="mlf-error">No detail content available.</div>';
                } else {
                    body.innerHTML = '<div class="mlf-error">Error: ' + response.data + '</div>';
                }
            },
            error: function() {
                body.innerHTML = '<div class="mlf-error">Failed to load listing details</div>';
            }
        });
    };
    
    // Close modal function
    window.mlfCloseModal = function() {
        var modal = document.getElementById('mlf-detail-modal');
        if(modal) {
            modal.classList.remove('active');
        }
    };
    
    // Close modal on overlay click
    $(document).on('click', '#mlf-detail-modal', function(e) {
        if(e.target === this) {
            mlfCloseModal();
        }
    });
    
    // Close modal on Escape key
    $(document).on('keydown', function(e) {
        if(e.key === 'Escape') {
            mlfCloseModal();
        }
    });
    
    // Delegate click events for dynamically added cards
    $(document).on('click', '.mlf-elementor-card, .mlf-listing-card, .mlf-user-card', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if(id) {
            mlfOpenDetail(id);
        }
    });
    
    // Card action buttons (approve, reject, delete)
    window.mlfCardAction = function(id, type) {
        if(!confirm('Are you sure you want to ' + type + ' this listing?')) return;
        
        $.ajax({
            url: mlf_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'mlf_action',
                id: id,
                type: type,
                nonce: mlf_vars.nonce
            },
            success: function(response) {
                if(response.success) {
                    if (type === 'trash') {
                        removeCardFromDom(id);
                        mlfCloseModal();
                        return;
                    }

                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Failed to perform action');
            }
        });
    };
    
})(jQuery);