<?php

require_once 'cdntaxreceipts.civix.php';
require_once 'cdntaxreceipts.functions.inc';
require_once 'cdntaxreceipts.db.inc';

use CRM_Cdntaxreceipts_ExtensionUtil as E;

define('CDNTAXRECEIPTS_MODE_BACKOFFICE', 1);
define('CDNTAXRECEIPTS_MODE_PREVIEW', 2);
define('CDNTAXRECEIPTS_MODE_WORKFLOW', 3);
define('CDNTAXRECEIPTS_MODE_API', 4);

/**
 * Implements hook_civicrm_buildForm().
 */
function cdntaxreceipts_civicrm_buildForm($formName, &$form) {
  if (is_a($form, 'CRM_Contribute_Form_ContributionView')) {
    // add "Issue Tax Receipt" button to the "View Contribution" page
    // if the Tax Receipt has NOT yet been issued -> display a white maple leaf icon
    // if the Tax Receipt has already been issued -> display a red maple leaf icon
    $contributionId = $form->get('id');

    if (isset($contributionId) && cdntaxreceipts_eligibleForReceipt($contributionId)) {
      Civi::resources()->addStyleFile('org.civicrm.cdntaxreceipts', 'css/civicrm_cdntaxreceipts.css');
      list($issued_on, $receipt_id) = cdntaxreceipts_issued_on($contributionId);
      $is_original_receipt = empty($issued_on);
      $subName = 'view_tax_receipt';

      if ($is_original_receipt) {
        $subName = 'issue_tax_receipt';
      }

      $buttons = [
        [
          'type' => 'cancel',
          'name' => ts('Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type'      => 'submit',
          'subName'   => $subName,
          'name'      => E::ts('Tax Receipt'),
          'isDefault' => FALSE,
          'icon'      => 'fa-check-square',
        ],
      ];

      $form->addButtons($buttons);
    }
  }
}

/**
 * Implementation of hook_civicrm_postProcess().
 *
 * Called when a form comes back for processing. Basically, we want to process
 * the button we added in cdntaxreceipts_civicrm_buildForm().
 */
function cdntaxreceipts_civicrm_postProcess($formName, &$form) {
  // First check whether I really need to process this form
  if (!is_a($form, 'CRM_Contribute_Form_ContributionView')) {
    return;
  }

  // Is it one of our tax receipt buttons?
  $buttonName = $form->controller->getButtonName();
  if ($buttonName !== '_qf_ContributionView_submit_issue_tax_receipt' && $buttonName !== '_qf_ContributionView_submit_view_tax_receipt') {
    return;
  }

  // the tax receipt button has been pressed.  redirect to the tax receipt 'view' screen, preserving context.
  $contributionId = $form->get('id');
  $contactId = $form->get('cid');

  $session = CRM_Core_Session::singleton();
  $session->pushUserContext(CRM_Utils_System::url('civicrm/contact/view/contribution',
    "reset=1&id=$contributionId&cid=$contactId&action=view&context=contribution&selectedChild=contribute"
  ));

  CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/cdntaxreceipts/view', "reset=1&id=$contributionId&cid=$contactId"));
}

/**
 * Implements hook_civicrm_searchTasks().
 *
 * For users with permission to issue tax receipts, give them the ability to do it
 * as a batch of search results.
 */
function cdntaxreceipts_civicrm_searchTasks($objectType, &$tasks) {
  if ($objectType == 'contribution' && CRM_Core_Permission::check('issue cdn tax receipts')) {
    $single_in_list = FALSE;
    $aggregate_in_list = FALSE;
    foreach ($tasks as $key => $task) {
      if ($task['class'] == 'CRM_Cdntaxreceipts_Task_IssueSingleTaxReceipts') {
        $single_in_list = TRUE;
      }
    }
    foreach ($tasks as $key => $task) {
      if ($task['class'] == 'CRM_Cdntaxreceipts_Task_IssueAggregateTaxReceipts') {
        $aggregate_in_list = TRUE;
      }
    }
    if (!$single_in_list) {
      $tasks[] = [
        'title' => E::ts('Issue Tax Receipts (Separate Receipt for Each Contribution)'),
        'class' => 'CRM_Cdntaxreceipts_Task_IssueSingleTaxReceipts',
        'result' => TRUE,
      ];
    }
    if (!$aggregate_in_list) {
      $tasks[] = [
        'title' => ts('Issue Tax Receipts (Combined Receipt with Total Contributed)'),
        'class' => 'CRM_Cdntaxreceipts_Task_IssueAggregateTaxReceipts',
        'result' => TRUE,
      ];
    }
  }
  elseif ($objectType == 'contact' && CRM_Core_Permission::check('issue cdn tax receipts')) {
    $annual_in_list = FALSE;
    foreach ($tasks as $key => $task) {
      if($task['class'] == 'CRM_Cdntaxreceipts_Task_IssueAnnualTaxReceipts') {
        $annual_in_list = TRUE;
      }
    }
    if (!$annual_in_list) {
      $tasks[] = [
        'title' => E::ts('Issue Annual Tax Receipts'),
        'class' => 'CRM_Cdntaxreceipts_Task_IssueAnnualTaxReceipts',
        'result' => TRUE,
      ];
    }
  }
}

/**
 * Implements hook_civicrm_permission().
 */
function cdntaxreceipts_civicrm_permission( &$permissions ) {
  $permissions['issue cdn tax receipts'] = [
    'label' => E::ts('CiviCRM CDN Tax Receipts: Issue Tax Receipts'),
    'description' => '',
  ];
}

/**
 * API should use the CDN Tax Receipts permission.
 */
function cdntaxreceipts_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  $permissions['cdntaxreceipts']['generate'] = ['issue cdn tax receipts'];
}

