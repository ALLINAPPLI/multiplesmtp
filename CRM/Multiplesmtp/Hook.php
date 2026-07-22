<?php
use CRM_Multiplesmtp_ExtensionUtil as E;

class CRM_Multiplesmtp_Hook {

  const SETTING_PREFIX = 'multiplesmtp_';

  private static bool $internalSend = FALSE;
  // Dans Hook.php
  private static bool $mailerWasSwapped = FALSE;

  public static function postEmailSend($params) {
    if (self::$mailerWasSwapped) {
      \Civi::container()->set('pear_mail', \CRM_Utils_Mail::createMailer());
      self::$mailerWasSwapped = FALSE;
    }
  }
  
  private static $fields = [
    'enabled' => [
      'label' => 'Configurer un flux transactionnel',
      'type'  => 'checkbox',
    ],
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

    $isEnabled = (bool) Civi::settings()->get(self::SETTING_PREFIX . 'enabled');
    $fullKeyEnabled = self::SETTING_PREFIX . 'enabled';
    $form->addElement('checkbox', $fullKeyEnabled, '');
    $form->setDefaults([$fullKeyEnabled => (bool) Civi::settings()->get($fullKeyEnabled)]);

    $settings = Civi::settings();
    // Civi::log()->debug('buildForm settings: ' . print_r($settings, TRUE));
    
    foreach (self::$fields as $key => $info) {
      $fullKey      = self::SETTING_PREFIX . $key;
      $currentValue = $settings->get($fullKey);
      // Civi::log()->debug('buildForm fullKey: ' . print_r($fullKey, TRUE));
      // Civi::log()->debug('buildForm currentValue: ' . print_r($currentValue, TRUE));

      if ($info['type'] === 'radio') {
        $form->addYesNo($fullKey, $info['label'], empty($props[$fullKey]['disabled']), FALSE, $props[$fullKey] ?? []);
        if ($currentValue == 1 && $fullKey == "multiplesmtp_smtp_auth") {
          // $form->setDefaults([$fullKey => (int) $currentValue]);
          if ($isEnabled) {
            $form->setDefaults([$fullKey => (int) $currentValue]);
          } else {
            $form->setDefaults([$fullKey => 0]); // champ vide si désactivé
          }
        }
        $form->assign('smtpAltDefaults', [
          'multiplesmtp_smtp_auth' => (int) Civi::settings()->get('multiplesmtp_smtp_auth'),
        ]);
      }
      elseif ($info['type'] === 'checkbox') {
        $form->addElement('checkbox', $fullKey, $info['label']);
        // $form->setDefaults([$fullKey => (bool) $currentValue]);
        if ($isEnabled) {
          $form->setDefaults([$fullKey => (bool) $currentValue]);
        } else {
          $form->setDefaults([$fullKey => 0]); // champ vide si désactivé
        }
      }
      elseif ($info['type'] === 'password') {
        $form->addElement('password', $fullKey, $info['label'],
          ['class' => 'crm-form-text', 'size' => 45, 'autocomplete' => 'off']
        );
        // Afficher un placeholder si un mot de passe existe déjà en DB
        if (!empty($currentValue)) {
          $form->getElement($fullKey)->updateAttributes(['placeholder' => '(mot de passe enregistré)']);
        }
      }
      else {
        $form->addElement('text', $fullKey, $info['label'],
          ['class' => 'crm-form-text', 'size' => 45]
        );
        // $form->setDefaults([$fullKey => $currentValue]);
        if ($isEnabled) {
          $form->setDefaults([$fullKey => $currentValue]);
        } else {
          $form->setDefaults([$fullKey => '']); // champ vide si désactivé
        }
      }
    }

    $form->addElement('hidden', 'multiplesmtp_is_visible', 0);
    $form->setDefaults(['multiplesmtp_is_visible' => 0]);

    $form->assign('smtpAltFields', self::$fields);
    $form->assign('smtpAltPrefix', self::SETTING_PREFIX);

    if ($formName == 'CRM_Admin_Form_Setting_Smtp') {
      Civi::resources()->addScriptFile('multiplesmtp', 'js/multiplesmtp.js');
      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Multiplesmtp/SmtpAltFields.tpl']);
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
    $s         = Civi::settings();
    $prefix    = self::SETTING_PREFIX;

    // ── Vérifier si la case est cochée ──────────────────────────────────
    // La checkbox peut arriver comme '1', 1, ou être absente si décochée
    $isEnabled = !empty($values[$prefix . 'enabled']);

    // Case décochée → effacer TOUS les settings et sortir
    if (!$isEnabled) {
      foreach (array_keys(self::$fields) as $key) {
        $s->set($prefix . $key, NULL);
      }
      $s->set($prefix . 'enabled', NULL);
      return;
    }

    // Case cochée → sauvegarder (pas besoin de vérifier is_visible)
    // is_visible servait à détecter si le SMTP principal était affiché,
    // mais ce n'est pas nécessaire pour les settings alternatifs.
    foreach (self::$fields as $key => $info) {
      $fullKey = $prefix . $key;
      $value   = $values[$fullKey] ?? NULL;

      if ($key === 'enabled') {
        $s->set($fullKey, 1);
        continue;
      }

      if ($key === 'smtp_auth') {
        if ($value !== NULL) {
          $s->set($fullKey, (int) $value);
        }
        continue;
      }

      if ($key === 'smtp_password') {
        $newPlain = $values[$fullKey] ?? '';
        if (!empty($newPlain)) {
          $s->set($fullKey, self::encryptPassword($newPlain));
        }
        // Si vide → on garde la valeur DB existante
        continue;
      }

      if ($value !== NULL) {
        $s->set($fullKey, $value);
      }
    }

    if (!empty($values['multiplesmtp_test'])) {
      self::sendTestEmail();
    }
  }

  public static bool $useAltMailerForNextSend = FALSE;

  /**
   * hook_civicrm_alterMailer : n'intervient qu'une fois par requête,
   * au moment où CiviCRM construit son mailer par défaut.
   */
  public static function alterMailer(&$mailer, $driver, $params) {
    if (!($mailer instanceof CRM_Multiplesmtp_ProxyMailer)) {
      $mailer = new CRM_Multiplesmtp_ProxyMailer($mailer);
    }
  }

  public static function alterMailParams(&$params, $context = NULL) {
    if (self::$internalSend) {
      self::$useAltMailerForNextSend = FALSE;
      return;
    }

    $isRealMailing = in_array($context, ['civimail', 'flexmailer', 'testEmail'], TRUE)
      || !empty($params['headers']['List-Unsubscribe']);

    // On ne fait QUE positionner le flag ; le proxy mailer s'occupe du routage réel.
    self::$useAltMailerForNextSend = !$isRealMailing
      && self::buildAlternativeMailer() !== NULL;
  }


  // -------------------------------------------------------
  // Envoi du mail de test (depuis le formulaire)
  // -------------------------------------------------------
  private static function sendTestEmail() {
    $userEmail = CRM_Core_Session::singleton()->getLoggedInContactEmail();

    if (empty($userEmail)) {
      CRM_Core_Session::setStatus(
        ts('Impossible de trouver votre adresse email.'),
        ts('Erreur'),
        'error'
      );
      return;
    }

    $mailer = self::buildAlternativeMailer();

    if ($mailer === NULL) {
      CRM_Core_Session::setStatus(
        ts('Le SMTP transactionnel n\'est pas configuré.'),
        ts('Erreur'),
        'error'
      );
      return;
    }

    $siteName = Civi::settings()->get('site_name') ?: 'CiviCRM';
    $from     = Civi::settings()->get('fromEmailAddress') ?: 'no-reply@example.com';

    $headers = [
      'From'         => $from,
      'To'           => $userEmail,
      'Subject'      => ts('Test SMTP transactionnel - %1', [1 => $siteName]),
      'Content-Type' => 'text/html; charset=UTF-8',
      'Date'         => date('r'),
      'Message-ID'   => '<' . uniqid('multiplesmtp_') . '@' . php_uname('n') . '>',
    ];

    $body = "
      <html>
      <body>
        <p>" . ts('Bonjour,') . "</p>
        <p>" . ts('Ceci est un email de test envoyé via le <strong>SMTP transactionnel</strong> configuré dans votre extension Multiple SMTP.') . "</p>
        <p>" . ts('Si vous recevez cet email, la configuration est correcte.') . "</p>
        <hr>
        <p><small>
          " . ts('Serveur : %1', [1 => Civi::settings()->get('multiplesmtp_smtp_server')]) . "<br>
          " . ts('Port : %1', [1 => Civi::settings()->get('multiplesmtp_smtp_port')]) . "<br>
          " . ts('Envoyé le : %1', [1 => date('d/m/Y H:i:s')]) . "
        </small></p>
      </body>
      </html>
    ";

    self::$internalSend = TRUE;
    try {
      $result = $mailer->send($userEmail, $headers, $body);
    }
    finally {
      self::$internalSend = FALSE;
    }

    if ($result === TRUE || !is_a($result, 'PEAR_Error')) {
      CRM_Core_Session::setStatus(
        ts('Email de test envoyé avec succès à %1 via le SMTP transactionnel.', [1 => $userEmail]),
        ts('Succès'),
        'success'
      );
    }
    else {
      CRM_Core_Session::setStatus(
        ts('Échec de l\'envoi : %1', [1 => $result->getMessage()]),
        ts('Erreur SMTP transactionnel'),
        'error'
      );
    }
  }

  // -------------------------------------------------------
  // Helpers
  // -------------------------------------------------------
  public static function buildAlternativeMailerPublic() {
    return self::buildAlternativeMailer();
  }
  private static function buildAlternativeMailer() {
    $s = Civi::settings();

    // Vérifier que l'extension est activée
    if (!$s->get(self::SETTING_PREFIX . 'enabled')) {
      return NULL;
    }

    $server   = $s->get(self::SETTING_PREFIX . 'smtp_server');
    $port     = $s->get(self::SETTING_PREFIX . 'smtp_port') ?: 587;
    $auth     = (bool) $s->get(self::SETTING_PREFIX . 'smtp_auth');
    $username = $s->get(self::SETTING_PREFIX . 'smtp_username');
    $password = $s->get(self::SETTING_PREFIX . 'smtp_password');

    if (empty($server)) {
      return NULL;
    }

    if (!empty($password)) {
      $password = self::decryptPasswordPublic($password);
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

  private static function encryptPassword(string $plain): string {
    if (class_exists('CRM_Utils_Crypt')) {
      return CRM_Utils_Crypt::encrypt($plain);
    }
    return base64_encode($plain);
  }

  public static function decryptPasswordPublic(string $encrypted): string {
    if (class_exists('CRM_Utils_Crypt')) {
      return CRM_Utils_Crypt::decrypt($encrypted);
    }
    return base64_decode($encrypted);
  }
}