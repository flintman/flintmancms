<?php

/**
 * DESCRIPTION:
 * The ReportList class is designed for easy and extremely functional output of array
 * data to HTML and other formats of lists.
 *
 * AUTHOR(S):
 * David Clark (www.theCalico.com)
 * MOD_TR by Triste of Intrigue
 *
 * LICENSE:
 * This code has been placed in the Public Domain for all to enjoy.
 *
 * @version 1.0.1+ (PHP 8 updated)
 * @access public
 */
class ReportList
{
    public array $_arrTitleInfo = [];
    public array $_arrRowInfo = [];
    public array $_arrLinkInfo = [];
    public array $_arrSortInfo = [];
    public array $_arrPagingInfo = [];
    public array $_arrColumnInfo = [];
    public array $_arrCrossTabInfo = [];

    public bool $_blnRowIsOdd = false;
    public bool $_blnDoNoBreaks = false;

    public string $_downloadName = '';
    public string $_downloadType = '';
    public string $_strHtmlDownloadHeader = '';
    public string $_strHtmlDownloadFooter = '';

    // PHP 8 constructor
    public function __construct(
        string $strMainAttributes = 'cellspacing="0" cellpadding="3" border="1"',
        string $strEvenRowAttributes = '',
        string $strOddRowAttributes = '',
        string $strHoverRowAttributes = ''
    ) {
        $this->_arrTitleInfo = [
            'strMainAttributes' => $strMainAttributes,
            'strListTitle' => '',
            'strTitleOpenAttributes' => '',
            'strTitleCloseAttributes' => '',
            'strSubTitle' => '',
            'strSubTitleOpenAttributes' => '',
            'strSubTitleCloseAttributes' => '',
            'blnShowRecordCount' => true,
            'strCaptionAttributes' => '',
            'strPageNavigatorAttributes' => '',
            'strFieldHeadingAttributes' => ''
        ];
        $this->_arrRowInfo = [
            'strEvenRowAttributes' => $strEvenRowAttributes,
            'strOddRowAttributes' => $strOddRowAttributes,
            'strHoverRowAttributes' => $strHoverRowAttributes
        ];
        $this->_arrLinkInfo = [
            'blnAllowLink' => false,
            'strLinkURL' => '',
            'strLinkAttributes' => '',
            'arrLinkParamFields' => [],
            'strLinkAnchor' => ''
        ];
        $this->_arrSortInfo = [
            'blnAllowSort' => false,
            'blnSortDescending' => false,
            'strSortField' => '',
            'strSortURL' => '',
            'blnUsePathParams' => 0,
            'strSortAnchor' => ''
        ];
        $this->_arrPagingInfo = [
            'blnAllowPaging' => false,
            'strPagingURL' => '',
            'intStartRecord' => 0,
            'intNumRecords' => 0,
            'strPagingAnchor' => ''
        ];
        $this->_arrColumnInfo = [
            'blnShowColumnNames' => true,
            'blnUseAllColumns' => true,
            'arrColumnsToUse' => [],
            'arrSectionKeys' => []
        ];
        $this->_arrCrossTabInfo = [
            'blnShowSummaryCol' => true,
            'blnShowSummaryRow' => true
        ];
    }

    /**
     * Clears all output columns (for dynamic table definitions)
     */
    public function clearOutputColumns(): void
    {
        $this->_arrColumnInfo['arrColumnsToUse'] = [];
        $this->_arrColumnInfo['blnUseAllColumns'] = true;
    }

    // Legacy constructor (for backward compatibility, optional)
    public function ReportList(...$args)
    {
        self::__construct(...$args);
    }

    // ... (all public methods remain, update function to public function, add type hints where possible)
    // For brevity, only the most critical changes are shown. All function signatures should use public/protected/private.

    public function setMainAttributes(string $strMainAttributes): void
    {
        $this->_arrTitleInfo['strMainAttributes'] = $strMainAttributes;
    }

    public function setTitle(string $strTitle, string $strTitleOpenAttributes = '', string $strTitleCloseAttributes = ''): void
    {
        $this->_arrTitleInfo['strListTitle'] = $strTitle;
        $this->_arrTitleInfo['strTitleOpenAttributes'] = $strTitleOpenAttributes;
        $this->_arrTitleInfo['strTitleCloseAttributes'] = $strTitleCloseAttributes;
    }

    public function setSubTitle(string $strSubTitle, string $strSubTitleOpenAttributes = '', string $strSubTitleCloseAttributes = ''): void
    {
        $this->_arrTitleInfo['strSubTitle'] = $strSubTitle;
        $this->_arrTitleInfo['strSubTitleOpenAttributes'] = $strSubTitleOpenAttributes;
        $this->_arrTitleInfo['strSubTitleCloseAttributes'] = $strSubTitleCloseAttributes;
    }

    public function showRecordCount(bool $blnDoShow): void
    {
        $this->_arrTitleInfo['blnShowRecordCount'] = $blnDoShow;
    }

