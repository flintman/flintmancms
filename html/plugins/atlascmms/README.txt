================================================================================
AtlasCMMS Integration Plugin for FlintmanCMS
================================================================================

DESCRIPTION:
This plugin integrates FlintmanCMS with the Atlas CMMS API (https://github.com/Grashjs/cmms),
providing a full read-only front-end for assets and work orders within your CMS. All
rendering is handled by AtlasCmmsRenderer (functions/AtlasCmmsRenderer.php).
The dispatcher (atlascmms.php) boots auth, config, and the API client, then
delegates to the renderer. Helper functions live in functions/common.php.

FEATURES:
- Session-based login: API Key or email/password (Bearer token)
- CSRF-protected login form with tab switching
- Assets list with expand/collapse for child (sub-) assets
- Assets sorted alphabetically by name
- Click any asset row to open the asset detail page
- Asset detail page with three tabs:
    Details       — name, ID, serial, model, manufacturer, location, status,
                    parent asset link (resolved even if API omits name),
                    child asset pill links
    Files & Photos — all photos and documents aggregated from the asset itself,
                    its work orders (image + files), and task images from each
                    work order; each item shows its source with a clickable
                    WO link when it originated from a work order
    Work Orders   — all WOs for the asset, sorted newest-first; completed WOs
                    show the completion date/time; click any row to view the WO
- Work orders list with status tabs (All / Open / In Progress / On Hold / Completed)
- Work orders list rows are clickable (no separate View button)
- Work order detail with full task list, billing data, files, and photos
- Assets list shows the latest work order per asset; completed WOs show
  the completion date/time beneath the status badge
- Back navigation uses browser history (history.back()) on detail pages
- Real-time search across all pages for both work orders and assets
- All JS split across three files: atlascmms.js, atlascmms-search.js,
  atlascmms-billing.js

ARCHITECTURE:
  atlascmms.php            — Entry point / dispatcher
  api/ApiClient.php        — All API calls (POST /assets/search,
                             POST /work-orders/search, GET /tasks/work-order/:id,
                             etc.) with reliable search+filter pattern
  functions/
    AtlasCmmsRenderer.php  — All HTML generation (OOP, ~1000 lines)
    common.php             — atlascmms_nav(), atlascmms_str() helpers
  js/
    atlascmms.js           — Tab switching, row-click navigation,
                             expand/collapse children
    atlascmms-search.js    — Live search for assets and work orders
    atlascmms-billing.js   — Billing / labour cost UI
  css/atlascmms.css        — All plugin styles

RELIABLE API PATTERN:
Several per-resource GET endpoints (/assets/{id}/children,
/assets/{id}/work-orders) proved unreliable. All fetching now uses
POST /assets/search and POST /work-orders/search with pageSize=1000,
then filters results in PHP. This is the proven approach used throughout.

INSTALLATION:
1. Upload the 'atlascmms' folder to /html/plugins/
2. Go to Admin > Plugins
3. Find "AtlasCMMS Integration" and click "Activate"
4. Configure your API settings in Admin > Plugins > AtlasCMMS

CONFIGURATION:
Admin Interface: Admin > Plugins > AtlasCMMS

Required Fields:
- API URL: Base URL of your Atlas CMMS API (e.g., http://localhost:8080)
- Authentication Mode: "API Key" or "Login Authorization"
- API Key / Credentials: Your authentication credential

Optional Fields:
- MinIO URL: URL for the MinIO file server used for image/file display

USAGE:

Frontend entry point:  index.php?n=plugins&p=atlascmms

Available Actions:
- workorders          List all work orders (default view)
- workorder_detail    View a work order       (&id=XXX)
- assets              List all assets
- asset_detail        View an asset detail    (&id=XXX)
- asset_workorders    Work orders for an asset (&asset_id=XXX)
- logout              Clear session and return to login

DATABASE TABLES:
- flintmancms_atlascmms_config: Stores API configuration (api_url, minio_url,
  api_key, auth_mode, is_active)
- flintmancms_atlascmms_cache:  Caches API responses for performance

TROUBLESHOOTING:

Q: "API is not properly configured" error
A: Configure the API URL and credentials in Admin > Plugins > AtlasCMMS.

Q: Asset children or work orders not showing
A: The per-resource GET endpoints can return empty results. The plugin uses
   POST search + PHP filtering as the reliable fallback — verify your API
   URL is correct and the service is reachable.

Q: Images not displaying
A: Set the MinIO URL in plugin settings. Image URLs from the API are rewritten
   using that base URL.

Q: Parent asset name missing on child detail
A: The API sometimes returns parentAsset with only an id and no name. The
   plugin automatically fetches the full parent asset to resolve the name.

VERSION: 2.0.0
AUTHOR: FlintmanCMS
LICENSE: Same as FlintmanCMS

================================================================================
