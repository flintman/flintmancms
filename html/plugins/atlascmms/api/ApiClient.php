<?php
/* * *************************************************************************
 *  FlintmanCMS AtlasCMMS Plugin - API Client
 *
 *  PURPOSE:
 *  PHP implementation of the Atlas CMMS API client.
 *  Handles all HTTP communication with the Atlas CMMS API.
 *
 *  FEATURES:
 *  - Authentication (API Key or Bearer token)
 *  - Work Order CRUD operations
 *  - Asset management
 *  - Image/file download from MinIO
 *  - Error handling and response parsing
 *
 * ************************************************************************* */

if (!defined('IN_CMS')) {
    die("ERROR - Hacking attempt");
}

class AtlasCmmsApiClient {

    private $baseUrl = 'http://localhost:8080';
    private $minioUrl = '';
    private $apiKey = '';
    private $token = '';
    private $authMode = 'none'; // 'none', 'api_key', or 'bearer' (login authorization)
    private $timeout = 30;

    /**
     * Constructor
     * @param string $baseUrl Base URL of Atlas CMMS API
     * @param string $minioUrl MinIO file server URL
     */
    public function __construct($baseUrl = '', $minioUrl = '') {
        if (!empty($baseUrl)) {
            $this->setBaseUrl($baseUrl);
        }
        if (!empty($minioUrl)) {
            $this->setMinioUrl($minioUrl);
        }
    }

    /**
     * Set the base URL for API calls
     * @param string $url Base URL
     */
    public function setBaseUrl($url) {
        // Strip trailing slash for consistency
        $this->baseUrl = rtrim($url, '/');
    }

    /**
     * Get the current base URL
     * @return string Base URL
     */
    public function getBaseUrl() {
        return $this->baseUrl;
    }

    /**
     * Set MinIO URL for file serving
     * @param string $url MinIO URL
     */
    public function setMinioUrl($url) {
        $this->minioUrl = rtrim($url, '/');
    }

    /**
     * Get MinIO URL
     * @return string MinIO URL
     */
    public function getMinioUrl() {
        return $this->minioUrl;
    }

