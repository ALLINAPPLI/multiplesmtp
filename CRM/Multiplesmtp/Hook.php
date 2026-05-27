<?php
use CRM_Multiplesmtp_ExtensionUtil as E;

class CRM_Multiplesmtp_Hook {

  const SETTING_PREFIX = 'multiplesmtp_';

  private static $fields = [
    'smtp_server' => [
      'label'       => 'Serveur SMTP transactionnel',
      'type'        => 'text',
      'description' => 'Entrez le nom du serveur SMTP (machine), tel que "smtp.example.com". Si le serveur utilise SSL, ajoutez "ssl: //" au début du nom du serveur, tel que : "ssl://smtp.example.com".',
    ],
    'smtp_port' => [
      'label'       => 'Port SMTP transactionnel',
      'type'        => 'text',
      'description' => 'Les possibilités de port SMTP les plus courantes sont 25, 465 et 587. Vérifiez avec votre fournisseur de messagerie pour choisir le port approprié.',
    ],
    'smtp_auth' => [
      'label'       => 'Authentification requise',
      'type'        => 'radio',
      'description' => 'Votre SMTP requiert-il une authentification (nom + mot de passe) ?',
    ],
    'smtp_username' => [
      'label'       => 'Nom d\'utilisateur SMTP',
      'type'        => 'text',
      'description' => 'Nom d\'utilisateur fourni par votre prestataire SMTP transactionnel.',
    ],
    'smtp_password' => [
      'label'       => 'Mot de passe SMTP',
      'type'        => 'password',
      'description' => 'Si votre serveur SMTP transactionnel requiert une authentification, entrez votre nom et mot de passe ici.',
    ],
  ];

  // -------------------------------------------------------
  // 1. Injection des champs dans la page
  // -------------------------------------------------------
  public static function buildForm($formName, &$form) {
    if ($formName !== 'CRM_Admin_Form_Setting_Smtp') {
      return;
    }

    $settings = Civi::settings();

    foreach (self::$fields as $key => $info) {
      $fullKey      = self::SETTING_PREFIX . $key;
      $currentValue = $settings->get($fullKey);

      if ($info['type'] === 'radio') {
        // Boutons radio Oui / Non
        $form->addRadio(
          $fullKey,
          $info['label'],
          [1 => ts('Oui'), 0 => ts('Non')]
        );
        $form->setDefaults([$fullKey => $currentValue ?? 0]);
      }
      elseif ($info['type'] === 'checkbox') {
        $form->addElement('checkbox', $fullKey, $info['label']);
        $form->setDefaults([$fullKey => (bool) $currentValue]);
      }
      elseif ($info['type'] === 'password') {
        $form->addElement('password', $fullKey, $info['label'],
          ['class' => 'crm-form-text', 'size' => 45]
        );
        $form->setDefaults([$fullKey => $currentValue]);
      }
      else {
        $form->addElement('text', $fullKey, $info['label'],
          ['class' => 'crm-form-text', 'size' => 45]
        );
        $form->setDefaults([$fullKey => $currentValue]);
      }
    }

    // Champ hidden visibilité
    $form->addElement('hidden', 'multiplesmtp_is_visible', 0);
    $form->setDefaults(['multiplesmtp_is_visible' => 0]);

    $form->assign('smtpAltFields', self::$fields);
    $form->assign('smtpAltPrefix', self::SETTING_PREFIX);

    if($formName == 'CRM_Admin_Form_Setting_Smtp') {
        Civi::resources()->addScriptFile('multiplesmtp', 'js/smtp_alternatif.js');
        CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Multiplesmtp/SmtpAltFields.tpl',]);
    }
  }

  // -------------------------------------------------------
  // 2. Sauvegarde des champs
  // -------------------------------------------------------
  public static function postProcess($formName, &$form) {
    if ($formName !== 'CRM_Admin_Form_Setting_Smtp') {
      return;
    }

    $values    = $form->exportValues();
    $isVisible = !empty($values['multiplesmtp_is_visible']);

    // Si le bloc SMTP n'était pas visible, on ne sauvegarde rien
    if (!$isVisible) {
      return;
    }

    $s      = Civi::settings();
    $prefix = self::SETTING_PREFIX;

    foreach (self::$fields as $key => $info) {
      $fullKey = $prefix . $key;

      if ($key === 'smtp_password') {
        // Ne mettre à jour que si un nouveau mot de passe est saisi
        if (!empty($values[$fullKey])) {
          $s->set($fullKey, self::encryptPassword($values[$fullKey]));
        }
        continue;
      }

      $value = $values[$fullKey] ?? NULL;
      if ($value !== NULL) {
        $s->set($fullKey, $value);
      }
    }
  }

