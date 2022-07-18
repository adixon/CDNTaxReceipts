<?php
use CRM_Cdntaxreceipts_ExtensionUtil as E;

class CRM_Cdntaxreceipts_Form_Report_ContributionReceipts extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = array('Contact','Contribution');
  protected $_customGroupGroupBy = FALSE; function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'sort_name' => array(
            'title' => E::ts('Contact Name'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'first_name' => array(
            'title' => E::ts('First Name'),
            'no_repeat' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'last_name' => array(
            'title' => E::ts('Last Name'),
            'no_repeat' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' => array(
          'sort_name' => array(
            'title' => E::ts('Contact Name'),
            'operator' => 'like',
          ),
          'id' => array(
            'no_display' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'total_amount' => array(
            'title' => ts('Contribution Amount'),
          ),
          'receive_date' => array(
            'title' => ts('Receive Date'),
          ),
	  'contribution_status_id' => array(
            'title' => ts('Donation Status'),
          ),
        ),
	'order_bys' => array(
          'receive_date' => array(
            'title' => ts('Receive Date'),
          ),
        ),
        'filters' => array(
          'total_amount' => array(
            'title' => ts('Total Amount'),
            'operatorType' => CRM_Report_Form::OP_FLOAT,
            'type' => CRM_Utils_Type::T_FLOAT,
          ),
          'receive_date' => array(
            'title' => ts('Receive Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'contribution_status_id' => array(
	    'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
            'default' => [1],
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
      ),
      'civicrm_address' => array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => array(
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' => array('title' => E::ts('State/Province')),
          'country_id' => array('title' => E::ts('Country')),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array('email' => NULL),
        'grouping' => 'contact-fields',
      ),
      'civicrm_cdntaxreceipts_log' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
          'issued_on' => array('title' => 'Issued On', 'default' => TRUE,'type' => CRM_Utils_Type::T_TIMESTAMP,),
          'location_issued' => array('title' => 'Location Issued', 'default' => FALSE,),
          'receipt_amount' => array('title' => 'Receipt Amount', 'default' => TRUE, 'type' => CRM_Utils_Type::T_MONEY,),
          'receipt_no' => array('title' => 'Receipt No.', 'default' => TRUE),
          'issue_type' => array('title' => 'Issue Type', 'default' => TRUE),
          'issue_method' => array('title' => 'Issue Method', 'default' => TRUE),
          'uid' => array('title' => 'Issued By', 'default' => TRUE, 'type' => CRM_Utils_Type::T_INT),
          'receipt_status' => array('title' => 'Receipt Status', 'default' => TRUE,),
          'email_opened' => array('title' => 'Email Open Date', 'type' => CRM_Utils_Type::T_TIMESTAMP, 'default' => TRUE),
        ),
        'grouping' => 'tax-fields',
        'filters' =>
        array(
          'issued_on' =>
          array(
            'title' => 'Issued On',
            'type' => CRM_Utils_Type::T_TIMESTAMP,
            'operatorType' => CRM_Report_Form::OP_DATE),
          'location_issued' =>
          array(
            'title' => 'Location Issued',
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'issue_type' =>
            array(
              'title' => ts('Issue Type'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => array('single' => ts('Single'), 'annual' => ts('Annual'), 'aggregate' => ts('Aggregate')),
              'type' => CRM_Utils_Type::T_STRING,
            ),
          'issue_method' =>
            array(
            'title' => ts('Issue Method', array('domain' => 'org.civicrm.cdntaxreceipts')),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => array('email' => 'Email', 'print' => 'Print'),
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'receipt_status' =>
            array(
              'title' => ts('Receipt Status', array('domain' => 'org.civicrm.cdntaxreceipts')),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => array('issued' => 'Issued', 'cancelled' => 'Cancelled'),
              'type' => CRM_Utils_Type::T_STRING,
            ),
          'email_opened' =>
          array('title' => ts('Email Open Date', array('domain' => 'org.civicrm.cdntaxreceipts')),
            'type' => CRM_Utils_Type::T_DATE,
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
        ),
        'order_bys' =>
        array(
          'issued_on' =>
            array(
              'title' => 'Issued On', 'default' => '1', 'default_weight' => '0', 'default_order' => 'DESC',
            ),
          'receipt_no' =>
            array(
              'title' => ts('Receipt No.', array('domain' => 'org.civicrm.cdntaxreceipts')),
            ),
          'receipt_amount' =>
            array(
              'title' => ts('Receipt Amount', array('domain' => 'org.civicrm.cdntaxreceipts')),
            ),
          'receipt_status' =>
            array(
              'title' => ts('Receipt Status', array('domain' => 'org.civicrm.cdntaxreceipts')),
            ),
        ),
      ),
      'civicrm_cdntaxreceipts_log_contributions' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
          'contribution_id' => array(
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_INT,
           ),
        ),
        'grouping' => 'tax-fields',
      ),
    );
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', E::ts('CDN Tax-Receipted Contributions'));
    parent::preProcess();
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
      FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
        INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
        ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id
        AND {$this->_aliases['civicrm_contribution']}.is_test = 0
        AND {$this->_aliases['civicrm_contribution']}.is_template = 0)
        INNER JOIN cdntaxreceipts_log_contributions {$this->_aliases['civicrm_cdntaxreceipts_log_contributions']}
                ON {$this->_aliases['civicrm_cdntaxreceipts_log_contributions']}.contribution_id = {$this->_aliases['civicrm_contribution']}.id
        LEFT JOIN cdntaxreceipts_log {$this->_aliases['civicrm_cdntaxreceipts_log']}
		ON {$this->_aliases['civicrm_cdntaxreceipts_log']}.id = {$this->_aliases['civicrm_cdntaxreceipts_log_contributions']}.receipt_id
       ";
    $this->joinAddressFromContact();
    $this->joinEmailFromContact();
  }

  /**
   * Add field specific select alterations.
   *
   * @param string $tableName
   * @param string $tableKey
   * @param string $fieldName
   * @param array $field
   *
   * @return string
   */
  function selectClause(&$tableName, $tableKey, &$fieldName, &$field) {
    return parent::selectClause($tableName, $tableKey, $fieldName, $field);
  }

  /**
   * Add field specific where alterations.
   *
   * This can be overridden in reports for special treatment of a field
   *
   * @param array $field Field specifications
   * @param string $op Query operator (not an exact match to sql)
   * @param mixed $value
   * @param float $min
   * @param float $max
   *
   * @return null|string
   */
  public function whereClause(&$field, $op, $value, $min, $max) {
    return parent::whereClause($field, $op, $value, $min, $max);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();
    foreach ($rows as $rowNum => $row) {

      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row
        $repeatFound = FALSE;
        foreach ($row as $colName => $colVal) {
          if (CRM_Utils_Array::value($colName, $checkList) &&
            is_array($checkList[$colName]) &&
            in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
            $repeatFound = TRUE;
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = E::ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      if (!$entryFound) {
        break;
      }
    }
  }

}
