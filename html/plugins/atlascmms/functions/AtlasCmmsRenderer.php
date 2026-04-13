<?php
/* =========================================================================
 * FlintmanCMS AtlasCMMS Plugin — Renderer
 *
 * Handles HTML generation for all plugin actions.
 * Depends on: atlascmms_str() from functions/common.php
 * ========================================================================= */

if (!defined('IN_CMS')) { die("ERROR - Hacking attempt"); }

class AtlasCmmsRenderer {

    /** @var AtlasCmmsApiClient */
    private $client;

    public function __construct(AtlasCmmsApiClient $client) {
        $this->client = $client;
    }

    /* =====================================================================
     * PUBLIC: renderAssets
     * ===================================================================== */

    public function renderAssets(int $page): string {
        if (!$this->client->isAuthenticated()) {
            return '<div class="alert alert-danger">API is not properly configured.</div>';
        }

        $response = $this->client->fetchAssets(0, 1000);

        if (!isset($response['statusCode']) || $response['statusCode'] !== 200) {
            return '<div class="alert alert-danger">Failed to fetch assets from the API.</div>';
        }

        $rawAssets = $response['content'] ?? (is_array($response) ? $response : []);

        // Separate parents and children
        $childrenMap = [];
        foreach ($rawAssets as $a) {
            $pid = intval($a['parentAsset']['id'] ?? 0);
            if ($pid) {
                $childrenMap[$pid][] = $a;
            }
        }
        $allAssets = array_values(array_filter($rawAssets, function($a) {
            return empty($a['parentAsset']);
        }));
        usort($allAssets, function($a, $b) {
            return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
        });

        // Build latest-WO-per-asset index (covers parents and children)
        $latestWoByAsset = [];
        $allWoResponse   = $this->client->fetchWorkOrders(0, 1000, '');
        if (isset($allWoResponse['statusCode']) && $allWoResponse['statusCode'] === 200) {
            $allWos = $allWoResponse['content'] ?? [];
            usort($allWos, function($a, $b) {
                return strtotime($b['createdAt'] ?? '0') - strtotime($a['createdAt'] ?? '0');
            });
            foreach ($allWos as $wo) {
                $aid = intval($wo['asset']['id'] ?? $wo['assetId'] ?? 0);
                if ($aid && !isset($latestWoByAsset[$aid])) {
                    $latestWoByAsset[$aid] = $wo;
                }
            }
        }

        $perPage    = 20;
        $totalCount = count($allAssets);
        $totalPages = $totalCount > 0 ? (int) ceil($totalCount / $perPage) : 1;
        $page       = min($page, $totalPages - 1);

        $body = '<h2>Assets</h2>';

        if (empty($allAssets)) {
            $body .= '<p>No assets found.</p>';
        } else {
            $body .= '<div class="workorders-search-wrap">'
                   . '<input type="search" id="asset-search" class="login-input" placeholder="Search assets..."'
                   . ' autocomplete="off" data-current-page="' . $page . '">'
                   . '</div>';
            $body .= '<table class="workorders-table assets-table">';
            $body .= '<thead><tr>'
                   . '<th class="asset-expand-col"></th>'
                   . '<th>Name</th><th>Serial #</th><th>Model</th>'
                   . '<th>Manufacturer</th><th>Status</th><th>Latest Work Order</th>'
                   . '</tr></thead>';
            $body .= '<tbody>';

            foreach ($allAssets as $idx => $asset) {
                $assetPage   = (int) floor($idx / $perPage);
                $isCurrentPg = ($assetPage === $page);
                $assetId     = intval($asset['id'] ?? 0);
                $hasChildren = !empty($childrenMap[$assetId]);

                $body .= $this->renderAssetRow($asset, $assetId, $latestWoByAsset, $assetPage, $isCurrentPg, $hasChildren, false, 0);

                // Render child rows hidden beneath the parent
                if ($hasChildren) {
                    foreach ($childrenMap[$assetId] as $child) {
                        $childId = intval($child['id'] ?? 0);
                        $body   .= $this->renderAssetRow($child, $childId, $latestWoByAsset, $assetPage, false, false, true, $assetId);
                    }
                }
            }

            $body .= '</tbody>';
            $body .= '<tfoot><tr id="asset-no-results" style="display:none">'
                   . '<td colspan="8" style="text-align:center;color:var(--text-secondary);padding:20px;">'
                   . 'No assets match your search.</td></tr></tfoot>';
            $body .= '</table>';
        }

        if ($totalPages > 1) {
            $body .= '<div id="asset-pagination" class="pagination">';
            if ($page > 0) {
                $body .= '<a href="?n=plugins&p=atlascmms&action=assets&page=' . ($page - 1) . '">&laquo; Prev</a>';
            }
            $body .= ' Page ' . ($page + 1) . ' of ' . $totalPages . ' ';
            if ($page < $totalPages - 1) {
                $body .= '<a href="?n=plugins&p=atlascmms&action=assets&page=' . ($page + 1) . '">Next &raquo;</a>';
            }
            $body .= '</div>';
        }

        return $body;
    }

    private function renderAssetRow(array $asset, int $assetId, array $latestWoByAsset, int $assetPage, bool $isCurrentPg, bool $hasChildren, bool $isChild, int $parentId): string {
        $latestWo    = $latestWoByAsset[$assetId] ?? null;
        $assetName   = atlascmms_str($asset['name']     ?? '');
        $assetCat    = atlascmms_str($asset['category']['name'] ?? $asset['type'] ?? '');
        $assetSerial = atlascmms_str($asset['serialNumber'] ?? '');
        $assetModel  = atlascmms_str($asset['model']    ?? '');
        $assetMfg    = atlascmms_str($asset['manufacturer'] ?? '');
        $assetStat   = atlascmms_str($asset['status']   ?? '');
        $woTitle     = $latestWo ? atlascmms_str($latestWo['title'] ?? '') : '';
        $woStat      = $latestWo ? atlascmms_str($latestWo['status'] ?? '') : '';

        $searchStr = strtolower($assetName . ' ' . $assetCat . ' ' . $assetSerial . ' ' . $assetModel . ' ' . $assetMfg . ' ' . $assetStat . ' ' . $woTitle . ' ' . $woStat);

        $detailHref = '?n=plugins&amp;p=atlascmms&amp;action=asset_detail&amp;id=' . $assetId;

        if ($isChild) {
            $row  = '<tr class="asset-child-row asset-row-link" data-parent-id="' . $parentId . '"'
                  . ' data-href="' . $detailHref . '"'
                  . ' data-search="' . htmlspecialchars($searchStr, ENT_QUOTES, 'UTF-8') . '"'
                  . ' style="display:none">';
            $row .= '<td class="asset-expand-col asset-child-indent">&#8627;</td>';
        } else {
            $trStyle = $isCurrentPg ? '' : ' style="display:none"';
            $row  = '<tr class="asset-parent-row asset-row-link" data-page="' . $assetPage . '"'
                  . ' data-href="' . $detailHref . '"'
                  . ' data-search="' . htmlspecialchars($searchStr, ENT_QUOTES, 'UTF-8') . '"' . $trStyle . '>';
            if ($hasChildren) {
                $row .= '<td class="asset-expand-col">'
                      . '<button class="asset-expand-btn" data-asset-id="' . $assetId . '" aria-label="Expand children">&#9654;</button>'
                      . '</td>';
            } else {
                $row .= '<td class="asset-expand-col"></td>';
            }
        }

        $row .= '<td>' . $assetName   . '</td>';
        $row .= '<td>' . $assetSerial . '</td>';
        $row .= '<td>' . $assetModel  . '</td>';
        $row .= '<td>' . $assetMfg    . '</td>';
        $row .= '<td>' . $assetStat   . '</td>';

        if ($latestWo) {
            $woId      = intval($latestWo['id'] ?? 0);
            $woCls     = strtolower(preg_replace('/[^a-z0-9]/i', '-', $latestWo['status'] ?? ''));
            $isComplete = in_array(strtoupper($latestWo['status'] ?? ''), ['COMPLETED', 'COMPLETE'], true);
            $woDateStr  = '';
            if ($isComplete && !empty($latestWo['completedOn'])) {
                $woDateStr = '<br><small style="color:var(--text-secondary);font-size:0.75em">'
                           . htmlspecialchars(date('M j, Y g:ia', strtotime($latestWo['completedOn'])), ENT_QUOTES, 'UTF-8')
                           . '</small>';
            }
            $row  .= '<td><a href="?n=plugins&p=atlascmms&action=workorder_detail&id=' . $woId . '">'
                   . $woTitle . '</a>'
                   . ' <span class="status-badge status-' . $woCls . '">' . $woStat . '</span>'
                   . $woDateStr . '</td>';
        } else {
            $row .= '<td><em>None</em></td>';
        }

        $row .= '</tr>';
        return $row;
    }