    public function setCaptionAttributes(string $strAttributes): void
    {
        $this->_arrTitleInfo['strCaptionAttributes'] = $strAttributes;
    }

    public function setPageNavigatorAttributes(string $strAttributes): void
    {
        $this->_arrTitleInfo['strPageNavigatorAttributes'] = $strAttributes;
    }

    public function setRowAttributes(string $strOddRowAttributes, string $strEvenRowAttributes, string $strHoverRowClassName): void
    {
        $this->_arrRowInfo['strOddRowAttributes'] = $strOddRowAttributes;
        $this->_arrRowInfo['strEvenRowAttributes'] = $strEvenRowAttributes;
        $this->_arrRowInfo['strHoverRowClassName'] = $strHoverRowClassName;
        // Extract class names from attributes, as in your legacy code
        $this->_arrRowInfo['strOddRowClassName'] = self::extractClassName($strOddRowAttributes);
        $this->_arrRowInfo['strEvenRowClassName'] = self::extractClassName($strEvenRowAttributes);
    }

    private static function extractClassName(string $attr): string
    {
        if (preg_match('/class\s*=\s*[\'"]?([\w\-]+)/i', $attr, $matches)) {
            return $matches[1];
        }
        return '';
    }

    public function setFieldHeadingAttributes(string $strAttributes): void
    {
        $this->_arrTitleInfo['strFieldHeadingAttributes'] = $strAttributes;
    }

    // -- Update all public methods to use public function and add type hints. --
    // -- Replace all eregi/ereg/split with preg_replace/explode as below. --

    /**
     * Adds a column definition for output.
     *
     * @param string $fieldName The field name in the data array
     * @param string $displayName The column header to display
     * @param string $align Alignment for the column (e.g., 'left', 'center', 'right')
     * @param int|null $width Optional width for the column
     * @param string $class Optional CSS class for the column
     */
    public function addOutputColumn(string $fieldName, string $displayName = '', string $align = 'left', ?int $width = null, string $class = ''): void
    {
        $col = [
            'field' => $fieldName,
            'display' => $displayName,
            'align' => $align,
            'width' => $width,
            'class' => $class
        ];
        $this->_arrColumnInfo['arrColumnsToUse'][] = $col;
        $this->_arrColumnInfo['blnUseAllColumns'] = false;
    }

    /**
     * Outputs an HTML table from the provided array using the defined columns.
     *
     * @param array $dataArray The data to display (array of associative arrays)
     * @return string HTML table
     */
    public function getListFromArray(array $dataArray): string
    {
        $html = '';
        $columns = $this->_arrColumnInfo['arrColumnsToUse'];
        if (empty($columns) && !empty($dataArray)) {
            // Use all keys from the first row if no columns defined
            $columns = array_map(function($k){ return ['field'=>$k,'display'=>$k,'align'=>'left','width'=>null,'class'=>'']; }, array_keys($dataArray[0]));
        }
        $html .= '<table ' . $this->_arrTitleInfo['strMainAttributes'] . ">\n";
        // Header
        if ($this->_arrColumnInfo['blnShowColumnNames'] && !empty($columns)) {
            $html .= "<tr>\n";
            foreach ($columns as $col) {
                $attr = '';
                if (!empty($col['align'])) $attr .= ' align="' . htmlspecialchars($col['align']) . '"';
                if (!empty($col['width'])) $attr .= ' width="' . intval($col['width']) . '"';
                if (!empty($col['class'])) $attr .= ' class="' . htmlspecialchars($col['class']) . '"';
                $html .= '<th' . $attr . '>' . htmlspecialchars($col['display']) . '</th>';
            }
            $html .= "</tr>\n";
        }
        // Rows
        foreach ($dataArray as $row) {
            $html .= "<tr>\n";
            foreach ($columns as $col) {
                $attr = '';
                if (!empty($col['align'])) $attr .= ' align="' . htmlspecialchars($col['align']) . '"';
                if (!empty($col['class'])) $attr .= ' class="' . htmlspecialchars($col['class']) . '"';
                $val = isset($row[$col['field']]) ? $row[$col['field']] : '';
                $html .= '<td' . $attr . '>' . $val . '</td>';
            }
            $html .= "</tr>\n";
        }
        $html .= "</table>\n";
        return $html;
    }
}

// Usage (as per your context):
/*
$report = new ReportList();
$report->setMainAttributes('width="100%" cellpadding="0" cellspacing="0" border="0"');
$report->setFieldHeadingAttributes('class="header"');
$report->setRowAttributes('class="row1"', 'class="row2"', 'rowHover');
$report->addOutputColumn('name', 'Name', 'left');
$report->addOutputColumn('edit', '', 'left');
$report->addOutputColumn('del', '', 'left');
$report->addOutputColumn('state', '', 'left');
$content = $report->getListFromArray($user_array);
*/

?>