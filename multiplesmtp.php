<?php

require_once 'multiplesmtp.civix.php';

use CRM_Multiplesmtp_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function multiplesmtp_civicrm_config(&$config): void {
  _multiplesmtp_civix_civicrm_config($config);

 
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function multiplesmtp_civicrm_install(): void {
  _multiplesmtp_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function multiplesmtp_civicrm_enable(): void {
  _multiplesmtp_civix_civicrm_enable();
}

/**
 * Ajoute les champs SMTP transactionnel sur la page de config SMTP
 */
function multiplesmtp_civicrm_buildForm($formName, &$form) {

  if($formName == 'CRM_Admin_Form_Setting_Smtp') {
      Civi::resources()->addScriptFile('multiplesmtp', 'js/multiplesmtp.js');
      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Multiplesmtp/SmtpAltFields.tpl',]);
  }

  CRM_Multiplesmtp_Hook::buildForm($formName, $form);
}

/**
 * Sauvegarde les valeurs des champs supplémentaires
 */
function multiplesmtp_civicrm_postProcess($formName, &$form) {
  CRM_Multiplesmtp_Hook::postProcess($formName, $form);
}

/**
 * Intercepte chaque envoi de mail pour choisir le bon SMTP
 */
function multiplesmtp_civicrm_alterMailParams(&$params, $context = NULL) {
  CRM_Multiplesmtp_Hook::alterMailParams($params, $context);
}