    /* =====================================================================
     * PUBLIC: renderWorkOrders
     * ===================================================================== */

    public function renderWorkOrders(int $page, int $perPage, string $statusFilter): string {
        if (!$this->client->isAuthenticated()) {
            return '<div class="alert alert-danger">API is not properly configured.</div>';
        }

        $response = $this->client->fetchWorkOrders(0, 1000, '');

        if (!isset($response['statusCode']) || $response['statusCode'] !== 200) {
            return '<div class="alert alert-danger">Failed to fetch work orders from the API.</div>';
        }

        $allWorkorders = $response['content'] ?? (is_array($response) ? $response : []);
        unset($allWorkorders['statusCode']);

        // Client-side status filter (normalises underscores/hyphens for fuzzy match)
        if (!empty($statusFilter)) {
            $allWorkorders = array_values(array_filter($allWorkorders, function($wo) use ($statusFilter) {
                $woStatus = strtolower(str_replace(['_', '-', ' '], '', $wo['status'] ?? ''));
                $filter   = strtolower(str_replace(['_', '-', ' '], '', $statusFilter));
                return $filter !== '' && (strpos($filter, $woStatus) === 0 || strpos($woStatus, $filter) === 0);
            }));
        }

        // Sort newest first
        if (!empty($allWorkorders)) {
            usort($allWorkorders, function($a, $b) {
                return strtotime($b['createdAt'] ?? '0') - strtotime($a['createdAt'] ?? '0');
            });
        }

        $totalCount = count($allWorkorders);
        $totalPages = $totalCount > 0 ? (int) ceil($totalCount / $perPage) : 1;
        $page       = min($page, $totalPages - 1);

        $body = '<h2>Work Orders</h2>';

        // Status tabs
        $statuses = [
            ''           => 'All',
            'OPEN'       => 'Open',
            'IN_PROGRESS'=> 'In Progress',
            'ON_HOLD'    => 'On Hold',
            'COMPLETED'  => 'Completed',
        ];
        $body .= '<div class="status-tabs">';
        foreach ($statuses as $val => $label) {
            $active = ($statusFilter === $val) ? ' status-tab-active' : '';
            $body .= '<a href="?n=plugins&p=atlascmms&action=workorders&status='
                   . htmlspecialchars($val, ENT_QUOTES) . '" class="status-tab' . $active . '">' . $label . '</a>';
        }
        $body .= '</div>';

        $body .= '<div class="workorders-search-wrap">';
        $body .= '<input type="search" id="wo-search" class="login-input" placeholder="Search work orders..."'
               . ' autocomplete="off" data-current-page="' . $page . '">';
        $body .= '</div>';

        $body .= '<div class="workorders-container">';

        if (empty($allWorkorders)) {
            $body .= '<p>No work orders found.</p>';
        } else {
            // All rows rendered in DOM; non-current-page rows hidden via inline style.
            // JS search reveals them across pages using data-search attribute.
            $body .= '<table class="workorders-table">';
            $body .= '<thead><tr>'
                   . '<th>WO #</th><th>Title</th><th>Asset</th><th>Status</th>'
                   . '<th>Priority</th><th>Assigned To</th><th>Date</th>'
                   . '</tr></thead>';
            $body .= '<tbody>';

            foreach ($allWorkorders as $woIdx => $wo) {
                $woPage        = (int) floor($woIdx / $perPage);
                $isCurrentPage = ($woPage === $page);

                $createdAt = $wo['createdAt'] ?? '';
                if ($createdAt) {
                    $createdAt = date('M j, Y', strtotime($createdAt));
                }

                $assignedStr = $this->buildAssignedNames($wo);
                $searchText  = $this->buildSearchText($wo, $assignedStr, $createdAt);
                $trStyle     = $isCurrentPage ? '' : ' style="display:none"';
                $statusCls   = strtolower(preg_replace('/[^a-z0-9]/i', '-', $wo['status'] ?? ''));

                $woDetailUrl = '?n=plugins&amp;p=atlascmms&amp;action=workorder_detail&amp;id=' . intval($wo['id']);
                $body .= '<tr class="asset-row-link" data-href="' . $woDetailUrl . '" data-page="' . $woPage . '" data-search="'
                       . htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') . '"' . $trStyle . '>';
                $body .= '<td>' . atlascmms_str($wo['customId'] ?? $wo['id'] ?? '') . '</td>';
                $body .= '<td>' . atlascmms_str($wo['title'] ?? '') . '</td>';
                $body .= '<td>' . atlascmms_str($wo['asset']['name'] ?? '') . '</td>';
                $body .= '<td><span class="status-badge status-' . $statusCls . '">'
                       . atlascmms_str($wo['status'] ?? '') . '</span></td>';
                $body .= '<td>' . atlascmms_str($wo['priority'] ?? '') . '</td>';
                $body .= '<td>' . htmlspecialchars($assignedStr, ENT_QUOTES, 'UTF-8') . '</td>';
                $body .= '<td>' . htmlspecialchars($createdAt,   ENT_QUOTES, 'UTF-8') . '</td>';
                $body .= '</tr>';
            }

            $body .= '</tbody>';
            $body .= '<tfoot><tr id="wo-no-results" style="display:none">'
                   . '<td colspan="7" style="text-align:center;color:var(--text-secondary);padding:20px;">'
                   . 'No work orders match your search.</td></tr></tfoot>';
            $body .= '</table>';
        }

        $body .= '</div>'; // workorders-container

        if ($totalPages > 1) {
            $body .= '<div id="wo-pagination" class="pagination">';
            if ($page > 0) {
                $body .= '<a href="?n=plugins&p=atlascmms&action=workorders&status='
                       . htmlspecialchars($statusFilter, ENT_QUOTES) . '&page=' . ($page - 1) . '">&laquo; Prev</a>';
            }
            $body .= ' Page ' . ($page + 1) . ' of ' . $totalPages . ' ';
            if ($page < $totalPages - 1) {
                $body .= '<a href="?n=plugins&p=atlascmms&action=workorders&status='
                       . htmlspecialchars($statusFilter, ENT_QUOTES) . '&page=' . ($page + 1) . '">Next &raquo;</a>';
            }
            $body .= '</div>';
        }

        return $body;
    }