/**
 * Implements hook_civicrm_config().
 */
function cdntaxreceipts_civicrm_config(&$config) {
  _cdntaxreceipts_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 */
function cdntaxreceipts_civicrm_install() {
  // copy tables civicrm_cdntaxreceipts_log and civicrm_cdntaxreceipts_log_contributions IF they already exist
  // Issue: #1
  return _cdntaxreceipts_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 */
function cdntaxreceipts_civicrm_enable() {
  CRM_Core_Session::setStatus(E::ts('Configure the Tax Receipts extension at Administer >> CiviContribute >> CDN Tax Receipts.'));
  return _cdntaxreceipts_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * Add entries to the navigation menu, automatically removed on uninstall
 */
function cdntaxreceipts_civicrm_navigationMenu(&$params) {
  // Check that our item doesn't already exist
  $cdntax_search = ['url' => 'civicrm/cdntaxreceipts/settings?reset=1'];
  $cdntax_item = [];
  CRM_Core_BAO_Navigation::retrieve($cdntax_search, $cdntax_item);

  if (!empty($cdntax_item)) {
    return;
  }

  // Get the maximum key of $params using method mentioned in discussion
  // https://issues.civicrm.org/jira/browse/CRM-13803
  $navId = CRM_Core_DAO::singleValueQuery("SELECT max(id) FROM civicrm_navigation");
  if (is_integer($navId)) {
    $navId++;
  }
  // Find the Memberships menu
  foreach($params as $key => $value) {
    if ('Administer' == $value['attributes']['name']) {
      $parent_key = $key;
      foreach($value['child'] as $child_key => $child_value) {
        if ('CiviContribute' == $child_value['attributes']['name']) {
          $params[$parent_key]['child'][$child_key]['child'][$navId] = [
            'attributes' => [
              'label' => ts('CDN Tax Receipts',array('domain' => 'org.civicrm.cdntaxreceipts')),
              'name' => 'CDN Tax Receipts',
              'url' => 'civicrm/cdntaxreceipts/settings?reset=1',
              'permission' => 'access CiviContribute,administer CiviCRM',
              'operator' => 'AND',
              'separator' => 2,
              'parentID' => $child_key,
              'navID' => $navId,
              'active' => 1
            ],
          ];
        }
      }
    }
  }
}

function cdntaxreceipts_civicrm_validate($formName, &$fields, &$files, &$form) {
  if ($formName == 'CRM_Cdntaxreceipts_Form_Settings') {
    $errors = [];
    $allowed = ['gif', 'png', 'jpg', 'pdf'];
    foreach ($files as $key => $value) {
      if (CRM_Utils_Array::value('name', $value)) {
        $ext = pathinfo($value['name'], PATHINFO_EXTENSION);
        if (!in_array($ext, $allowed)) {
          $errors[$key] = E::ts('Please upload a valid file. Allowed extensions are (.gif, .png, .jpg, .pdf)');
        }
      }
    }
    return $errors;
  }
}

/**
 * Implements hook_civicrm_alterMailParams().
 */
function cdntaxreceipts_civicrm_alterMailParams(&$params, $context) {
  /*
    When CiviCRM core sends receipt email using CRM_Core_BAO_MessageTemplate, this hook was invoked twice:
    - once in CRM_Core_BAO_MessageTemplate::sendTemplate(), context "messageTemplate"
    - once in CRM_Utils_Mail::send(), which is called by CRM_Core_BAO_MessageTemplate::sendTemplate(), context "singleEmail"

    Hence, cdntaxreceipts_issueTaxReceipt() is called twice, sending 2 receipts to archive email.

    To avoid this, only execute this hook when context is "messageTemplate"
  */
  if ($context != 'messageTemplate') {
    return;
  }

  $msg_template_types = ['contribution_online_receipt', 'contribution_offline_receipt'];

  // Both of these are replaced by the same value of 'workflow' in 5.47
  $groupName = isset($params['groupName']) ? $params['groupName'] : (isset($params['workflow']) ? $params['workflow'] : '');
  $valueName = isset($params['valueName']) ? $params['valueName'] : (isset($params['workflow']) ? $params['workflow'] : '');
  if (($groupName == 'msg_tpl_workflow_contribution' || $groupName == 'contribution_online_receipt' || $groupName == 'contribution_offline_receipt')
      && in_array($valueName, $msg_template_types)) {

    // get the related contribution id for this message
    if (isset($params['tplParams']['contributionID'])) {
      $contribution_id = $params['tplParams']['contributionID'];
    }
    elseif (isset($params['contributionId'])) {
      $contribution_id = $params['contributionId'];
    }
    else {
      return;
    }

    // is the extension configured to send receipts attached to automated workflows?
    if (!Civi::settings()->get('attach_to_workflows')) {
      return;
    }

    // is this particular donation receiptable?
    if (!cdntaxreceipts_eligibleForReceipt($contribution_id)) {
      return;
    }

    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $contribution_id;
    $contribution->find(TRUE);

    $nullVar = NULL;
    list($ret, $method, $pdf_file) = cdntaxreceipts_issueTaxReceipt(
      $contribution,
      $nullVar,
      CDNTAXRECEIPTS_MODE_WORKFLOW
    );

    if ($ret) {
      $attachment = [
        'fullPath' => $pdf_file,
        'mime_type' => 'application/pdf',
        'cleanName' => basename($pdf_file),
      ];
      $params['attachments'] = [$attachment];
    }
  }
}

/**
 * On upgrade to 1.9 we try to determine the financial type for in-kind, but if
 * they had changed the name we won't be able to. So direct them to the settings
 * page to pick it manually.
 */
function cdntaxreceipts_civicrm_check(&$messages, $statusNames = [], $includeDisabled = FALSE) {
  if (!empty($statusNames) && !isset($statusNames['cdntaxreceiptsInkind'])) {
    return;
  }
  if (!$includeDisabled) {
    $disabled = \Civi\Api4\StatusPreference::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('is_active', '=', FALSE)
      ->addWhere('domain_id', '=', 'current_domain')
      ->addWhere('name', '=', 'cdntaxreceiptsInkind')
      ->execute()->count();
    if ($disabled) {
      return;
    }
  }
  // If we know the financial type, we're good.
  if (Civi::settings()->get('cdntaxreceipts_inkind')) {
    return;
  }
  // If the custom fields don't exist, then inkind was never set up, so we're
  // good.
  $inkind_custom = \Civi\Api4\CustomGroup::get(FALSE)
    ->addSelect('id')
    ->addWhere('name', '=', 'In_kind_donation_fields')
    ->execute()->first();
  if (empty($inkind_custom['id'])) {
    return;
  }
  $messages[] = new CRM_Utils_Check_Message(
    'cdntaxreceiptsInkind',
    '<p>'
    . E::ts('In-kind receipts appear to have been configured, but the financial type may have changed and it can not be determined automatically. Please visit <a %1>the settings page</a> and select the financial type being used for In-kind.', [1 => 'href="' . CRM_Utils_System::url('civicrm/cdntaxreceipts/settings', 'reset=1') . '"'])
    . '</p>',
    E::ts('In-kind financial type is unknown'),
    \Psr\Log\LogLevel::ERROR,
    'fa-money'
  );
}