  // -------------------------------------------------------
  // 3. Interception et routage du SMTP
  // -------------------------------------------------------
  public static function alterMailParams(&$params, $context = NULL) {
    $isBulk = self::isBulkMailing($params, $context);
    Civi::log()->debug(" alterMailParams isBulk : ".print_r($isBulk,1));

    if ($isBulk) {
      // SMTP principal — comportement par défaut, on ne touche à rien
      return;
    }

    // Mail transactionnel → SMTP alternatif
    $altMailer = self::buildAlternativeMailer();
    Civi::log()->debug(" alterMailParams altMailer : ".print_r($altMailer,1));

    if ($altMailer !== NULL) {
      $params['mailer'] = $altMailer;
    }
  }

  // -------------------------------------------------------
  // Helpers privés
  // -------------------------------------------------------

  private static function isBulkMailing(&$params, $context) {
     // Contexte explicite (CiviCRM 5.x passe ce paramètre)
    if ($context === 'civimail') {
      Civi::log()->debug(" isBulkMailing : civimail");
      return TRUE;
    }

    // Vérifier le groupName dans les params
    if (!empty($params['groupName']) && stripos($params['groupName'], 'Mailing') !== FALSE) {
      Civi::log()->debug(" isBulkMailing : groupName Mailing");
      return TRUE;
    }

    // // Vérifier le header X-CiviMail-Bounce s'il est présent
    // if (!empty($params['headers']['X-CiviMail-Bounce'])) {
    //   Civi::log()->debug(" isBulkMailing : headers X-CiviMail-Bounce : ".print_r($params['headers']['X-CiviMail-Bounce'],1));
    //   return TRUE;
    // }

    // 🆕 Mosaico : il pose son propre header sur les previews et envois test
    if (!empty($params['headers']['X-Mosaico-Tracking'])) {
      Civi::log()->debug(" isBulkMailing : headers X-Mosaico-Tracking : ".print_r($params['headers']['X-Mosaico-Tracking'],1));
        return TRUE;
    }

    // 🆕 Mosaico : les envois test depuis l'éditeur ont ce groupName spécifique
    if (!empty($params['groupName']) && stripos($params['groupName'], 'mosaico') !== FALSE) {
        Civi::log()->debug(" isBulkMailing : groupName mosaico");
        return TRUE;
    }

    // 🆕 Mosaico stocke l'ID du template dans les params lors des previews
    if (!empty($params['templateId']) || !empty($params['mosaicoTemplateId'])) {
        Civi::log()->debug(" isBulkMailing : templateId mosaicoTemplateId");
        return TRUE;
    }

    // 🆕 Vérifier si le body contient la signature Mosaico
    // (envois test depuis l'éditeur visuel, avant job de mailing)
    if (!empty($params['html']) && strpos($params['html'], 'data-mosaico') !== FALSE) {
        Civi::log()->debug(" isBulkMailing : signature Mosaico");
        return TRUE;
    }

    // 🆕 Emails de test/preview de mailing (Mosaico et standard)
    // CiviCRM utilise ce groupName pour les envois test
    if (!empty($params['groupName']) &&
        stripos($params['groupName'], 'test') !== FALSE &&
        stripos($params['groupName'], 'Mailing') !== FALSE) {
          Civi::log()->debug(" isBulkMailing : groupName test ou Mailing");
        return TRUE;
    }

    return FALSE;
  }

  private static function buildAlternativeMailer() {
    $s = Civi::settings();

    $server   = $s->get(self::SETTING_PREFIX . 'smtp_server');
    $port     = $s->get(self::SETTING_PREFIX . 'smtp_port') ?: 587;
    $auth     = (bool) $s->get(self::SETTING_PREFIX . 'smtp_auth');
    $username = $s->get(self::SETTING_PREFIX . 'smtp_username');
    $password = $s->get(self::SETTING_PREFIX . 'smtp_password');

    if (empty($server)) {
      return NULL;
    }

    if (!empty($password)) {
      $password = self::decryptPassword($password);
    }

    $params = [
      'host'     => $server,
      'port'     => (int) $port,
      'auth'     => $auth,
      'username' => $username,
      'password' => $password,
    ];

    require_once 'Mail.php';
    return Mail::factory('smtp', $params);
  }

  private static function encryptPassword($plain) {
    if (class_exists('CRM_Utils_Crypt')) {
      return CRM_Utils_Crypt::encrypt($plain);
    }
    return base64_encode($plain);
  }

  private static function decryptPassword($encrypted) {
    if (class_exists('CRM_Utils_Crypt')) {
      return CRM_Utils_Crypt::decrypt($encrypted);
    }
    return base64_decode($encrypted);
  }
}

