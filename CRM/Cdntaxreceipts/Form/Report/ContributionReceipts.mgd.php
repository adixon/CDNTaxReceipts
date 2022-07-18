<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return [
  [
    'name' => 'CRM_Cdntaxreceipts_Form_Report_ContributionReceipts',
    'entity' => 'ReportTemplate',
    'params' => [
      'version' => 3,
      'label' => 'CDN Tax-Receipted Contributions',
      'description' => 'Listing of CDN tax-receipted contributions with associated receipt details.',
      'class_name' => 'CRM_Cdntaxreceipts_Form_Report_ContributionReceipts',
      'report_url' => 'org.civicrm.cdntaxreceipts/contributionreceipts',
      'component' => 'CiviContribute',
    ],
  ],
];