    /**
     * Set API key for authentication
     * @param string $apiKey API key
     */
    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
        $this->token = $apiKey;
        $this->authMode = 'api_key';
    }

    /**
     * Set Bearer token for Login Authorization authentication
     * @param string $token Bearer token or login credentials
     */
    public function setToken($token) {
        $this->token = $token;
        $this->authMode = 'bearer';
    }

    /**
     * Check if authenticated
     * @return bool True if API key or token is set
     */
    public function isAuthenticated() {
        return !empty($this->token);
    }

    /**
     * Make an authenticated HTTP request
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH)
     * @param string $endpoint API endpoint path
     * @param array $data Request body data (for POST/PUT/PATCH)
     * @param array $query Query parameters
     * @return array|false Response data or false on error
     */
    private function makeRequest($method, $endpoint, $data = null, $query = array()) {
        $url = $this->baseUrl . $endpoint;

        // Add query parameters
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // Set headers
        $headers = array(
            'Content-Type: application/json',
            'Accept: application/json',
        );

        // Add authentication header
        if ($this->authMode === 'api_key' && !empty($this->token)) {
            $headers[] = 'x-api-key: ' . $this->token;
        } elseif ($this->authMode === 'bearer' && !empty($this->token)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Set request body for POST/PUT/PATCH
        if (in_array($method, array('POST', 'PUT', 'PATCH')) && $data !== null) {
            $jsonData = is_string($data) ? $data : json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Handle cURL errors
        if ($curlError) {
            return false;
        }

        // Parse JSON response
        if (empty($response)) {
            return array('statusCode' => $httpCode);
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            // Not JSON, return raw response
            return array(
                'statusCode' => $httpCode,
                'raw' => $response
            );
        }

        // Add status code to response
        if (is_array($decoded)) {
            $decoded['statusCode'] = $httpCode;
        }

        return $decoded;
    }

    // ====================================================================
    // AUTHENTICATION METHODS
    // ====================================================================

    /**
     * Login with email and password
     * @param string $email User email
     * @param string $password User password
     * @return array Response with token or error
     */
    public function login($email, $password) {
        $data = array(
            'email'    => $email,
            'password' => $password
        );
        $response = $this->makeRequest('POST', '/auth/signin', $data);

        if ($response && isset($response['accessToken'])) {
            $this->setToken($response['accessToken']);
        }

        return $response;
    }

    /**
     * Get current user information
     * @return array User data or error
     */
    public function getCurrentUser() {
        return $this->makeRequest('GET', '/auth/me');
    }

    /**
     * Fetch a company by ID
     * @param int $id Company ID
     * @return array Company data or error
     */
    public function fetchCompany($id) {
        return $this->makeRequest('GET', '/companies/' . intval($id));
    }

    /**
     * Test the API connection
     * @return array Status response
     */
    public function testConnection() {
        $response = $this->getCurrentUser();

        if (isset($response['statusCode'])) {
            if ($response['statusCode'] === 200) {
                return array('success' => true, 'message' => 'Connected successfully');
            } elseif ($response['statusCode'] === 401) {
                return array('success' => false, 'message' => 'Authentication failed - invalid API key');
            } else {
                return array('success' => false, 'message' => 'Connection error - HTTP ' . $response['statusCode']);
            }
        }

        return array('success' => false, 'message' => 'Connection failed');
    }

    // ====================================================================
    // WORK ORDER METHODS
    // ====================================================================

    /**
     * Fetch work orders with pagination
     * @param int $page Page number (0-based)
     * @param int $pageSize Records per page
     * @param string $statusFilter Filter by status
     * @return array Work orders response
     */
    public function fetchWorkOrders($page = 0, $pageSize = 50, $statusFilter = '') {
        $data = array(
            'pageNum' => $page,
            'pageSize' => $pageSize,
            'sortBy' => 'createdAt',
            'sortDir' => 'DESC'
        );

        if (!empty($statusFilter)) {
            $data['status'] = $statusFilter;
        }

        return $this->makeRequest('POST', '/work-orders/search', $data);
    }

    /**
     * Fetch a single work order
     * @param int $id Work order ID
     * @return array Work order data
     */
    public function fetchWorkOrder($id) {
        return $this->makeRequest('GET', '/work-orders/' . intval($id));
    }

    /**
     * Fetch recent work orders
     * @param int $limit Number of records
     * @return array Array of work orders
     */
    public function fetchRecentWorkOrders($limit = 10) {
        return $this->makeRequest('GET', '/work-orders/recent', null, array('limit' => $limit));
    }

    /**
     * Fetch work order count by status
     * @param string $status Status filter
     * @return int Count
     */
    public function fetchWorkOrderCount($status = '') {
        $response = $this->makeRequest('GET', '/work-orders/count', null, array('status' => $status));
        return isset($response['count']) ? $response['count'] : 0;
    }

    /**
     * Create a new work order
     * @param array $data Work order data
     * @return array Created work order response
     */
    public function createWorkOrder($data) {
        return $this->makeRequest('POST', '/work-orders', $data);
    }

    /**
     * Update a work order
     * @param int $id Work order ID
     * @param array $data Partial data to update
     * @return array Updated work order response
     */
    public function updateWorkOrder($id, $data) {
        return $this->makeRequest('PATCH', '/work-orders/' . intval($id), $data);
    }

    /**
     * Delete a work order
     * @param int $id Work order ID
     * @return array Response
     */
    public function deleteWorkOrder($id) {
        return $this->makeRequest('DELETE', '/work-orders/' . intval($id));
    }

    /**
     * Change work order status
     * @param int $id Work order ID
     * @param string $status New status
     * @param string $feedback Optional feedback
     * @return array Response
     */
    public function changeWorkOrderStatus($id, $status, $feedback = '') {
        $data = array('status' => $status);
        if (!empty($feedback)) {
            $data['feedback'] = $feedback;
        }
        return $this->makeRequest('PATCH', '/work-orders/' . intval($id) . '/status', $data);
    }

    /**
     * Fetch comments for a work order
     * @param int $id Work order ID
     * @param int $page Page number
     * @return array Comments array
     */
    public function fetchWorkOrderComments($id, $page = 0) {
        $data = array(
            'pageNum' => $page,
            'pageSize' => 100,
            'filterFields' => array()
        );
        $response = $this->makeRequest('POST', '/comments/search/' . intval($id), $data);

        // Handle different response formats
        if (isset($response['content'])) {
            return $response['content'];
        } elseif (is_array($response)) {
            return $response;
        }
        return array();
    }

    /**
     * Fetch tasks for a work order
     * @param int $id Work order ID
     * @return array Tasks array
     */
    public function fetchWorkOrderTasks($id) {
        $response = $this->makeRequest('GET', '/tasks/work-order/' . intval($id));

        if (isset($response['content'])) {
            return $response['content'];
        } elseif (is_array($response)) {
            return $response;
        }
        return array();
    }

    /**
     * Fetch part quantities used on a work order
     * @param int $id Work order ID
     * @return array Part quantities array: [{quantity, part: {name, cost, unit, description}}]
     */
    public function fetchWorkOrderPartQuantities($id) {
        $response = $this->makeRequest('GET', '/part-quantities/work-order/' . intval($id));

        if (isset($response['content'])) {
            return $response['content'];
        } elseif (is_array($response)) {
            return $response;
        }
        return array();
    }

    // ====================================================================
    // ASSET METHODS
    // ====================================================================

    /**
     * Fetch assets with pagination
     * @param int $page Page number
     * @param int $pageSize Records per page
     * @return array Assets response
     */
    public function fetchAssets($page = 0, $pageSize = 50) {
        $data = array(
            'pageNum' => $page,
            'pageSize' => $pageSize
        );
        return $this->makeRequest('POST', '/assets/search', $data);
    }

    /**
     * Fetch a single asset
     * @param int $id Asset ID
     * @return array Asset data
     */
    public function fetchAsset($id) {
        return $this->makeRequest('GET', '/assets/' . intval($id));
    }

    /**
     * Fetch all parent assets
     * @return array Array of parent assets
     */
    public function fetchAllParentAssets() {
        $response = $this->makeRequest('GET', '/assets/parent');

        if (isset($response['content'])) {
            return $response['content'];
        } elseif (is_array($response)) {
            return $response;
        }
        return array();
    }

    /**
     * Fetch child assets for a parent using the search endpoint (reliable).
     * Fetches all assets and filters by parentAsset.id in PHP.
     * @param int $parentId Parent asset ID
     * @return array Array of child assets
     */
    public function fetchChildAssetsReliable($parentId) {
        $parentId = intval($parentId);
        $data     = array('pageNum' => 0, 'pageSize' => 1000);
        $response = $this->makeRequest('POST', '/assets/search', $data);

        if (!isset($response['statusCode']) || $response['statusCode'] !== 200) {
            return array();
        }

        $all = $response['content'] ?? array();
        return array_values(array_filter($all, function($a) use ($parentId) {
            return intval($a['parentAsset']['id'] ?? 0) === $parentId;
        }));
    }

    /**
     * Fetch child assets for a parent
     * @param int $parentId Parent asset ID
     * @return array Array of child assets
     */
    public function fetchChildAssets($parentId) {
        $response = $this->makeRequest('GET', '/assets/' . intval($parentId) . '/children');

        if (isset($response['content'])) {
            return $response['content'];
        } elseif (is_array($response)) {
            return $response;
        }
        return array();
    }

    /**
     * Fetch work orders related to an asset
     * @param int $assetId Asset ID
     * @return array Array of work orders
     */
    public function fetchWorkOrdersByAsset($assetId) {
        $response = $this->makeRequest('GET', '/assets/' . intval($assetId) . '/work-orders');

        if (isset($response['content'])) {
            return $response['content'];
        } elseif (is_array($response)) {
            return $response;
        }
        return array();
    }

    /**
     * Fetch all work orders for an asset using the search endpoint (reliable fallback).
     * Fetches all WOs and filters by asset ID in PHP.
     * @param int $assetId Asset ID
     * @return array Array of work orders sorted newest first
     */
    public function fetchWorkOrdersForAsset($assetId) {
        $assetId  = intval($assetId);
        $data     = array(
            'pageNum'  => 0,
            'pageSize' => 1000,
            'sortBy'   => 'createdAt',
            'sortDir'  => 'DESC',
        );
        $response = $this->makeRequest('POST', '/work-orders/search', $data);

        if (!isset($response['statusCode']) || $response['statusCode'] !== 200) {
            return array();
        }

        $all = $response['content'] ?? array();
        return array_values(array_filter($all, function($wo) use ($assetId) {
            return intval($wo['asset']['id'] ?? $wo['assetId'] ?? 0) === $assetId;
        }));
    }

    // ====================================================================
    // IMAGE/FILE METHODS
    // ====================================================================

    /**
     * Get full image URL from MinIO
     * @param string $imageUrl Image URL/path
     * @return string Full URL
     */
    public function getImageUrl($imageUrl) {
        if (empty($imageUrl)) {
            return '';
        }

        // If already a full URL, validate scheme is http(s) only
        if (strpos($imageUrl, 'http') === 0) {
            $scheme = parse_url($imageUrl, PHP_URL_SCHEME);
            if (!in_array($scheme, array('http', 'https'), true)) {
                return '';
            }
            return $imageUrl;
        }

        // Otherwise prepend MinIO URL
        return $this->minioUrl . '/' . ltrim($imageUrl, '/');
    }

    /**
     * Download image data
     * @param string $imageUrl Image URL
     * @return string|false Image data or false on error
     */
    public function downloadImage($imageUrl) {
        if (empty($imageUrl)) {
            return false;
        }

        $url = $this->getImageUrl($imageUrl);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200) ? $data : false;
    }
}

?>