    /* =====================================================================
     * PUBLIC: renderWorkOrderDetail
     * $title is passed by reference and set to the WO title on success.
     * ===================================================================== */

    public function renderWorkOrderDetail(int $id, string &$title): string {
        if (!$this->client->isAuthenticated()) {
            return '<div class="alert alert-danger">API is not properly configured.</div>';
        }

        $response = $this->client->fetchWorkOrder($id);

        if (!(isset($response['statusCode']) && ($response['statusCode'] === 200 || isset($response['id'])))) {
            $title = 'Work Order Not Found';
            return '<div class="alert alert-danger">Work order not found.</div>';
        }

        $wo = $response;
        $title = 'Work Order ' . ($wo['customId'] ?? '#' . $id);

        $assignedStr   = $this->buildAssignedNames($wo);
        $assignedNames = $assignedStr ?: '—';

        $primaryUser = '';
        if (!empty($wo['primaryUser'])) {
            $primaryUser = trim(($wo['primaryUser']['firstName'] ?? '') . ' ' . ($wo['primaryUser']['lastName'] ?? ''));
        }

        $completedBy = '';
        if (!empty($wo['completedBy'])) {
            $completedBy = trim(($wo['completedBy']['firstName'] ?? '') . ' ' . ($wo['completedBy']['lastName'] ?? ''));
        }

        $statusClass = 'status-' . strtolower(preg_replace('/[^a-z0-9]/i', '-', $wo['status'] ?? ''));

        $body = '<div class="wo-print-wrap" id="wo-printable">';

        // ── Header bar ──────────────────────────────────────────────────────
        $body .= '<div class="wo-header">';
        $body .= '<div class="wo-header-left">';
        $body .= '<div class="wo-number">WO# ' . htmlspecialchars($wo['customId'] ?? $id, ENT_QUOTES, 'UTF-8') . '</div>';
        $body .= '<h2 class="wo-title">' . htmlspecialchars($wo['title'] ?? '', ENT_QUOTES, 'UTF-8') . '</h2>';
        $body .= '</div>';
        $body .= '<div class="wo-header-right">';
        $body .= '<span class="status-badge ' . $statusClass . ' wo-status-badge">'
               . htmlspecialchars($wo['status'] ?? '', ENT_QUOTES, 'UTF-8') . '</span>';
        $body .= '<button class="button wo-billable-btn no-print" id="wo-billable-btn">&#36; Billable</button>';
        $body .= '<button class="button wo-print-btn no-print" onclick="window.print()">&#128438; Print</button>';
        $body .= '</div>';
        $body .= '</div>'; // wo-header

        // ── Info grid ───────────────────────────────────────────────────────
        $body .= '<div class="wo-grid">';

        $body .= '<div class="wo-section">';
        $body .= '<div class="wo-section-title">Work Order Details</div>';
        $body .= '<table class="detail-table">';
        $body .= '<tr><td>Priority</td><td>'      . htmlspecialchars($wo['priority'] ?? '—',                ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $body .= '<tr><td>Category</td><td>'      . htmlspecialchars($wo['category']['name'] ?? '—',       ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $body .= '<tr><td>Due Date</td><td>'      . $this->formatDay($wo['dueDate']             ?? '')     . '</td></tr>';
        $body .= '<tr><td>Est. Start</td><td>'    . $this->formatDay($wo['estimatedStartDate']  ?? '')     . '</td></tr>';
        $body .= '<tr><td>Est. Duration</td><td>' . (isset($wo['estimatedDuration'])
                    ? htmlspecialchars($wo['estimatedDuration'] . ' hrs', ENT_QUOTES, 'UTF-8') : '—')      . '</td></tr>';
        $body .= '<tr><td>Created</td><td>'       . $this->formatDateTime($wo['createdAt']      ?? '')     . '</td></tr>';
        if ($wo['status'] === 'COMPLETED' || $wo['status'] === 'complete') {
            $body .= '<tr><td>Completed On</td><td>' . $this->formatDateTime($wo['completedOn'] ?? '') . '</td></tr>';
        }
        $body .= '</table></div>';

        $body .= '<div class="wo-section">';
        $body .= '<div class="wo-section-title">People &amp; Location</div>';
        $body .= '<table class="detail-table">';
        if ($primaryUser) {
            $body .= '<tr><td>Primary Tech</td><td>' . htmlspecialchars($primaryUser, ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }
        $body .= '<tr><td>Assigned To</td><td>' . htmlspecialchars($assignedNames, ENT_QUOTES, 'UTF-8') . '</td></tr>';
        if (!empty($wo['team']['name'])) {
            $body .= '<tr><td>Team</td><td>' . htmlspecialchars($wo['team']['name'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }
        if ($completedBy) {
            $body .= '<tr><td>Completed By</td><td>' . htmlspecialchars($completedBy, ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }
        if (!empty($wo['asset']['name'])) {
            $body .= '<tr><td>Asset</td><td>' . htmlspecialchars($wo['asset']['name'], ENT_QUOTES, 'UTF-8');
            if (!empty($wo['asset']['customId'])) {
                $body .= ' <small>(' . htmlspecialchars($wo['asset']['customId'], ENT_QUOTES, 'UTF-8') . ')</small>';
            }
            $body .= '</td></tr>';
        }
        if (!empty($wo['location']['name'])) {
            $body .= '<tr><td>Location</td><td>' . htmlspecialchars($wo['location']['name'], ENT_QUOTES, 'UTF-8');
            if (!empty($wo['location']['address'])) {
                $body .= '<br><small>' . htmlspecialchars($wo['location']['address'], ENT_QUOTES, 'UTF-8') . '</small>';
            }
            $body .= '</td></tr>';
        }
        $body .= '</table></div>';

        $body .= '</div>'; // wo-grid

        // ── Description ─────────────────────────────────────────────────────
        if (!empty($wo['description'])) {
            $body .= '<div class="wo-section wo-section-full">';
            $body .= '<div class="wo-section-title">Description</div>';
            $body .= '<div class="wo-description">'
                   . nl2br(htmlspecialchars((string)$wo['description'], ENT_QUOTES, 'UTF-8')) . '</div>';
            $body .= '</div>';
        }

        // ── Feedback ────────────────────────────────────────────────────────
        if (!empty($wo['feedback'])) {
            $body .= '<div class="wo-section wo-section-full">';
            $body .= '<div class="wo-section-title">Feedback / Resolution</div>';
            $body .= '<div class="wo-description">'
                   . nl2br(htmlspecialchars((string)$wo['feedback'], ENT_QUOTES, 'UTF-8')) . '</div>';
            $body .= '</div>';
        }

        // ── Main image ──────────────────────────────────────────────────────
        if (!empty($wo['image']['url'])) {
            $body .= $this->renderPhotoSection('Work Order Image', [
                ['url' => $wo['image']['url'], 'name' => $wo['image']['name'] ?? '']
            ]);
        }

        // ── Attachments (photos + docs) ─────────────────────────────────────
        if (!empty($wo['files']) && is_array($wo['files'])) {
            $photos = [];
            $docs   = [];
            foreach ($wo['files'] as $file) {
                if (empty($file['url'])) continue;
                if ($this->isImage($file['name'] ?? '')) {
                    $photos[] = $file;
                } else {
                    $docs[] = $file;
                }
            }
            if (!empty($photos)) {
                $body .= $this->renderPhotoSection('Photos (' . count($photos) . ')', $photos);
            }
            if (!empty($docs)) {
                $body .= $this->renderDocSection('Attachments (' . count($docs) . ')', $docs);
            }
        }

        // ── Tasks & Parts ───────────────────────────────────────────────────
        $tasks    = $this->client->fetchWorkOrderTasks($id);
        $partQtys = $this->client->fetchWorkOrderPartQuantities($id);

        // Billing data script tag (must come before tasks/parts sections)
        $body .= $this->renderBillingDataScript($wo, $id, $tasks, $partQtys, $assignedStr, $completedBy);

        if (!empty($tasks) && is_array($tasks)) {
            $body .= $this->renderTasks($tasks);
        }

        if (!empty($partQtys) && is_array($partQtys)) {
            $body .= $this->renderParts($partQtys);
        }

        // ── Signature ───────────────────────────────────────────────────────
        if (!empty($wo['signature'])) {
            $body .= '<div class="wo-section wo-section-full">';
            $body .= '<div class="wo-section-title">Signature</div>';
            $sigUrl = htmlspecialchars($wo['signature'], ENT_QUOTES, 'UTF-8');
            if (strpos($wo['signature'], 'data:image') === 0 || $this->isImage($wo['signature'])) {
                $body .= '<img src="' . $sigUrl . '" alt="Signature" class="wo-signature">';
            } else {
                $body .= '<p>' . $sigUrl . '</p>';
            }
            $body .= '</div>';
        }

        $body .= '</div>'; // wo-print-wrap

        return $body;
    }

    /* =====================================================================
     * PUBLIC: renderAssetDetail
     * ===================================================================== */

    public function renderAssetDetail(int $id, string &$title): string {
        if (!$this->client->isAuthenticated()) {
            return '<div class="alert alert-danger">API is not properly configured.</div>';
        }

        $response = $this->client->fetchAsset($id);
        if (!(isset($response['statusCode']) && ($response['statusCode'] === 200 || isset($response['id'])))) {
            $title = 'Asset Not Found';
            return '<div class="alert alert-danger">Asset not found.</div>';
        }

        $asset  = $response;
        $title  = 'Asset: ' . htmlspecialchars($asset['name'] ?? ('#' . $id), ENT_QUOTES, 'UTF-8');

        // Children — reliable search+filter (same pattern as WO fetch)
        $children = $this->client->fetchChildAssetsReliable($id);

        // Parent — $asset['parentAsset'] may carry only an id stub; resolve name if missing
        $parentAsset = null;
        $rawParent   = $asset['parentAsset'] ?? null;
        if (!empty($rawParent['id'])) {
            if (!empty($rawParent['name'])) {
                $parentAsset = $rawParent;
            } else {
                $fetched = $this->client->fetchAsset(intval($rawParent['id']));
                if (isset($fetched['id'])) {
                    $parentAsset = $fetched;
                }
            }
        }

        // Work orders for this asset — sorted newest first
        // Uses search endpoint (proven reliable) rather than the per-asset GET endpoint
        $wos = $this->client->fetchWorkOrdersForAsset($id);
        if (!empty($wos)) {
            usort($wos, function($a, $b) {
                return strtotime($b['createdAt'] ?? '0') - strtotime($a['createdAt'] ?? '0');
            });
        }

        // Collect photos and docs from the asset itself and all WOs
        $allPhotos = [];
        $allDocs   = [];
        // Asset primary image
        if (!empty($asset['image']['url'])) {
            $imgUrl = $this->client->getImageUrl($asset['image']['url']);
            if ($imgUrl !== '') {
                $allPhotos[] = [
                    'url'    => $imgUrl,
                    'name'   => $asset['image']['name'] ?? ($asset['name'] ?? 'Asset Image'),
                    'source' => 'Asset',
                    'woId'   => null,
                ];
            }
        }
        // Asset files array
        if (!empty($asset['files']) && is_array($asset['files'])) {
            foreach ($asset['files'] as $file) {
                if (empty($file['url'])) continue;
                $fileUrl = $this->client->getImageUrl($file['url']);
                if ($fileUrl === '') continue;
                $entry = [
                    'url'    => $fileUrl,
                    'name'   => $file['name'] ?? 'File',
                    'source' => 'Asset',
                    'woId'   => null,
                ];
                if ($this->isImage($file['name'] ?? '')) {
                    $allPhotos[] = $entry;
                } else {
                    $allDocs[] = $entry;
                }
            }
        }
        foreach ($wos as $wo) {
            $woId    = intval($wo['id'] ?? 0);
            $woLabel = 'WO# ' . ($wo['customId'] ?? $woId);
            if (!empty($wo['image']['url'])) {
                $imgUrl = $this->client->getImageUrl($wo['image']['url']);
                if ($imgUrl !== '') {
                    $allPhotos[] = [
                        'url'    => $imgUrl,
                        'name'   => $wo['image']['name'] ?? $wo['title'] ?? '',
                        'source' => $woLabel,
                        'woId'   => $woId,
                    ];
                }
            }
            if (!empty($wo['files']) && is_array($wo['files'])) {
                foreach ($wo['files'] as $file) {
                    if (empty($file['url'])) continue;
                    $fileUrl = $this->client->getImageUrl($file['url']);
                    if ($fileUrl === '') continue;
                    $entry = [
                        'url'    => $fileUrl,
                        'name'   => $file['name'] ?? 'File',
                        'source' => $woLabel,
                        'woId'   => $woId,
                    ];
                    if ($this->isImage($file['name'] ?? '')) {
                        $allPhotos[] = $entry;
                    } else {
                        $allDocs[] = $entry;
                    }
                }
            }
            // Task images embedded in WO data (if present) or fetched separately
            $woTasks = !empty($wo['tasks']) && is_array($wo['tasks'])
                ? $wo['tasks']
                : $this->client->fetchWorkOrderTasks($woId);
            foreach ($woTasks as $task) {
                if (empty($task['images']) || !is_array($task['images'])) continue;
                $taskLabel = $task['taskBase']['label'] ?? $task['taskBase']['name'] ?? '';
                $taskLabel = $taskLabel !== '' ? ' — ' . $taskLabel : '';
                foreach ($task['images'] as $tImg) {
                    if (empty($tImg['url'])) continue;
                    $imgUrl = $this->client->getImageUrl($tImg['url']);
                    if ($imgUrl === '') continue;
                    $allPhotos[] = [
                        'url'    => $imgUrl,
                        'name'   => $tImg['name'] ?? 'Task Photo',
                        'source' => $woLabel . $taskLabel,
                        'woId'   => $woId,
                    ];
                }
            }
        }

        $statusCls = 'status-' . strtolower(preg_replace('/[^a-z0-9]/i', '-', $asset['status'] ?? ''));

        $body  = '<div class="asset-detail-wrap">';

        // Header
        $body .= '<div class="asset-detail-header">';
        $body .= '<div class="asset-detail-header-left">';
        $body .= '<div class="asset-detail-id">'
               . htmlspecialchars($asset['customId'] ?? ('Asset #' . $id), ENT_QUOTES, 'UTF-8')
               . '</div>';
        $body .= '<h2 class="asset-detail-title">'
               . htmlspecialchars($asset['name'] ?? '', ENT_QUOTES, 'UTF-8') . '</h2>';
        $body .= '</div>';
        $body .= '<div class="asset-detail-header-right">';
        if (!empty($asset['status'])) {
            $body .= '<span class="status-badge ' . $statusCls . '">'
                   . htmlspecialchars($asset['status'], ENT_QUOTES, 'UTF-8') . '</span>';
        }
        $body .= '</div>';
        $body .= '</div>'; // asset-detail-header

        // Tab buttons
        $hasFiles = !empty($allPhotos) || !empty($allDocs);
        $body .= '<div class="asset-tabs" id="asset-detail-tabs">';
        $body .= '<button class="asset-tab-btn asset-tab-active" data-tab="asset-tab-details">Details</button>';
        $body .= '<button class="asset-tab-btn" data-tab="asset-tab-files">Files &amp; Photos'
               . ($hasFiles ? ' <span class="asset-tab-badge">' . (count($allPhotos) + count($allDocs)) . '</span>' : '')
               . '</button>';
        $body .= '<button class="asset-tab-btn" data-tab="asset-tab-workorders">Work Orders'
               . (!empty($wos) ? ' <span class="asset-tab-badge">' . count($wos) . '</span>' : '')
               . '</button>';
        $body .= '</div>';

        // ── TAB 1: Details ────────────────────────────────────────────────
        $body .= '<div class="asset-tab-panel" id="asset-tab-details">';
        $body .= '<div class="wo-grid">';

        // Left column — asset info
        $body .= '<div class="wo-section">';
        $body .= '<div class="wo-section-title">Asset Information</div>';
        $body .= '<table class="detail-table">';
        $body .= '<tr><td>Name</td><td>'         . htmlspecialchars($asset['name']         ?? '—', ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $body .= '<tr><td>Category</td><td>'     . htmlspecialchars($asset['category']['name'] ?? $asset['type'] ?? '—', ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $body .= '<tr><td>Serial #</td><td>'     . htmlspecialchars($asset['serialNumber']  ?? '—', ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $body .= '<tr><td>Model</td><td>'        . htmlspecialchars($asset['model']         ?? '—', ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $body .= '<tr><td>Manufacturer</td><td>' . htmlspecialchars($asset['manufacturer']  ?? '—', ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $body .= '<tr><td>Status</td><td>'       . htmlspecialchars($asset['status']        ?? '—', ENT_QUOTES, 'UTF-8') . '</td></tr>';
        if (!empty($asset['purchasePrice'])) {
            $body .= '<tr><td>Purchase Price</td><td>$' . htmlspecialchars((string)$asset['purchasePrice'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }
        if (!empty($asset['purchaseDate'])) {
            $body .= '<tr><td>Purchase Date</td><td>' . $this->formatDay($asset['purchaseDate']) . '</td></tr>';
        }
        if (!empty($asset['warrantyExpirationDate'])) {
            $body .= '<tr><td>Warranty Exp.</td><td>' . $this->formatDay($asset['warrantyExpirationDate']) . '</td></tr>';
        }
        $body .= '</table></div>';

        // Right column — location + hierarchy
        $body .= '<div class="wo-section">';
        $body .= '<div class="wo-section-title">Location &amp; Hierarchy</div>';
        $body .= '<table class="detail-table">';
        if (!empty($asset['location']['name'])) {
            $body .= '<tr><td>Location</td><td>' . htmlspecialchars($asset['location']['name'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }
        if (!empty($asset['location']['address'])) {
            $body .= '<tr><td>Address</td><td>' . htmlspecialchars($asset['location']['address'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }
        if ($parentAsset !== null) {
            $pId   = intval($parentAsset['id'] ?? 0);
            $pName = htmlspecialchars($parentAsset['name'] ?? ('Asset #' . $pId), ENT_QUOTES, 'UTF-8');
            $pUrl  = '?n=plugins&amp;p=atlascmms&amp;action=asset_detail&amp;id=' . $pId;
            $body .= '<tr><td>Parent Asset</td><td><a href="' . $pUrl . '" class="asset-child-link">' . $pName . '</a></td></tr>';
        }
        // Children
        if (!empty($children)) {
            $body .= '<tr><td>Sub-Assets</td><td>';
            foreach ($children as $child) {
                $cId   = intval($child['id'] ?? 0);
                $cName = htmlspecialchars($child['name'] ?? ('Asset #' . $cId), ENT_QUOTES, 'UTF-8');
                $cUrl  = '?n=plugins&amp;p=atlascmms&amp;action=asset_detail&amp;id=' . $cId;
                $body .= '<a href="' . $cUrl . '" class="asset-child-link">' . $cName . '</a> ';
            }
            $body .= '</td></tr>';
        }
        $body .= '</table></div>';
        $body .= '</div>'; // wo-grid

        // Description
        if (!empty($asset['description'])) {
            $body .= '<div class="wo-section wo-section-full">';
            $body .= '<div class="wo-section-title">Description</div>';
            $body .= '<div class="wo-description">'
                   . nl2br(htmlspecialchars((string)$asset['description'], ENT_QUOTES, 'UTF-8')) . '</div>';
            $body .= '</div>';
        }

        // Asset's own image
        if (!empty($asset['image']['url'])) {
            $body .= $this->renderPhotoSection('Asset Image', [[
                'url'  => $asset['image']['url'],
                'name' => $asset['image']['name'] ?? ($asset['name'] ?? ''),
            ]]);
        }

        $body .= '</div>'; // asset-tab-details

        // ── TAB 2: Files & Photos ─────────────────────────────────────────
        $body .= '<div class="asset-tab-panel" id="asset-tab-files" style="display:none">';
        if (empty($allPhotos) && empty($allDocs)) {
            $body .= '<p style="padding:16px;color:var(--text-secondary)">No files or photos found for this asset or its work orders.</p>';
        } else {
            if (!empty($allPhotos)) {
                $body .= '<div class="wo-section wo-section-full">';
                $body .= '<div class="wo-section-title">Photos (' . count($allPhotos) . ')</div>';
                $body .= '<div class="wo-photos">';
                foreach ($allPhotos as $photo) {
                    $pUrl  = htmlspecialchars($photo['url'],  ENT_QUOTES, 'UTF-8');
                    $pName = htmlspecialchars($photo['name'], ENT_QUOTES, 'UTF-8');
                    $body .= '<div class="wo-photo-item">';
                    $body .= '<a href="' . $pUrl . '" onclick="window.open(this.href,\'photo\','
                           . '\'width=1000,height=800,resizable=yes,scrollbars=yes\');return false;">';
                    $body .= '<img src="' . $pUrl . '" alt="' . $pName . '" class="wo-photo" loading="lazy">';
                    $body .= '</a>';
                    if ($pName) {
                        $body .= '<span class="wo-photo-name">' . $pName . '</span>';
                    }
                    if (!empty($photo['woId'])) {
                        $woUrl  = '?n=plugins&amp;p=atlascmms&amp;action=workorder_detail&amp;id=' . intval($photo['woId']);
                        $body  .= '<a href="' . $woUrl . '" class="asset-file-wo-link">'
                               . htmlspecialchars($photo['source'], ENT_QUOTES, 'UTF-8') . '</a>';
                    } else {
                        $body .= '<span class="asset-file-source">'
                               . htmlspecialchars($photo['source'], ENT_QUOTES, 'UTF-8') . '</span>';
                    }
                    $body .= '<a href="' . $pUrl . '" download class="btn-link wo-photo-dl">&#11123; Download</a>';
                    $body .= '</div>';
                }
                $body .= '</div></div>';
            }
            if (!empty($allDocs)) {
                $body .= '<div class="wo-section wo-section-full">';
                $body .= '<div class="wo-section-title">Documents (' . count($allDocs) . ')</div>';
                $body .= '<ul class="wo-files">';
                foreach ($allDocs as $doc) {
                    $dUrl    = htmlspecialchars($doc['url'],  ENT_QUOTES, 'UTF-8');
                    $dName   = htmlspecialchars($doc['name'], ENT_QUOTES, 'UTF-8');
                    $ext     = strtoupper(pathinfo($doc['name'], PATHINFO_EXTENSION));
                    $body   .= '<li class="wo-file-item">';
                    $body   .= '<span class="wo-file-ext">' . htmlspecialchars($ext ?: '?', ENT_QUOTES, 'UTF-8') . '</span>';
                    $body   .= '<a href="' . $dUrl . '" target="_blank" rel="noopener" class="wo-file-name">' . $dName . '</a>';
                    if (!empty($doc['woId'])) {
                        $woUrl  = '?n=plugins&amp;p=atlascmms&amp;action=workorder_detail&amp;id=' . intval($doc['woId']);
                        $body  .= '<a href="' . $woUrl . '" class="asset-file-wo-link">'
                               . htmlspecialchars($doc['source'], ENT_QUOTES, 'UTF-8') . '</a>';
                    }
                    $body .= '<a href="' . $dUrl . '" download class="btn-link wo-photo-dl">&#11123; Download</a>';
                    $body .= '</li>';
                }
                $body .= '</ul></div>';
            }
        }
        $body .= '</div>'; // asset-tab-files

        // ── TAB 3: Work Orders ────────────────────────────────────────────
        $body .= '<div class="asset-tab-panel" id="asset-tab-workorders" style="display:none">';
        if (empty($wos)) {
            $body .= '<p style="padding:16px;color:var(--text-secondary)">No work orders found for this asset.</p>';
        } else {
            $body .= '<table class="workorders-table">';
            $body .= '<thead><tr>'
                   . '<th>WO #</th><th>Title</th><th>Status</th>'
                   . '<th>Priority</th><th>Assigned To</th><th>Date</th>'
                   . '</tr></thead>';
            $body .= '<tbody>';
            foreach ($wos as $wo) {
                $woId     = intval($wo['id'] ?? 0);
                $isComplete = in_array(strtoupper($wo['status'] ?? ''), ['COMPLETED', 'COMPLETE'], true);
                if ($isComplete && !empty($wo['completedOn'])) {
                    $dateLabel = 'Completed ' . date('M j, Y g:ia', strtotime($wo['completedOn']));
                } else {
                    $raw = $wo['createdAt'] ?? '';
                    $dateLabel = $raw ? date('M j, Y', strtotime($raw)) : '';
                }
                $stCls    = strtolower(preg_replace('/[^a-z0-9]/i', '-', $wo['status'] ?? ''));
                $assigned = $this->buildAssignedNames($wo) ?: '—';
                $woUrl = '?n=plugins&amp;p=atlascmms&amp;action=workorder_detail&amp;id=' . $woId;
                $body .= '<tr class="asset-row-link" data-href="' . $woUrl . '">';
                $body .= '<td>' . atlascmms_str($wo['customId'] ?? $wo['id'] ?? '') . '</td>';
                $body .= '<td>' . atlascmms_str($wo['title'] ?? '') . '</td>';
                $body .= '<td><span class="status-badge status-' . $stCls . '">'
                       . atlascmms_str($wo['status'] ?? '') . '</span></td>';
                $body .= '<td>' . atlascmms_str($wo['priority'] ?? '—') . '</td>';
                $body .= '<td>' . htmlspecialchars($assigned,   ENT_QUOTES, 'UTF-8') . '</td>';
                $body .= '<td>' . htmlspecialchars($dateLabel,  ENT_QUOTES, 'UTF-8') . '</td>';
                $body .= '</tr>';
            }
            $body .= '</tbody></table>';
        }
        $body .= '</div>'; // asset-tab-workorders

        $body .= '</div>'; // asset-detail-wrap
        return $body;
    }

    /* =====================================================================
     * PUBLIC: renderAssetWorkOrders
     * ===================================================================== */

    public function renderAssetWorkOrders(int $assetId): string {
        if (!$this->client->isAuthenticated()) {
            return '<div class="alert alert-danger">API is not properly configured.</div>';
        }

        $response = $this->client->fetchWorkOrdersByAsset($assetId);

        if (!empty($response) && is_array($response)) {
            usort($response, function($a, $b) {
                return strtotime($b['createdAt'] ?? '0') - strtotime($a['createdAt'] ?? '0');
            });
        }

        $body = '<h2>Work Orders for Asset #' . $assetId . '</h2>';

        if (empty($response) && !is_array($response)) {
            return $body . '<p>Unable to fetch work orders.</p>';
        }

        if (empty($response)) {
            return $body
                 . '<p>No work orders found for this asset.</p>'
                 . '<br><a href="?n=plugins&p=atlascmms&action=assets" class="button">Back to Assets</a>';
        }

        $body .= '<table class="workorders-table">';
        $body .= '<thead><tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Date</th></tr></thead>';
        $body .= '<tbody>';

        foreach ($response as $wo) {
            $createdAt = $wo['createdAt'] ?? '';
            if ($createdAt) {
                $createdAt = date('M j, Y', strtotime($createdAt));
            }
            $statusCls = strtolower(preg_replace('/[^a-z0-9]/i', '-', $wo['status'] ?? ''));
            $body .= '<tr>';
            $body .= '<td>' . atlascmms_str($wo['id'] ?? '') . '</td>';
            $body .= '<td><a href="?n=plugins&p=atlascmms&action=workorder_detail&id='
                   . intval($wo['id']) . '">' . atlascmms_str($wo['title'] ?? '') . '</a></td>';
            $body .= '<td><span class="status-badge status-' . $statusCls . '">'
                   . atlascmms_str($wo['status'] ?? '') . '</span></td>';
            $body .= '<td>' . atlascmms_str($wo['priority'] ?? '') . '</td>';
            $body .= '<td>' . htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') . '</td>';
            $body .= '</tr>';
        }

        $body .= '</tbody></table>';
        $body .= '<br><a href="?n=plugins&p=atlascmms&action=assets" class="button">Back to Assets</a>';

        return $body;
    }

    /* =====================================================================
     * PRIVATE: Date formatters
     * ===================================================================== */

    private function formatDateTime(string $d): string {
        return $d ? date('M j, Y g:i A', strtotime($d)) : '—';
    }

    private function formatDay(string $d): string {
        return $d ? date('M j, Y', strtotime($d)) : '—';
    }

    /* =====================================================================
     * PRIVATE: Image type detection
     * ===================================================================== */

    private function isImage(string $name): bool {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
    }

    /* =====================================================================
     * PRIVATE: Build assigned-to display string from WO array
     * ===================================================================== */

    private function buildAssignedNames(array $wo): string {
        $names = [];
        if (!empty($wo['assignedTo']) && is_array($wo['assignedTo'])) {
            foreach ($wo['assignedTo'] as $u) {
                if (is_array($u)) {
                    $n = trim(($u['firstName'] ?? '') . ' ' . ($u['lastName'] ?? ''));
                    if ($n !== '') $names[] = $n;
                } elseif (is_string($u) && $u !== '') {
                    $names[] = $u;
                }
            }
        }
        return implode(', ', $names);
    }

    /* =====================================================================
     * PRIVATE: Build data-search text for WO list rows
     * ===================================================================== */

    private function buildSearchText(array $wo, string $assignedStr, string $createdAt): string {
        $parts = array_filter([
            $wo['customId'] ?? '',
            (string)($wo['id'] ?? ''),
            $wo['title'] ?? '',
            $wo['asset']['name'] ?? '',
            $wo['status'] ?? '',
            $wo['priority'] ?? '',
            $createdAt,
            $assignedStr,
        ], function($s) { return $s !== ''; });
        return strtolower(implode(' ', $parts));
    }

    /* =====================================================================
     * PRIVATE: Photo gallery section
     * ===================================================================== */

    private function renderPhotoSection(string $sectionTitle, array $photos): string {
        $body  = '<div class="wo-section wo-section-full">';
        $body .= '<div class="wo-section-title">' . htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') . '</div>';
        $body .= '<div class="wo-photos">';
        foreach ($photos as $photo) {
            $rawUrl = $this->client->getImageUrl($photo['url'] ?? '');
            if ($rawUrl === '') continue;
            $pUrl  = htmlspecialchars($rawUrl,            ENT_QUOTES, 'UTF-8');
            $pName = htmlspecialchars($photo['name'] ?? '', ENT_QUOTES, 'UTF-8');
            $body .= '<div class="wo-photo-item">';
            $body .= '<a href="' . $pUrl . '" onclick="window.open(this.href,\'photo\','
                   . '\'width=1000,height=800,resizable=yes,scrollbars=yes\');return false;">';
            $body .= '<img src="' . $pUrl . '" alt="' . $pName . '" class="wo-photo" loading="lazy">';
            $body .= '</a>';
            if ($pName) {
                $body .= '<span class="wo-photo-name">' . $pName . '</span>';
            }
            $body .= '<a href="' . $pUrl . '" download class="btn-link wo-photo-dl">&#11123; Download</a>';
            $body .= '</div>';
        }
        $body .= '</div></div>';
        return $body;
    }

    /* =====================================================================
     * PRIVATE: Document/file list section
     * ===================================================================== */

    private function renderDocSection(string $sectionTitle, array $docs): string {
        $body  = '<div class="wo-section wo-section-full">';
        $body .= '<div class="wo-section-title">' . htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') . '</div>';
        $body .= '<ul class="wo-files">';
        foreach ($docs as $doc) {
            $rawUrl = $this->client->getImageUrl($doc['url'] ?? '');
            if ($rawUrl === '') continue;
            $dUrl  = htmlspecialchars($rawUrl,               ENT_QUOTES, 'UTF-8');
            $dName = htmlspecialchars($doc['name'] ?? 'File', ENT_QUOTES, 'UTF-8');
            $ext   = strtoupper(pathinfo($doc['name'] ?? '', PATHINFO_EXTENSION));
            $body .= '<li class="wo-file-item">';
            $body .= '<span class="wo-file-ext">' . htmlspecialchars($ext ?: '?', ENT_QUOTES, 'UTF-8') . '</span>';
            $body .= '<a href="' . $dUrl . '" target="_blank" rel="noopener" class="wo-file-name">' . $dName . '</a>';
            $body .= '<a href="' . $dUrl . '" download class="btn-link wo-photo-dl">&#11123; Download</a>';
            $body .= '</li>';
        }
        $body .= '</ul></div>';
        return $body;
    }

    /* =====================================================================
     * PRIVATE: Tasks checklist section
     * ===================================================================== */

    private function renderTasks(array $tasks): string {
        // Filter out blank placeholder tasks before counting or rendering
        $tasks = array_values(array_filter($tasks, function($t) {
            $lbl = trim((string)($t['taskBase']['label'] ?? ''));
            $val = $t['value'] ?? null;
            return !($lbl === '' && ($val === null || $val === ''));
        }));

        $body  = '<div class="wo-section wo-section-full">';
        $body .= '<div class="wo-section-title">Tasks (' . count($tasks) . ')</div>';
        $body .= '<ul class="wo-task-list">';

        foreach ($tasks as $task) {
            $rawLabel  = trim((string)($task['taskBase']['label'] ?? ''));
            $taskVal   = $task['value'] ?? null;
            $taskLabel = htmlspecialchars($rawLabel !== '' ? $rawLabel : 'Task', ENT_QUOTES, 'UTF-8');
            $taskType  = strtolower($task['taskBase']['taskType'] ?? '');
            $taskNotes = trim((string)($task['notes'] ?? ''));
            $taskImgs  = $task['images'] ?? [];



            $taskValStr = strtolower(trim((string)($taskVal ?? '')));

            $valDisplay = '';
            $valClass   = '';

            if ($taskType === 'subtask') {
                // Subtask: value is either a boolean, a status string, or null/empty (untouched)
                $checked = ($taskVal === true || $taskVal === 1
                            || $taskValStr === 'true' || $taskValStr === '1'
                            || $taskValStr === 'completed' || $taskValStr === 'complete');
                $inProgress = ($taskValStr === 'in_progress' || $taskValStr === 'inprogress'
                               || $taskValStr === 'in progress');
                $onHold     = ($taskValStr === 'on_hold' || $taskValStr === 'onhold'
                               || $taskValStr === 'on hold');
                $isOpen     = ($taskValStr === 'open');

                if ($checked) {
                    $valDisplay = 'DONE';
                    $valClass   = ' val-pass';
                } elseif ($inProgress) {
                    $valDisplay = 'IN PROGRESS';
                    $valClass   = ' val-inprogress';
                } elseif ($onHold) {
                    $valDisplay = 'ON HOLD';
                    $valClass   = ' val-onhold';
                } elseif ($isOpen) {
                    $valDisplay = 'OPEN';
                    $valClass   = ' val-open';
                }
                // null / empty / false → no badge, no checkmark
                if (!$checked && !$inProgress && !$onHold && !$isOpen) { $checked = false; }
            } else {
                $isTextField    = in_array($taskType, ['text', 'textfield', 'text_field', 'textarea']);
                $isNumberField  = in_array($taskType, ['number', 'numberfield', 'number_field', 'numeric']);
                $isChoiceField  = in_array($taskType, ['multiple', 'multichoice', 'multi_choice', 'multiplechoice', 'multiple_choice', 'select', 'dropdown', 'choice']);

                if ($taskVal !== null && $taskVal !== '') {
                    $valUpper   = strtoupper(trim((string)$taskVal));
                    $valDisplay = htmlspecialchars((string)$taskVal, ENT_QUOTES, 'UTF-8');
                    if      ($valUpper === 'PASS')                              { $valClass = ' val-pass';  $checked = true; }
                    elseif  ($valUpper === 'FAIL')                              { $valClass = ' val-fail';  $checked = false; }
                    elseif  ($valUpper === 'N/A')                               { $valClass = ' val-na';    $checked = false; }
                    elseif  ($isTextField || $isNumberField || $isChoiceField)  { $valClass = ' val-other'; $checked = true; }
                    else                                                        { $valClass = ' val-other'; $checked = false; }
                } else {
                    $checked = false;
                }
            }

            $body .= '<li class="wo-task-item">';
            $body .= '<div class="wo-task-row">';
            $body .= '<span class="wo-task-check ' . ($checked ? 'checked' : '') . '">'
                   . ($checked ? '&#10003;' : '') . '</span>';
            $body .= '<span class="wo-task-label">' . $taskLabel . '</span>';
            if ($valDisplay !== '') {
                $body .= '<span class="wo-task-value' . $valClass . '">' . $valDisplay . '</span>';
            }
            $body .= '</div>';

            if ($taskNotes !== '') {
                $body .= '<div class="wo-task-notes">'
                       . htmlspecialchars($taskNotes, ENT_QUOTES, 'UTF-8') . '</div>';
            }

            if (!empty($taskImgs) && is_array($taskImgs)) {
                $body .= '<div class="wo-task-images">';
                foreach ($taskImgs as $tImg) {
                    if (empty($tImg['url'])) continue;
                    $tUrl  = htmlspecialchars($tImg['url'],        ENT_QUOTES, 'UTF-8');
                    $tName = htmlspecialchars($tImg['name'] ?? '', ENT_QUOTES, 'UTF-8');
                    $body .= '<a href="' . $tUrl . '" onclick="window.open(this.href,\'photo\','
                           . '\'width=1000,height=800,resizable=yes,scrollbars=yes\');return false;">';
                    $body .= '<img src="' . $tUrl . '" alt="' . $tName
                           . '" class="wo-photo wo-task-photo" loading="lazy">';
                    $body .= '</a>';
                }
                $body .= '</div>';
            }

            $body .= '</li>';
        }

        $body .= '</ul></div>';
        return $body;
    }

    /* =====================================================================
     * PRIVATE: Parts used table section
     * ===================================================================== */

    private function renderParts(array $partQtys): string {
        $totalCost = 0;
        foreach ($partQtys as $pq) {
            $totalCost += ($pq['quantity'] ?? 0) * ($pq['part']['cost'] ?? 0);
        }

        $body  = '<div class="wo-section wo-section-full">';
        $body .= '<div class="wo-section-title">Parts Used (' . count($partQtys) . ')</div>';
        $body .= '<table class="wo-parts-table">';
        $body .= '<thead><tr>'
               . '<th>Part Name</th><th>Description</th>'
               . '<th class="num">Qty</th><th class="num">Unit</th>'
               . '<th class="num">Unit Cost</th><th class="num">Total</th>'
               . '</tr></thead><tbody>';

        foreach ($partQtys as $pq) {
            $pName  = htmlspecialchars($pq['part']['name']        ?? '—', ENT_QUOTES, 'UTF-8');
            $pDesc  = htmlspecialchars($pq['part']['description'] ?? '',  ENT_QUOTES, 'UTF-8');
            $pQty   = $pq['quantity'] ?? 0;
            $pUnit  = htmlspecialchars($pq['part']['unit']        ?? '',  ENT_QUOTES, 'UTF-8');
            $pCost  = $pq['part']['cost'] ?? null;
            $pTotal = ($pCost !== null) ? ($pQty * $pCost) : null;

            $body .= '<tr>';
            $body .= '<td class="wo-part-name">' . $pName . '</td>';
            $body .= '<td class="wo-part-desc">' . $pDesc . '</td>';
            $body .= '<td class="num">' . htmlspecialchars((string)$pQty, ENT_QUOTES, 'UTF-8') . '</td>';
            $body .= '<td class="num">' . $pUnit . '</td>';
            $body .= '<td class="num">' . ($pCost  !== null ? '$' . number_format($pCost,  2) : '—') . '</td>';
            $body .= '<td class="num">' . ($pTotal !== null ? '$' . number_format($pTotal, 2) : '—') . '</td>';
            $body .= '</tr>';
        }

        $body .= '</tbody>';
        if ($totalCost > 0) {
            $body .= '<tfoot><tr>'
                   . '<td colspan="5" class="wo-parts-total-label">Total Parts Cost</td>'
                   . '<td class="num wo-parts-total">$' . number_format($totalCost, 2) . '</td>'
                   . '</tr></tfoot>';
        }
        $body .= '</table></div>';
        return $body;
    }

    /* =====================================================================
     * PRIVATE: Billing data <script> tag injected into WO detail page
     * ===================================================================== */

    private function renderBillingDataScript(array $wo, int $id, $tasks, $partQtys,
                                             string $assignedStr, string $completedBy): string {
        // Parts
        $billingParts = [];
        if (!empty($partQtys) && is_array($partQtys)) {
            foreach ($partQtys as $pq) {
                $billingParts[] = [
                    'name'        => $pq['part']['name']        ?? '',
                    'description' => $pq['part']['description'] ?? '',
                    'qty'         => (float)($pq['quantity']    ?? 0),
                    'unit'        => $pq['part']['unit']        ?? '',
                    'cost'        => (float)($pq['part']['cost'] ?? 0),
                ];
            }
        }

        // Photos (main image + file attachments + task images)
        $billingPhotos = [];
        if (!empty($wo['image']['url'])) {
            $billingPhotos[] = ['url' => $wo['image']['url'], 'name' => $wo['image']['name'] ?? ''];
        }
        if (!empty($wo['files']) && is_array($wo['files'])) {
            foreach ($wo['files'] as $file) {
                if (!empty($file['url']) && $this->isImage($file['name'] ?? '')) {
                    $billingPhotos[] = ['url' => $file['url'], 'name' => $file['name'] ?? ''];
                }
            }
        }
        if (!empty($tasks) && is_array($tasks)) {
            foreach ($tasks as $task) {
                foreach ($task['images'] ?? [] as $tImg) {
                    if (!empty($tImg['url'])) {
                        $billingPhotos[] = ['url' => $tImg['url'], 'name' => $tImg['name'] ?? ''];
                    }
                }
            }
        }

        // Company info
        $companyInfo = [];
        $me = $this->client->getCurrentUser();
        $companyId = isset($me['companyId']) ? (int)$me['companyId'] : null;
        if ($companyId) {
            $co = $this->client->fetchCompany($companyId);
            $companyInfo = [
                'name'    => $co['name']    ?? '',
                'address' => $co['address'] ?? '',
                'city'    => $co['city']    ?? '',
                'state'   => $co['state']   ?? '',
                'zipCode' => $co['zipCode'] ?? '',
                'phone'   => $co['phone']   ?? '',
                'email'   => $co['email']   ?? '',
            ];
        }

        $billingData = [
            'woNumber'          => $wo['customId'] ?? (string)$id,
            'title'             => $wo['title'] ?? '',
            'status'            => $wo['status'] ?? '',
            'category'          => $wo['category']['name'] ?? '',
            'description'       => $wo['description'] ?? '',
            'feedback'          => $wo['feedback'] ?? '',
            'completedOn'       => $this->formatDateTime($wo['completedOn'] ?? ''),
            'estimatedDuration' => (float)($wo['estimatedDuration'] ?? 0),
            'asset'             => trim(($wo['asset']['name'] ?? '')
                                   . (!empty($wo['asset']['customId'])
                                      ? ' (' . $wo['asset']['customId'] . ')' : '')),
            'location'          => $wo['location']['name']    ?? '',
            'locationAddress'   => $wo['location']['address'] ?? '',
            'completedBy'       => $completedBy,
            'assignedTo'        => $assignedStr,
            'parts'             => $billingParts,
            'photos'            => $billingPhotos,
            'company'           => $companyInfo,
        ];

        return '<script>window.atlasBillingData = ' . json_encode($billingData, JSON_HEX_TAG) . ';</script>';
    }
}
