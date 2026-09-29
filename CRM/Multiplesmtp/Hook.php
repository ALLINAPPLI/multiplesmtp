<?php
use CRM_Multiplesmtp_ExtensionUtil as E;


class CRM_Multiplesmtp_Hook {


  const SETTING_PREFIX = 'multiplesmtp_';


  private static bool $internalSend = FALSE;
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
    // >>> NOUVEAU CHAMP : forcer l'envoi des Donation Receipts
    'force_donrec' => [
      'label'       => 'Forcer l\'envoi des Donation Receipts via le SMTP transactionnel',
      'type'        => 'checkbox',
      'description' => 'Si l\'extension Donation Receipts (de.systopia.donrec) est installée, tous les reçus fiscaux seront envoyés via le SMTP transactionnel.',
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
    $prefix   = self::SETTING_PREFIX;

    // Vérifier si DonRec est installé
    $donrecInstalled = self::isDonRecInstalled();

    // Ajouter la case maîtresse "enabled"
    $fullKeyEnabled = $prefix . 'enabled';
    $form->addElement('checkbox', $fullKeyEnabled, '');
    $form->setDefaults([
      $fullKeyEnabled => (bool) $settings->get($fullKeyEnabled),
    ]);

    // Ajouter tous les autres champs
    foreach (self::$fields as $key => $info) {
      if ($key === 'enabled') {
        continue; // déjà traité
      }

      $fullKey = $prefix . $key;

      // Pour force_donrec : ne l'ajouter que si DonRec est installé
      if ($key === 'force_donrec' && !$donrecInstalled) {
        continue;
      }

      if ($info['type'] === 'radio') {
        $form->addYesNo($fullKey, $info['label'], FALSE, FALSE);
        $form->setDefaults([
          $fullKey => (int) $settings->get($fullKey),
        ]);
      }
      elseif ($info['type'] === 'checkbox') {
        $form->addElement('checkbox', $fullKey, $info['label']);
        $form->setDefaults([
          $fullKey => (bool) $settings->get($fullKey),
        ]);
      }
      elseif ($info['type'] === 'password') {
        $form->addElement('password', $fullKey, $info['label'],
          ['class' => 'crm-form-text', 'size' => 45, 'autocomplete' => 'off']
        );
        // Placeholder si un mot de passe existe déjà
        $currentValue = $settings->get($fullKey);
        if (!empty($currentValue)) {
          $form->getElement($fullKey)->updateAttributes(['placeholder' => '(mot de passe enregistré)']);
        }
      }
      else {
        $form->addElement('text', $fullKey, $info['label'],
          ['class' => 'crm-form-text', 'size' => 45]
        );
        $form->setDefaults([
          $fullKey => $settings->get($fullKey),
        ]);
      }
    }

    $form->addElement('hidden', 'multiplesmtp_is_visible', 0);
    $form->setDefaults(['multiplesmtp_is_visible' => 0]);

    $form->assign('smtpAltFields', self::$fields);
    $form->assign('smtpAltPrefix', self::SETTING_PREFIX);
    $form->assign('donrec_installed', $donrecInstalled);

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
    $donrecInstalled = self::isDonRecInstalled();

    // Sauvegarder chaque champ individuellement
    foreach (self::$fields as $key => $info) {
      $fullKey = $prefix . $key;
      $value   = $values[$fullKey] ?? NULL;

      if ($key === 'enabled') {
        // enabled : 1 si coché, 0 sinon
        $s->set($fullKey, !empty($value) ? 1 : 0);
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

      if ($key === 'force_donrec') {
        // Sauvegarder force_donrec seulement si DonRec est installé
        if ($donrecInstalled) {
          $s->set($fullKey, !empty($value) ? 1 : 0);
        }
        continue;
      }

      // Champs texte standards
      if ($value !== NULL) {
        $s->set($fullKey, $value);
      }
    }

    // Envoi du mail de test si demandé
    if (!empty($values['multiplesmtp_test'])) {
      self::sendTestEmail();
    }
  }


  public static bool $useAltMailerForNextSend = FALSE;
  private static bool $currentJobUseAltMailer = FALSE;


  public static function alterMailer(&$mailer, $driver, $params) {
    if (self::isNativeSmtpTestCall()) {
      return;
    }

    if (!($mailer instanceof CRM_Multiplesmtp_ProxyMailer)) {
      $mailer = new CRM_Multiplesmtp_ProxyMailer($mailer);
    }
  }


  private static function isNativeSmtpTestCall(): bool {
    foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20) as $frame) {
      if (($frame['class'] ?? NULL) === 'CRM_Admin_Form_Setting_Smtp'
        && ($frame['function'] ?? NULL) === 'sendTest') {
        return TRUE;
      }
    }
    return FALSE;
  }


  public static function onFlexMailerRun(\Civi\FlexMailer\Event\RunEvent $event): void {
    self::$currentJobUseAltMailer = FALSE;

    try {
      $job = $event->getJob();
      if (!$job || empty($job->id)) {
        return;
      }

      if (self::buildAlternativeMailer() === NULL) {
        return;
      }

      $limit = (int) (Civi::settings()->get('simple_mail_limit') ?? 0);
      if ($limit <= 0) {
        return;
      }

      $recipientCount = (int) CRM_Core_DAO::singleValueQuery(
        'SELECT COUNT(*) FROM civicrm_mailing_event_queue WHERE job_id = %1',
        [1 => [$job->id, 'Integer']]
      );

      self::$currentJobUseAltMailer = ($recipientCount > 0 && $recipientCount <= $limit);
    }
    catch (\Throwable $e) {
      Civi::log()->error('multiplesmtp: onFlexMailerRun a échoué : ' . $e->getMessage());
      self::$currentJobUseAltMailer = FALSE;
    }
  }


  public static function alterMailParams(&$params, $context = NULL) {
    try {
      if (self::$internalSend) {
        self::$useAltMailerForNextSend = FALSE;
        return;
      }

      if (self::buildAlternativeMailer() === NULL) {
        self::$useAltMailerForNextSend = FALSE;
        return;
      }

      $limit = (int) (Civi::settings()->get('simple_mail_limit') ?? 0);
      if ($limit <= 0) {
        self::$useAltMailerForNextSend = FALSE;
        return;
      }

      // -----------------------------------------------------------
      // >>> DÉTECTION DES EMAILS DONREC (REÇUS FISCAUX) <<<
      // -----------------------------------------------------------
      $isDonRec = FALSE;

      if (!empty($params['headers']['X400-Content-Identifier'])) {
        $headerValue = $params['headers']['X400-Content-Identifier'];
        if (is_string($headerValue) && stripos($headerValue, 'DONREC#') === 0) {
          $isDonRec = TRUE;
        }
      }

      if (!$isDonRec && !empty($params['subject'])) {
        $subject = strtolower($params['subject']);
        if (stripos($subject, 'zuwendungsbescheinigung') !== FALSE
          || stripos($subject, 'donation receipt') !== FALSE
        ) {
          $isDonRec = TRUE;
        }
      }

      // Si c'est un email DonRec, on vérifie le setting "force_donrec"
      if ($isDonRec) {
        $forceDonRec = (bool) Civi::settings()->get(self::SETTING_PREFIX . 'force_donrec');
        if ($forceDonRec) {
          self::$useAltMailerForNextSend = TRUE;
          return;
        }
      }
      // -----------------------------------------------------------

      $isMailingContext = in_array($context, ['civimail', 'flexmailer', 'testEmail'], TRUE)
        || !empty($params['headers']['List-Unsubscribe']);

      if ($isMailingContext) {
        self::$useAltMailerForNextSend = self::$currentJobUseAltMailer;
      }
      else {
        $recipientCount = self::countRecipients($params);
        self::$useAltMailerForNextSend = ($recipientCount > 0 && $recipientCount <= $limit);
      }
    }
    catch (\Throwable $e) {
      Civi::log()->error('multiplesmtp: alterMailParams a échoué : ' . $e->getMessage());
      self::$useAltMailerForNextSend = FALSE;
    }
  }


  private static function countRecipients(array $params): int {
    $blob = '';
    foreach (['toEmail', 'to', 'cc', 'bcc'] as $key) {
      if (!empty($params[$key])) {
        $blob .= ' ' . $params[$key];
      }
    }
    preg_match_all('/[^\s,;<>"]+@[^\s,;<>"]+/', $blob, $matches);
    $count = count(array_unique(array_map('strtolower', $matches[0])));
    return max($count, 1);
  }


  private static function isDonRecInstalled(): bool {
    try {
      $result = civicrm_api3('Extension', 'get', [
        'full_name' => 'de.systopia.donrec',
        'status'    => 'installed',
      ]);
      return !empty($result['values']);
    }
    catch (\Throwable $e) {
      return FALSE;
    }
  }


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


  public static function buildAlternativeMailerPublic() {
    return self::buildAlternativeMailer();
  }

  private static function buildAlternativeMailer() {
    $s = Civi::settings();

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