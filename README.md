# Multiple SMTP — User Guide


## What does this extension do?


By default, CiviCRM sends **all** emails (newsletters, donation receipts, event confirmations, reminders...) through a single SMTP server.


This extension adds a **second SMTP server**, dedicated to small ("transactional") sends, while your primary SMTP handles large "bulk" sends. This protects the reputation and deliverability of your primary SMTP.


## How does it decide which SMTP to use?


The extension uses CiviCRM's native **"Simple mail limit"** (`simple_mail_limit`), found under:


> **Administer > System Settings > Outbound Mail**


- **≤ limit** → email sent via **transactional SMTP** (second server).
- **> limit** → email sent via **primary SMTP** (native CiviCRM server).


This applies to all sends: CiviCRM mailings (test or normal), individual emails, and "Send an email" tasks.


### Special case: Donation Receipts


If the **Donation Receipts** extension (`de.systopia.donrec`) is installed, an additional option appears:


- **"Force sending of Donation Receipts via the transactional SMTP"**.


When checked, **all** donation receipts use the transactional SMTP, regardless of recipient count.


## Configuration


1. Go to **Administer > System Settings > Outbound Mail**.
2. Under primary SMTP settings, find **"Configure a transactional flow"**.
3. Check the box to enable.
4. Fill in:
   - **Transactional SMTP server** (e.g. `smtp.myprovider.com` or `ssl://smtp.myprovider.com`).
   - **Transactional SMTP port** (usually 25, 465, or 587).
   - **Authentication required** (Yes/No).
   - **Username** and **Password** (if required).
5. If Donation Receipts is installed, optionally check:
   - **"Force sending of Donation Receipts via the transactional SMTP"**.
6. Click **"Save & Test"** to send a test email via the transactional SMTP.
7. Ensure **"Simple mail limit"** is set — this is the switching threshold.


## Frequently asked questions


**What happens if I uncheck "Configure a transactional flow"?**  
All transactional SMTP settings are cleared.

**What if my transactional SMTP is down?**  
Use the test button to check connectivity. Actual sends will fail visibly (like any SMTP error).

**How do I know which SMTP was used?**  
Compare the mailing's recipient count against the `simple_mail_limit`.

**Why don't I see the "Force Donation Receipts" option?**  
It only appears if Donation Receipts (`de.systopia.donrec`) is installed and active.


--------------------------------------------------------------------------------------------------------------------------------------------------------------------


# Multiple SMTP — Guide utilisateur


## À quoi sert cette extension ?


Par défaut, CiviCRM envoie **tous** vos emails (newsletters, reçus de dons, confirmations, rappels...) via un seul serveur SMTP.


Cette extension ajoute un **second serveur SMTP**, dédié aux petits envois (« transactionnels »), tandis que votre SMTP principal gère les gros envois « en masse ». Cela préserve la réputation et la délivrabilité de votre SMTP principal.


## Comment ça choisit quel SMTP utiliser ?


L'extension utilise le réglage natif CiviCRM **« Nombre de destinataires maximum pour un envoi simple »** (`simple_mail_limit`), dans :


> **Administer > System Settings > Outbound Mail**


- **≤ limite** → email envoyé via le **SMTP transactionnel** (second serveur).
- **> limite** → email envoyé via le **SMTP principal** (serveur natif CiviCRM).


Cela s'applique à tous les envois : mailings CiviCRM (test ou normal), emails individuels, tâches « Envoyer un email ».


### Cas particulier : Donation Receipts


Si l'extension **Donation Receipts** (`de.systopia.donrec`) est installée, une option supplémentaire apparaît :


- **« Forcer l'envoi des Donation Receipts via le SMTP transactionnel »**.


Lorsqu'elle est cochée, **tous** les reçus fiscaux utilisent le SMTP transactionnel, indépendamment du nombre de destinataires.


## Configuration


1. Allez sur **Administer > System Settings > Outbound Mail**.
2. Sous les réglages du SMTP principal, trouvez **« Configurer un flux transactionnel »**.
3. Cochez la case pour activer.
4. Renseignez :
   - **Serveur SMTP transactionnel** (ex. `smtp.monfournisseur.com` ou `ssl://smtp.monfournisseur.com`).
   - **Port SMTP transactionnel** (généralement 25, 465 ou 587).
   - **Authentification requise** (Oui/Non).
   - **Nom d'utilisateur** et **Mot de passe** (si requis).
5. Si Donation Receipts est installé, vous pouvez optionnellement cocher :
   - **« Forcer l'envoi des Donation Receipts via le SMTP transactionnel »**.
6. Cliquez sur **« Enregistrer et tester »** pour envoyer un email de test via le SMTP transactionnel.
7. Vérifiez que **« Nombre de destinataires maximum pour un envoi simple »** est renseigné — c'est le seuil de basculement.


## Questions fréquentes


**Que se passe-t-il si je décoche « Configurer un flux transactionnel » ?**  
Tous les réglages du SMTP transactionnel sont effacés.

