/**
 * FlintmanCMS AtlasCMMS Plugin — Billing / Invoice Modal
 *
 * Handles invoice generation, inline editing, and print/PDF export.
 * Called from atlascmms.js via initializeBillable().
 */

/* =========================================================================
   ENTRY POINT
   ========================================================================= */

function initializeBillable() {
    var btn = document.getElementById('wo-billable-btn');
    if (!btn) return;
    btn.addEventListener('click', openBillingModal);
}

function openBillingModal() {
    var existing = document.getElementById('billing-modal-overlay');
    if (existing) existing.remove();

    var data = window.atlasBillingData;
    if (!data) return;

    var overlay = document.createElement('div');
    overlay.id        = 'billing-modal-overlay';
    overlay.className = 'billing-overlay';
    overlay.innerHTML = buildBillingModalHTML(data);
    document.body.appendChild(overlay);
    document.body.classList.add('billing-modal-open');

    document.getElementById('billing-close').addEventListener('click', closeBillingModal);
    overlay.addEventListener('click', function(e) { if (e.target === overlay) closeBillingModal(); });
    document.getElementById('billing-print-btn').addEventListener('click', printInvoice);

    var photosCheck = document.getElementById('billing-photos-check');
    if (photosCheck) {
        photosCheck.addEventListener('change', function() {
            var pp = document.getElementById('billing-photos-page');
            if (pp) pp.classList.toggle('photos-enabled', this.checked);
        });
    }

    document.getElementById('billing-add-labor').addEventListener('click', addLaborRow);
    document.getElementById('billing-add-parts').addEventListener('click', addPartsRow);

    document.getElementById('billing-labor-tbody').addEventListener('click', function(e) {
        if (e.target.classList.contains('billing-labor-remove')) {
            e.target.closest('tr').remove();
            recalcTotals();
        }
    });

    document.getElementById('billing-parts-tbody').addEventListener('click', function(e) {
        if (e.target.classList.contains('billing-labor-remove')) {
            e.target.closest('tr').remove();
            recalcTotals();
        }
    });

    document.getElementById('billing-modal').addEventListener('input', recalcTotals);
    recalcTotals();
}

function closeBillingModal() {
    var overlay = document.getElementById('billing-modal-overlay');
    if (overlay) overlay.remove();
    document.body.classList.remove('billing-modal-open');
}

/* =========================================================================
   ESCAPE HELPERS
   ========================================================================= */

