/**
 * FlintmanCMS AtlasCMMS Plugin — Core JS (v18)
 * Entry point; utility functions; simple UI handlers.
 * Billing modal: atlascmms-billing.js
 * WO search:     atlascmms-search.js
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeFilterHandlers();
    initializeImageLazyLoad();
    initializeFormHandlers();
    initializeWorkOrderSearch();
    initializeAssetSearch();
    initializeAssetExpand();
    initializeAssetRowClick();
    initializeAssetDetailTabs();
    initializeBillable();
});

function initializeFilterHandlers() {
    var statusSelect = document.querySelector('select[name="status"]');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            if (this.form) { this.form.submit(); }
        });
    }
}

function initializeFormHandlers() {
    var apiKeyInput = document.getElementById('api_key_input');
    if (apiKeyInput) {
        var counter   = document.getElementById('api_key_counter');
        var maxLength = 500;
        apiKeyInput.addEventListener('input', function() {
            var currentLength = this.value.length;
            if (counter) {
                counter.textContent = currentLength;
                counter.style.color = currentLength >= maxLength ? 'red'
                                    : (currentLength >= maxLength * 0.9 ? 'orange' : 'inherit');
            }
        });
        apiKeyInput.dispatchEvent(new Event('input'));
    }
    var form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            var input = document.getElementById('api_key_input');
            if (input && input.value.length > 500) {
                e.preventDefault();
                alert('API Key/Credentials exceeds 500 character limit. Current length: ' + input.value.length);
                return false;
            }
        });
    }
}

function initializeImageLazyLoad() {
    if (!('IntersectionObserver' in window)) return;
    var images = document.querySelectorAll('img.asset-image');
    if (images.length === 0) return;
    var observer = new IntersectionObserver(function(entries, obs) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var img = entry.target;
                if (img && img.dataset && img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
                obs.unobserve(img);
            }
        });
    });
    images.forEach(function(img) { if (img) observer.observe(img); });
}

function formatDate(dateString) {
    if (!dateString) return '';
    var date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function showLoading() {
    var loader = document.createElement('div');
    loader.className = 'loading-spinner';
    loader.innerHTML = '<p>Loading...</p>';
    document.body.appendChild(loader);
}

function hideLoading() {
    var loader = document.querySelector('.loading-spinner');
    if (loader) loader.remove();
}

function initializeAssetExpand() {
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.asset-expand-btn');
        if (!btn) return;
        var assetId   = btn.dataset.assetId;
        var children  = document.querySelectorAll('.asset-child-row[data-parent-id="' + assetId + '"]');
        var expanded  = btn.classList.contains('expanded');
        children.forEach(function(row) {
            row.style.display = expanded ? 'none' : '';
        });
        btn.classList.toggle('expanded', !expanded);
        btn.innerHTML = expanded ? '&#9654;' : '&#9660;';
    });
}

function initializeAssetRowClick() {
    document.addEventListener('click', function(e) {
        // Ignore clicks on links, buttons, or the expand column
        if (e.target.closest('a, button, .asset-expand-col')) return;
        var row = e.target.closest('tr.asset-row-link');
        if (!row || !row.dataset.href) return;
        window.location.href = row.dataset.href;
    });
}

function initializeAssetDetailTabs() {
    var tabContainer = document.getElementById('asset-detail-tabs');
    if (!tabContainer) return;
    tabContainer.addEventListener('click', function(e) {
        var btn = e.target.closest('.asset-tab-btn');
        if (!btn) return;
        var targetId = btn.dataset.tab;
        // Deactivate all buttons and hide all panels
        tabContainer.querySelectorAll('.asset-tab-btn').forEach(function(b) {
            b.classList.remove('asset-tab-active');
        });
        document.querySelectorAll('.asset-tab-panel').forEach(function(p) {
            p.style.display = 'none';
        });
        // Activate clicked button and show target panel
        btn.classList.add('asset-tab-active');
        var panel = document.getElementById(targetId);
        if (panel) panel.style.display = '';
    });
}

function showSuccess(message) {
    var alert = document.createElement('div');
    alert.className = 'alert alert-success';
    alert.innerHTML = message;
    document.body.insertBefore(alert, document.body.firstChild);
    setTimeout(function() { alert.remove(); }, 5000);
}

function showError(message) {
    var alert = document.createElement('div');
    alert.className = 'alert alert-danger';
    alert.innerHTML = message;
    document.body.insertBefore(alert, document.body.firstChild);
}
