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

  // // Créer un listener SendBatchEvent 
  // Civi::dispatcher()->addListener(
  //   \Civi\FlexMailer\FlexMailer::EVENT_SEND,
  //   function(\Civi\FlexMailer\Event\SendBatchEvent $e) {
  //     $job = $e->getJob();
  //     // Uniquement les envois test (is_test = 1)
  //     if (empty($job->is_test)) {
  //       return;
  //     }
  //     // Remplacer le service pear_mail par le mailer alternatif
  //     $altMailer = CRM_Multiplesmtp_Hook::buildAlternativeMailerPublic();
  //     if ($altMailer) {
  //       \Civi::$statics['pear_mail_override'] = $altMailer;
  //     }
  //   },
  //   200 // priorité plus haute que DefaultSender
  // );

  // // restaurer le service original après l'envoi
  // \Civi::dispatcher()->addListener(
  //   'civi.flexmailer.send',
  //   function($e) {
  //     // Restaurer le mailer original après l'envoi
  //     \Civi::container()->set('pear_mail', \CRM_Utils_Mail::createMailer());
  //   },
  //   -999 // priorité très basse = après DefaultSender
  // );
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
    // LOG TEMPORAIRE — à supprimer après diagnostic
    // Civi::log()->debug('multiplesmtp_civicrm_alterMailParams TOUS LES MAILS: ' . print_r([
    //   'context'   => $context,
    //   'params' => $params,
    // ], TRUE));
    CRM_Multiplesmtp_Hook::alterMailParams($params, $context);
}
function  multiplesmtp_civicrm_alterMailer(&$mailer, $driver, $params) {
  // Civi::log()->debug('multiplesmtp_civicrm_alterMailer TOUS LES MAILS: ' . print_r([
  //     'mailer'   => $mailer,
  //     'driver' => $driver,
  //     'params'  => $params,
  //     'workflow'  => $params['workflow'] ?? 'ABSENT',
  //   ], TRUE));
    CRM_Multiplesmtp_Hook::alterMailer($mailer, $driver, $params);
}

// Dans multiplesmtp.php
function multiplesmtp_civicrm_postEmailSend($params) {
  CRM_Multiplesmtp_Hook::postEmailSend($params);
}