function billEsc(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function billEscTA(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

/* =========================================================================
   MODAL HTML BUILDER
   ========================================================================= */

function buildBillingModalHTML(d) {
    var today = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    var co    = d.company || {};

    // Build from-block address; split on first comma when city/state/zip are absent
    var coStreet   = co.address || '';
    var coCityLine = [co.city, co.state].filter(Boolean).join(', ') + (co.zipCode ? '  ' + co.zipCode : '');
    if (!coCityLine && coStreet.indexOf(',') !== -1) {
        var firstComma = coStreet.indexOf(',');
        coCityLine = coStreet.substring(firstComma + 1).trim();
        coStreet   = coStreet.substring(0, firstComma).trim();
    }

    // Photos page (separate print page, toggled by checkbox)
    var photosPageHtml = '';
    if (d.photos && d.photos.length > 0) {
        var photoItems = '';
        d.photos.forEach(function(p) {
            photoItems +=
                '<div class="billing-photo-item">' +
                '<img src="' + billEsc(p.url) + '" alt="' + billEsc(p.name) + '" loading="lazy">' +
                (p.name ? '<div class="billing-photo-caption">' + billEsc(p.name) + '</div>' : '') +
                '</div>';
        });
        photosPageHtml =
            '<div class="billing-photos-page" id="billing-photos-page">' +
            '<div class="billing-photos-page-title">Work Order Photos</div>' +
            '<div class="billing-photos-grid">' + photoItems + '</div>' +
            '</div>';
    }

    // Pre-populate parts rows (name + description merged)
    var partsRows = '';
    (d.parts || []).forEach(function(p) {
        var label = p.name + (p.description ? ' \u2014 ' + p.description : '');
        partsRows +=
            '<tr>' +
            '<td colspan="2"><input class="bill-input" value="' + billEsc(label) + '"></td>' +
            '<td><input class="bill-input bill-num billing-part-qty"  type="number" min="0" step="0.01" value="' + billEsc(String(p.qty))  + '"></td>' +
            '<td><input class="bill-input bill-num billing-part-cost" type="number" min="0" step="0.01" value="' + billEsc(String(p.cost)) + '"></td>' +
            '<td class="bill-num billing-part-total">$0.00</td>' +
            '<td class="no-print-billing"><button class="billing-labor-remove" title="Remove">&#x2715;</button></td>' +
            '</tr>';
    });

    // WO reference rows
    var woRefRows = d.asset
        ? '<tr><td>Asset</td><td>' + billEsc(d.asset) + '</td></tr>'
        : '<tr><td>Asset</td><td><input class="bill-input" placeholder="Asset name"></td></tr>';
    if (d.completedOn) {
        woRefRows += '<tr><td>Completed</td><td>' + billEsc(d.completedOn) + '</td></tr>';
    }

    var descText = d.description || '';
    if (d.feedback) descText += (descText ? '\n' : '') + d.feedback;

    return (
        '<div id="billing-modal" class="billing-modal">' +

        // ── Toolbar ──
        '<div class="billing-toolbar no-print-billing">' +
        '<button class="billing-toolbar-btn" id="billing-close">&#x2715; Close</button>' +
        '<span class="billing-toolbar-title">Invoice \u2014 ' + billEsc(d.woNumber) + '</span>' +
        (d.photos && d.photos.length > 0
            ? '<label class="billing-photos-label"><input type="checkbox" id="billing-photos-check"> Include photos (' + d.photos.length + ')</label>'
            : '') +
        '<button class="billing-toolbar-btn billing-toolbar-print" id="billing-print-btn">&#128438; Print / Save PDF</button>' +
        '</div>' +

        // ── Invoice paper ──
        '<div class="billing-invoice" id="billing-invoice">' +

        // Row 1: From + Invoice meta
        '<div class="billing-header">' +
        '<div class="billing-from">' +
        '<input class="bill-input bill-input-company" placeholder="Your Company Name" value="' + billEsc(co.name || '') + '">' +
        '<input class="bill-input bill-input-sm" placeholder="Street Address"       value="' + billEsc(coStreet)   + '">' +
        '<input class="bill-input bill-input-sm" placeholder="City, State  ZIP"     value="' + billEsc(coCityLine) + '">' +
        '<input class="bill-input bill-input-sm" placeholder="Phone / Email"        value="' + billEsc([co.phone, co.email].filter(Boolean).join(' / ')) + '">' +
        '</div>' +
        '<div class="billing-meta-block">' +
        '<div class="billing-invoice-title">INVOICE</div>' +
        '<table class="billing-meta-table">' +
        '<tr><td>Invoice #</td><td><input class="bill-input" value="' + billEsc(d.woNumber) + '"></td></tr>' +
        '<tr><td>Date</td><td><input class="bill-input" value="'      + billEsc(today)       + '"></td></tr>' +
        '<tr><td>Due</td><td><input class="bill-input" value="Upon Receipt"></td></tr>' +
        (d.category ? '<tr><td>Category</td><td>' + billEsc(d.category) + '</td></tr>' : '') +
        '</table>' +
        '</div>' +
        '</div>' + // billing-header

        // Row 2: Bill To | WO Details
        '<div class="billing-addr-row">' +
        '<div class="billing-bill-to">' +
        '<div class="billing-section-label">Bill To</div>' +
        '<input class="bill-input bill-input-company" placeholder="Customer / Company Name">' +
        '<input class="bill-input bill-input-sm" placeholder="Address">' +
        '<input class="bill-input bill-input-sm" placeholder="City, State  ZIP">' +
        '</div>' +
        '<div class="billing-wo-ref">' +
        '<div class="billing-section-label">Work Order Details</div>' +
        '<table class="billing-ref-table">' +
        '<tr><td>WO #</td><td>' + billEsc(d.woNumber) + '</td></tr>' +
        woRefRows +
        '</table>' +
        '</div>' +
        '</div>' + // billing-addr-row

        // Description (only when present)
        (descText
            ? '<div class="billing-desc-row">' +
              '<div class="billing-section-label">Description of Work</div>' +
              '<textarea class="bill-textarea bill-textarea-sm">' + billEscTA(descText) + '</textarea>' +
              '</div>'
            : '') +

        // Services & Parts table
        '<div class="billing-section-label billing-items-label">Services &amp; Parts</div>' +
        '<table class="billing-table">' +
        '<thead><tr>' +
        '<th colspan="2">Description</th>' +
        '<th class="bill-num">Qty\u00a0/\u00a0Hrs</th>' +
        '<th class="bill-num">Rate\u00a0/\u00a0Cost</th>' +
        '<th class="bill-num">Total</th>' +
        '<th class="no-print-billing" style="width:28px"></th>' +
        '</tr></thead>' +

        '<tbody><tr class="billing-group-row"><td colspan="6">&#9656; Labor</td></tr></tbody>' +
        '<tbody id="billing-labor-tbody">' +
        '<tr>' +
        '<td colspan="2"><input class="bill-input billing-labor-desc" value="' + billEsc(d.title || 'Labor') + '"></td>' +
        '<td><input class="bill-input bill-num billing-labor-hrs"  type="number" min="0" step="0.25"  value="' + billEsc(String(d.estimatedDuration || 0)) + '"></td>' +
        '<td><input class="bill-input bill-num billing-labor-rate" type="number" min="0" step="0.01" value="0.00"></td>' +
        '<td class="bill-num billing-labor-total">$0.00</td>' +
        '<td class="no-print-billing"><button class="billing-labor-remove" title="Remove">&#x2715;</button></td>' +
        '</tr>' +
        '</tbody>' +

        '<tbody><tr class="billing-group-row"><td colspan="6">&#9656; Parts &amp; Materials</td></tr></tbody>' +
        '<tbody id="billing-parts-tbody">' + partsRows + '</tbody>' +

        '<tfoot>' +
        '<tr class="billing-subtotal-row"><td colspan="4" class="bill-subtotal-label">Labor Subtotal</td><td class="bill-num" id="billing-labor-subtotal">$0.00</td><td class="no-print-billing"></td></tr>' +
        '<tr class="billing-subtotal-row"><td colspan="4" class="bill-subtotal-label">Parts Subtotal</td><td class="bill-num" id="billing-parts-subtotal">$0.00</td><td class="no-print-billing"></td></tr>' +
        '</tfoot>' +
        '</table>' +

        '<div class="billing-add-btns no-print-billing">' +
        '<button class="billing-add-row" id="billing-add-labor">+ Add Labor</button>' +
        '<button class="billing-add-row" id="billing-add-parts">+ Add Parts / Material</button>' +
        '</div>' +

        // Notes | Grand total
        '<div class="billing-bottom-row">' +
        '<div class="billing-notes">' +
        '<div class="billing-section-label">Notes &amp; Terms</div>' +
        '<textarea class="bill-textarea" placeholder="Payment terms, warranty, additional notes..."></textarea>' +
        '</div>' +
        '<div class="billing-totals-block">' +
        '<table class="billing-totals-table">' +
        '<tr><td>Labor</td><td class="bill-num" id="billing-total-labor">$0.00</td></tr>' +
        '<tr><td>Parts</td><td class="bill-num" id="billing-total-parts">$0.00</td></tr>' +
        '<tr class="billing-tax-row"><td>Tax\u00a0<input class="bill-input bill-num billing-tax-rate" type="number" min="0" max="100" step="0.1" value="0" style="width:40px">%</td>' +
        '<td class="bill-num" id="billing-total-tax">$0.00</td></tr>' +
        '<tr class="billing-grand-row"><td>TOTAL DUE</td><td class="bill-num" id="billing-grand-total">$0.00</td></tr>' +
        '</table>' +
        '</div>' +
        '</div>' + // billing-bottom-row

        '</div>' + // billing-invoice
        photosPageHtml +
        '</div>'   // billing-modal
    );
}

/* =========================================================================
   ROW MANAGEMENT
   ========================================================================= */

function addLaborRow() {
    var tbody = document.getElementById('billing-labor-tbody');
    if (!tbody) return;
    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td colspan="2"><input class="bill-input billing-labor-desc" value="Labor"></td>' +
        '<td><input class="bill-input bill-num billing-labor-hrs"  type="number" min="0" step="0.25" value="0"></td>' +
        '<td><input class="bill-input bill-num billing-labor-rate" type="number" min="0" step="0.01" value="0.00"></td>' +
        '<td class="bill-num billing-labor-total">$0.00</td>' +
        '<td class="no-print-billing"><button class="billing-labor-remove" title="Remove">&#x2715;</button></td>';
    tbody.appendChild(tr);
    recalcTotals();
}

function addPartsRow() {
    var tbody = document.getElementById('billing-parts-tbody');
    if (!tbody) return;
    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td colspan="2"><input class="bill-input" placeholder="Part / Material"></td>' +
        '<td><input class="bill-input bill-num billing-part-qty"  type="number" min="0" step="0.01" value="1"></td>' +
        '<td><input class="bill-input bill-num billing-part-cost" type="number" min="0" step="0.01" value="0.00"></td>' +
        '<td class="bill-num billing-part-total">$0.00</td>' +
        '<td class="no-print-billing"><button class="billing-labor-remove" title="Remove">&#x2715;</button></td>';
    tbody.appendChild(tr);
    recalcTotals();
}

/* =========================================================================
   TOTALS CALCULATOR
   ========================================================================= */

function recalcTotals() {
    var fmt = function(n) { return '$' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };

    var laborTotal = 0;
    var laborRows  = document.querySelectorAll('#billing-labor-tbody tr');
    for (var i = 0; i < laborRows.length; i++) {
        var hrsEl  = laborRows[i].querySelector('.billing-labor-hrs');
        var rateEl = laborRows[i].querySelector('.billing-labor-rate');
        var hrs    = parseFloat((hrsEl  && hrsEl.value)  || 0) || 0;
        var rate   = parseFloat((rateEl && rateEl.value) || 0) || 0;
        var rowT   = hrs * rate;
        laborTotal += rowT;
        var tCell = laborRows[i].querySelector('.billing-labor-total');
        if (tCell) tCell.textContent = fmt(rowT);
    }

    var partsTotal = 0;
    var partRows   = document.querySelectorAll('#billing-parts-tbody tr');
    for (var j = 0; j < partRows.length; j++) {
        var qEl   = partRows[j].querySelector('.billing-part-qty');
        var cEl   = partRows[j].querySelector('.billing-part-cost');
        var qty   = parseFloat((qEl && qEl.value) || 0) || 0;
        var cost  = parseFloat((cEl && cEl.value) || 0) || 0;
        var pRowT = qty * cost;
        partsTotal += pRowT;
        var pCell = partRows[j].querySelector('.billing-part-total');
        if (pCell) pCell.textContent = fmt(pRowT);
    }

    var taxRateEl = document.querySelector('.billing-tax-rate');
    var taxRate   = parseFloat((taxRateEl && taxRateEl.value) || 0) || 0;
    var taxAmt    = (laborTotal + partsTotal) * (taxRate / 100);
    var grand     = laborTotal + partsTotal + taxAmt;

    var set = function(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
    set('billing-labor-subtotal', fmt(laborTotal));
    set('billing-parts-subtotal', fmt(partsTotal));
    set('billing-total-labor',    fmt(laborTotal));
    set('billing-total-parts',    fmt(partsTotal));
    set('billing-total-tax',      fmt(taxAmt));
    set('billing-grand-total',    fmt(grand));
}

/* =========================================================================
   PRINT
   ========================================================================= */

function printInvoice() {
    document.body.classList.add('billing-print-mode');
    window.print();
    var cleanup = function() {
        document.body.classList.remove('billing-print-mode');
        window.removeEventListener('afterprint', cleanup);
    };
    window.addEventListener('afterprint', cleanup);
}
