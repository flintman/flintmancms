/**
 * FlintmanCMS AtlasCMMS Plugin — Work Order Table Search
 *
 * Cross-page live search using data-search attributes set server-side.
 * All WO rows are rendered in the DOM (non-current-page rows hidden).
 * Searching reveals matching rows across all pages and hides pagination.
 * Called from atlascmms.js via initializeWorkOrderSearch().
 */

function initializeWorkOrderSearch() {
    var searchInput = document.getElementById('wo-search');
    if (!searchInput) return;

    var currentPage = parseInt(searchInput.dataset.currentPage || '0', 10);
    var pagination  = document.getElementById('wo-pagination');

    searchInput.addEventListener('input', function() {
        var query = this.value.toLowerCase().trim();
        var rows  = document.querySelectorAll('.workorders-table tbody tr');
        var visibleCount = 0;

        rows.forEach(function(row) {
            var match;
            if (query === '') {
                // Restore page-based visibility when search is cleared
                match = parseInt(row.dataset.page || '0', 10) === currentPage;
            } else {
                // Search all rows using the pre-built server-side data-search value
                var text = row.dataset.search || row.textContent.toLowerCase();
                match = text.indexOf(query) !== -1;
            }
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        // Hide pagination links while actively searching across all pages
        if (pagination) {
            pagination.style.display = query ? 'none' : '';
        }

        var noResults = document.getElementById('wo-no-results');
        if (noResults) {
            noResults.style.display = (query && visibleCount === 0) ? '' : 'none';
        }
    });
}

function initializeAssetSearch() {
    var searchInput = document.getElementById('asset-search');
    if (!searchInput) return;

    var currentPage = parseInt(searchInput.dataset.currentPage || '0', 10);
    var pagination  = document.getElementById('asset-pagination');

    searchInput.addEventListener('input', function() {
        var query = this.value.toLowerCase().trim();
        var rows  = document.querySelectorAll('.assets-table tbody tr');
        var visibleCount = 0;

        rows.forEach(function(row) {
            var match;
            if (query === '') {
                if (row.classList.contains('asset-child-row')) {
                    match = false; // always hide children when not searching
                } else {
                    match = parseInt(row.dataset.page || '0', 10) === currentPage;
                }
            } else {
                var text = row.dataset.search || row.textContent.toLowerCase();
                match = text.indexOf(query) !== -1;
            }
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        if (pagination) {
            pagination.style.display = query ? 'none' : '';
        }

        var noResults = document.getElementById('asset-no-results');
        if (noResults) {
            noResults.style.display = (query && visibleCount === 0) ? '' : 'none';
        }
    });
}