**Et si mon SMTP transactionnel est en panne ?**  
Utilisez le bouton de test pour vérifier la connexion. Les envois réels échoueront visiblement (comme toute erreur SMTP).

**Comment savoir quel SMTP a été utilisé ?**  
Comparez le nombre de destinataires du mailing avec la limite `simple_mail_limit`.

**Pourquoi je ne vois pas l'option « Forcer Donation Receipts » ?**  
Elle n'apparaît que si Donation Receipts (`de.systopia.donrec`) est installé et actif.



_______________________________________________________________________________________________________________________________________



# Multiple SMTP — Developer Guide


## Technical goal


CiviCRM builds its PEAR mailer (`pear_mail`) **once per request**, via `CRM_Utils_Mail::createMailer()`. There's no native mechanism to dynamically route to a different mailer per message. This extension hooks into two CiviCRM hooks and one FlexMailer event to achieve this.


## Architecture


### 1. `hook_civicrm_alterMailer` → `Hook::alterMailer()`


Enveloppe le mailer natif dans `CRM_Multiplesmtp_ProxyMailer` (sauf test natif) :


```php
public static function alterMailer(&$mailer, $driver, $params) {
  if (self::isNativeSmtpTestCall()) {
    return;
  }
  if (!($mailer instanceof CRM_Multiplesmtp_ProxyMailer)) {
    $mailer = new CRM_Multiplesmtp_ProxyMailer($mailer);
  }
}
```


### 2. `civi.flexmailer.run` → `Hook::onFlexMailerRun()`


Déclenché **une fois par job**, compte les destinataires :


```php
$recipientCount = (int) CRM_Core_DAO::singleValueQuery(
  'SELECT COUNT(*) FROM civicrm_mailing_event_queue WHERE job_id = %1',
  [1 => [$job->id, 'Integer']]
);
self::$currentJobUseAltMailer = ($recipientCount > 0 && $recipientCount <= $limit);
```


### 3. `hook_civicrm_alterMailParams` → `Hook::alterMailParams()`


Détermine si le SMTP transactionnel doit être utilisé :


```php
$isMailingContext = in_array($context, ['civimail', 'flexmailer', 'testEmail'], TRUE)
  || !empty($params['headers']['List-Unsubscribe']);

if ($isMailingContext) {
  self::$useAltMailerForNextSend = self::$currentJobUseAltMailer;
}
else {
  $recipientCount = self::countRecipients($params);
  self::$useAltMailerForNextSend = ($recipientCount > 0 && $recipientCount <= $limit);
}
```


#### Détection Donation Receipts


```php
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

if ($isDonRec) {
  $forceDonRec = (bool) Civi::settings()->get(self::SETTING_PREFIX . 'force_donrec');
  if ($forceDonRec) {
    self::$useAltMailerForNextSend = TRUE;
    return;
  }
}
```


Détection via header `X400-Content-Identifier` et fallback sujet.


### 4. `CRM_Multiplesmtp_ProxyMailer` — routage


```php
public function send($recipients, $headers, $body, $originalValues = []) {
  $target = $this->defaultMailer;
  if (CRM_Multiplesmtp_Hook::$useAltMailerForNextSend) {
    $alt = CRM_Multiplesmtp_Hook::buildAlternativeMailerPublic();
    if ($alt !== NULL) {
      $target = $alt;
    }
  }
  CRM_Multiplesmtp_Hook::$useAltMailerForNextSend = FALSE;
  return $target->send($recipients, $headers, $body, $originalValues);
}
```


### Stockage des réglages


| Clé | Type | Notes |
|---|---|---|
| `enabled` | checkbox | Si décochée : tous settings effacés. |
| `smtp_server` | text | |
| `smtp_port` | text | Défaut `587`. |
| `smtp_auth` | radio (0/1) | |
| `smtp_username` | text | |
| `smtp_password` | password | Chiffré (`CRM_Utils_Crypt` ou `base64`). |
| `force_donrec` | checkbox | Seulement si DonRec installé. |


### Gestion du formulaire


Champs injectés via `buildForm()`, `SmtpAltFields.tpl`, et `js/multiplesmtp.js`.


`force_donrec` ajouté seulement si `isDonRecInstalled()` :


```php
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
```


### Points d'attention


1. **`isNativeSmtpTestCall()`** : fragile aux changements CiviCRM.
2. **Pas de cache SMTP** : reconstruit à chaque envoi.
3. **`mailerJobsMax` > 1** : comptage par `job_id` seulement.
4. **Chiffrement mdp** : dépend de `CRM_Utils_Crypt`.
5. **Pas de settings déclarés** : via `Civi::settings()` sans métadonnées.
6. **Détection DonRec** : dépend du header `X400-Content-Identifier`.


### Points d'extension


- Nouveau contexte mailing : étendre le tableau dans `alterMailParams()`.
- Changer le seuil : modifier les appels `simple_mail_limit`.
- Nouveau champ SMTP : ajouter dans `Hook::$fields`.
- Modifier détection DonRec : mettre à jour header/sujet dans `alterMailParams()`.