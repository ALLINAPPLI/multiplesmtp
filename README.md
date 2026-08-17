# Multiple SMTP — User Guide

## What does this extension do?

By default, CiviCRM sends **all** your emails (newsletters, donation receipts, event confirmations, reminders...) through a single SMTP server.

This extension lets you add a **second SMTP server**, dedicated to small ("transactional") sends, alongside your usual primary SMTP server (dedicated to large "bulk" sends). The goal: protect the reputation and deliverability of your primary SMTP by only using it for genuine bulk sends, while routing small sends through a separate channel.

## How does it decide which SMTP to use?

The rule is simple and relies on a setting that already exists natively in CiviCRM, **"Simple mail limit"** (`simple_mail_limit`), found under:

> **Administer > System Settings > Outbound Mail**

- If the number of recipients for a send is **less than or equal to** this limit → the email goes out via the **transactional SMTP** (the second server configured by this extension).
- If the number of recipients is **greater than** this limit → the email goes out via the **primary SMTP** (the one configured natively by CiviCRM).

This rule applies to **all sends**:
- CiviCRM mailings (classic and Mosaico), whether it's a **test send** or a **normal send**.
- Individual emails (contribution receipts, event confirmations, etc.).
- The "Send an email" task on a selection of contacts.

**Deliberate exception:** the two test buttons found on the *Administer > System Settings > Outbound Mail* page (the native test for the primary SMTP, and the test for our transactional SMTP) are **never** affected by this rule: each one always tests exactly the server it's supposed to test, with no interference.

**If no limit is configured** (field left empty or set to 0): all sends keep using the primary SMTP, as if the extension didn't exist — CiviCRM's default behavior is unchanged.

## Configuration

1. Go to **Administer > System Settings > Outbound Mail**.
2. Below the primary SMTP settings, a new section appears: **"Configure a transactional flow"**.
3. Check the box to enable the feature.
4. Fill in:
   - **Transactional SMTP server**: your server's address (e.g. `smtp.myprovider.com`). Prefix it with `ssl://` if your server requires it (e.g. `ssl://smtp.myprovider.com`).
   - **Transactional SMTP port**: usually 25, 465, or 587 depending on your provider.
   - **Authentication required**: Yes/No depending on whether your server requires credentials.
   - **Username** and **Password** for SMTP (if authentication is required). The password is stored encrypted.
5. Click **"Save & Test"** to validate your configuration: a test email is sent to your own address via the transactional SMTP.
6. Don't forget to fill in (or check) the **"Simple mail limit"** field further up on the same page — that's the number used as the switching threshold.

## Frequently asked questions

**I unchecked "Configure a transactional flow" — what happens to my settings?**
All transactional SMTP settings (server, port, credentials...) are cleared. You'll need to start over if you re-enable it later.

**What if my transactional SMTP is down?**
The test button on the settings page lets you check the connection at any time. If an error occurs during an actual send, the email isn't silently lost — it fails, like any other SMTP failure in CiviCRM.

**How do I know a mailing went out through the right SMTP?**
There's no direct visual indicator in the interface. The simplest way is to compare the number of recipients of the mailing against the limit configured under *Outbound Mail*.

--------------------------------------------------------------------------------------------------------------------------------------------------------------------

# Multiple SMTP — Guide utilisateur

## À quoi sert cette extension ?

Par défaut, CiviCRM envoie **tous** vos emails (newsletters, reçus de dons, confirmations d'événements, rappels...) via un seul et même serveur SMTP.

Cette extension permet d'ajouter un **second serveur SMTP**, dédié aux petits envois (« transactionnels »), en plus de votre SMTP principal habituel (dédié aux gros envois « en masse »/« bulk »). L'objectif : préserver la réputation et la délivrabilité de votre SMTP principal en ne l'utilisant que pour les vrais envois de masse, et faire passer les petits envois par un canal séparé.

## Comment ça choisit quel SMTP utiliser ?

La règle est simple et s'appuie sur un réglage déjà présent nativement dans CiviCRM, **« Nombre de destinataires maximum pour un envoi simple »** (`simple_mail_limit`), que vous trouvez dans :

> **Administer > System Settings > Outbound Mail**

- Si le nombre de destinataires d'un envoi est **inférieur ou égal** à cette limite → l'email part via le **SMTP transactionnel** (le second serveur, configuré par cette extension).
- Si le nombre de destinataires est **supérieur** à cette limite → l'email part via le **SMTP principal** (celui configuré nativement par CiviCRM).

Cette règle s'applique à **tous les envois** :
- Les mailings CiviCRM (classiques et Mosaico), qu'il s'agisse d'un **envoi de test** ou d'un **envoi normal**.
- Les emails individuels (reçus de contribution, confirmations d'événement, etc.).
- La tâche « Envoyer un email » sur une sélection de contacts.

**Exception volontaire :** les deux boutons de test présents sur la page *Administer > System Settings > Outbound Mail* (le test natif du SMTP principal, et le test de notre SMTP transactionnel) ne sont **jamais** concernés par cette règle : chacun teste toujours exactement le serveur qu'il est censé tester, sans interférence.

**Si aucune limite n'est configurée** (champ vide ou à 0) : tous les envois continuent d'utiliser le SMTP principal, comme si l'extension n'existait pas — le comportement de CiviCRM reste inchangé par défaut.

## Configuration

1. Allez sur **Administer > System Settings > Outbound Mail**.
2. Sous les réglages du SMTP principal, une nouvelle section apparaît : **« Configurer un flux transactionnel »**.
3. Cochez la case pour activer la fonctionnalité.
4. Renseignez :
   - **Serveur SMTP transactionnel** : l'adresse de votre serveur (ex. `smtp.monfournisseur.com`). Ajoutez `ssl://` devant si votre serveur l'exige (ex. `ssl://smtp.monfournisseur.com`).
   - **Port SMTP transactionnel** : généralement 25, 465 ou 587 selon votre fournisseur.
   - **Authentification requise** : Oui/Non selon si votre serveur demande un identifiant.
   - **Nom d'utilisateur** et **Mot de passe** SMTP (si authentification requise). Le mot de passe est stocké chiffré.
5. Cliquez sur **« Enregistrer et tester »** pour valider votre configuration : un email de test est envoyé à votre propre adresse via le SMTP transactionnel.
6. N'oubliez pas de renseigner (ou vérifier) le champ **« Nombre de destinataires maximum pour un envoi simple »** plus haut sur la même page — c'est ce nombre qui sert de seuil de basculement.

## Questions fréquentes

**J'ai décoché la case « Configurer un flux transactionnel », que se passe-t-il à mes réglages ?**
Tous les réglages du SMTP transactionnel (serveur, port, identifiants...) sont effacés. Vous repartez de zéro si vous réactivez plus tard.

**Et si mon SMTP transactionnel est en panne ?**
Le test depuis la page de configuration vous permet de vérifier la connexion à tout moment. En cas d'erreur au moment d'un envoi réel, l'email n'est pas silencieusement perdu : il tombe en erreur, comme n'importe quel échec SMTP dans CiviCRM.

**Comment je sais qu'un mailing est bien parti par le bon SMTP ?**
Il n'y a pas d'indicateur visuel direct dans l'interface. Le plus simple est de comparer le nombre de destinataires du mailing avec la limite configurée dans *Outbound Mail*.


_______________________________________________________________________________________________________________________________________


# Multiple SMTP — Developer Guide

## Technical goal

CiviCRM only builds its PEAR mailer (`pear_mail`) **once per request**, via `CRM_Utils_Mail::createMailer()`. There's no native mechanism to dynamically route to a different mailer per message depending on context. This extension fills that gap by hooking into two CiviCRM hooks and one FlexMailer event.

## Architecture

```
multiplesmtp.php                    ← hook declarations + FlexMailer listener
CRM/Multiplesmtp/Hook.php           ← all business logic (form, routing, settings)
CRM/Multiplesmtp/ProxyMailer.php    ← "façade" mailer that actually routes the send
templates/CRM/Multiplesmtp/SmtpAltFields.tpl  ← fields injected into the native SMTP form
js/multiplesmtp.js                  ← conditional field display (show/hide based on the enabled checkbox)
```

### 1. `hook_civicrm_alterMailer` → `Hook::alterMailer()`

Fired when CiviCRM builds its default mailer (`_createMailer()`). We **wrap** the native mailer object in `CRM_Multiplesmtp_ProxyMailer`, except in one specific case (see below). All the logic for choosing the destination (primary vs. transactional) is then delegated to this proxy at the moment of the actual send.

```php
public static function alterMailer(&$mailer, $driver, $params) {
  if (self::isNativeSmtpTestCall()) {
    return; // don't wrap, see below
  }
  if (!($mailer instanceof CRM_Multiplesmtp_ProxyMailer)) {
    $mailer = new CRM_Multiplesmtp_ProxyMailer($mailer);
  }
}
```

**Excluding the native SMTP test** (`isNativeSmtpTestCall()`):
The native "Save & Send Test Email" button on *Administer > System Settings > Outbound Mail* calls `CRM_Utils_Mail::_createMailer()` (which fires `alterMailer`), then `CRM_Utils_Mail::sendTest()`, which calls `$mailer->send()` **directly**, without going back through `CRM_Utils_Mail::send()` — so it **never triggers `alterMailParams()`**. If this mailer were wrapped in our `ProxyMailer`, it would consult the static flag `$useAltMailerForNextSend`, which would hold a **stale** value left over from an unrelated previous send. So we detect this call via `debug_backtrace()` (looking for the `CRM_Admin_Form_Setting_Smtp::sendTest` frame) and return without wrapping the mailer — it stays the raw native mailer, testing exactly the config shown in the form.

⚠️ This backtrace-based detection is fragile against internal CiviCRM core changes. Worth re-checking after major version upgrades.

### 2. `civi.flexmailer.run` → `Hook::onFlexMailerRun()`

Registered in `multiplesmtp_civicrm_config()`:

```php
Civi::dispatcher()->addListener(
  \Civi\FlexMailer\FlexMailer::EVENT_RUN,
  ['CRM_Multiplesmtp_Hook', 'onFlexMailerRun'],
  200
);
```

This event fires **once per mailing job processed** (for both test sends and normal sends), **before** the job's messages are actually sent. At this point, the CiviMail workflow guarantees `civicrm_mailing_event_queue` is already fully populated for this job (a job only moves to `Running` status once its entire recipient queue has been built) — so we can reliably count actual recipients:

```php
$recipientCount = (int) CRM_Core_DAO::singleValueQuery(
  'SELECT COUNT(*) FROM civicrm_mailing_event_queue WHERE job_id = %1',
  [1 => [$job->id, 'Integer']]
);
self::$currentJobUseAltMailer = ($recipientCount > 0 && $recipientCount <= $limit);
```

The result is stored in the static property `$currentJobUseAltMailer`, later consumed by `alterMailParams()` for **every** recipient of that job (a single calculation per job, not per recipient — avoids `N` redundant SQL queries).

**Known limitation**: if CiviCRM splits a mailing into several parallel child jobs (`mailerJobsMax` > 1), each child job is counted independently — the threshold then applies per sub-job, not to the mailing's total. This is a rare case (default config = a single job), but worth documenting if the client enables parallelism.

Counting is done by `job_id`, not `mailing_id`, on purpose: counting by `mailing_id` would artificially inflate the total for a test send on a mailing that's already been partially sent in bulk (the real job's queue rows would add up with the test job's rows).

### 3. `hook_civicrm_alterMailParams` → `Hook::alterMailParams()`

Fired right before each individual send (one call per recipient). Determines, for **this** specific message, whether it should use the transactional SMTP:

```php
$isMailingContext = in_array($context, ['civimail', 'flexmailer', 'testEmail'], TRUE)
  || !empty($params['headers']['List-Unsubscribe']);

if ($isMailingContext) {
  // Decision already computed by onFlexMailerRun() for this job.
  self::$useAltMailerForNextSend = self::$currentJobUseAltMailer;
}
else {
  // Send outside CiviMail (individual transactional email, "Send an email" task...):
  // count this message's recipients (to+cc+bcc) and apply the same threshold.
  $recipientCount = self::countRecipients($params);
  self::$useAltMailerForNextSend = ($recipientCount > 0 && $recipientCount <= $limit);
}
```

The threshold used is CiviCRM's **native** `simple_mail_limit` setting (`Civi::settings()->get('simple_mail_limit')`) — **not** a setting specific to this extension. CiviCRM natively uses this same setting to decide whether a "Send an email" task should be forced into a real CiviMail mailing (above the threshold) or sent directly (below it): the extension reuses a threshold the admin is already familiar with.

The whole method is wrapped in a `try/catch (\Throwable $e)`: on any unexpected error (e.g. a missing table, a Mosaico edge case, etc.), we log via `Civi::log()->error()` and **systematically fall back to the primary SMTP** rather than letting the send fail. The same precaution is taken in `onFlexMailerRun()`.

### 4. `CRM_Multiplesmtp_ProxyMailer` — actual routing

```php
public function send($recipients, $headers, $body, $originalValues = []) {
  $target = $this->defaultMailer;
  if (CRM_Multiplesmtp_Hook::$useAltMailerForNextSend) {
    $alt = CRM_Multiplesmtp_Hook::buildAlternativeMailerPublic();
    if ($alt !== NULL) {
      $target = $alt;
    }
  }
  CRM_Multiplesmtp_Hook::$useAltMailerForNextSend = FALSE; // always reset
  return $target->send($recipients, $headers, $body, $originalValues);
}
```

A new PEAR mailer (`Mail::factory('smtp', ...)`) is rebuilt **on every transactional send** via `buildAlternativeMailer()` (no connection caching). The `$useAltMailerForNextSend` flag is reset to `FALSE` immediately after being consumed, as a safety net (in case `alterMailParams` isn't called again before the next `send()`).

Any method not defined here (`getDriver()`, `disconnect()`, etc.) is delegated to the default mailer via `__call()`.

### Settings storage

All settings are prefixed `multiplesmtp_` and stored via `Civi::settings()` (no dedicated table, no formal `settings/*.setting.php` file — settings are written/read dynamically, with no declared metadata). Fields defined in `Hook::$fields`:

| Key (prefixed `multiplesmtp_`) | Type    | Notes |
|---|---|---|
| `enabled` | checkbox | Enables/disables the whole feature. If unchecked: all other `multiplesmtp_*` settings are set to `NULL` (see `postProcess()`). |
| `smtp_server` | text | Can include an `ssl://` prefix. |
| `smtp_port` | text | Defaults to `587` if empty (see `buildAlternativeMailer()`). |
| `smtp_auth` | radio (Yes/No) | Stored as `int` (0/1). |
| `smtp_username` | text | |
| `smtp_password` | password | Encrypted via `CRM_Utils_Crypt::encrypt()` if available, otherwise falls back to `base64` (⚠️ not real encryption, just obfuscation — worth improving if `CRM_Utils_Crypt` availability isn't guaranteed in production). |

The password is **never** re-displayed in plain text in the form: a `(saved password)` placeholder is shown if a value already exists, and the field is only updated if the admin enters a new value (`postProcess()`, `smtp_password` block).

### Form (`Hook::buildForm()` / `Hook::postProcess()`)

The fields are dynamically injected into `CRM_Admin_Form_Setting_Smtp` via:
- `hook_civicrm_buildForm` → adds the form elements.
- An additional template (`SmtpAltFields.tpl`) injected into the `page-body` region (`CRM_Core_Region::instance('page-body')->add(...)`), which displays these fields in a dedicated table.
- `js/multiplesmtp.js` handles conditional display (show/hide based on the "Configure a transactional flow" checkbox).

`postProcess()` also handles the "Save & Test" button (`multiplesmtp_test` in `$values`), which calls `sendTestEmail()` — a send built and dispatched **directly** via `Mail::factory()` (so, like the native test, it happens **outside** of `CRM_Utils_Mail::send()`/`alterMailParams()` — no possible interference with the routing logic).

### Points of attention / technical debt

1. **`isNativeSmtpTestCall()` via `debug_backtrace()`**: fragile against internal refactors of `CRM_Admin_Form_Setting_Smtp` in future major CiviCRM versions. Worth re-validating after every major version upgrade.
2. **No SMTP connection caching**: every transactional send rebuilds a `Mail::factory()` connection. Acceptable at low volume (which is precisely the intended use case), worth revisiting if "transactional" volume grows significantly.
3. **`mailerJobsMax` > 1**: counting by `job_id` only reflects the sub-job, not the full mailing, if CiviMail parallelism is enabled.
4. **Password encryption**: relies on `CRM_Utils_Crypt` being available, with an insecure `base64_encode` fallback. Worth documenting/monitoring in case this class isn't loaded in a given context.
5. **No formally declared settings** (`settings/*.setting.php`) despite the `setting-php@1.0.0` mixin being present in `info.xml` — values go through `Civi::settings()->set()/get()` with no registered metadata. Functional but less "clean" than the standard CiviCRM declaration approach.

### Extension points

- To recognize a new "mailing" context: extend the `['civimail', 'flexmailer', 'testEmail']` array in `alterMailParams()`.
- To change the threshold's source (e.g. an extension-specific setting instead of the native `simple_mail_limit`): update both `Civi::settings()->get('simple_mail_limit')` calls in `onFlexMailerRun()` and `alterMailParams()`.
- To add a new SMTP configuration field: add it to `Hook::$fields` — it will automatically be rendered by `buildForm()` and saved by `postProcess()` (except for types requiring special handling, cf. the `smtp_auth`/`smtp_password` blocks).

---------------------------------------------------------------------------------------------

# Multiple SMTP — Guide développeur

## Objectif technique

CiviCRM ne construit son mailer PEAR (`pear_mail`) qu'**une seule fois par requête**, via `CRM_Utils_Mail::createMailer()`. Il n'existe pas de mécanisme natif pour router dynamiquement, message par message, vers un mailer différent selon le contexte. Cette extension comble ce manque en interceptant deux hooks CiviCRM et un événement FlexMailer.

## Architecture

```
multiplesmtp.php                    ← déclarations des hooks + listener FlexMailer
CRM/Multiplesmtp/Hook.php           ← toute la logique métier (formulaire, routage, settings)
CRM/Multiplesmtp/ProxyMailer.php    ← mailer "façade" qui route réellement l'envoi
templates/CRM/Multiplesmtp/SmtpAltFields.tpl  ← champs injectés dans le formulaire SMTP natif
js/multiplesmtp.js                  ← affichage conditionnel des champs (show/hide selon la case activée)
```

### 1. `hook_civicrm_alterMailer` → `Hook::alterMailer()`

Déclenché quand CiviCRM construit son mailer par défaut (`_createMailer()`). On y **enveloppe** l'objet mailer natif dans `CRM_Multiplesmtp_ProxyMailer`, sauf dans un cas précis (voir plus bas). Toute la logique de choix de destination (principal vs. transactionnel) est ensuite déléguée à ce proxy au moment de l'envoi effectif.

```php
public static function alterMailer(&$mailer, $driver, $params) {
  if (self::isNativeSmtpTestCall()) {
    return; // ne pas envelopper, voir ci-dessous
  }
  if (!($mailer instanceof CRM_Multiplesmtp_ProxyMailer)) {
    $mailer = new CRM_Multiplesmtp_ProxyMailer($mailer);
  }
}
```

**Exclusion du test natif SMTP** (`isNativeSmtpTestCall()`) :
Le bouton natif « Save & Send Test Email » de *Administer > System Settings > Outbound Mail* appelle `CRM_Utils_Mail::_createMailer()` (ce qui déclenche `alterMailer`), puis `CRM_Utils_Mail::sendTest()`, qui appelle `$mailer->send()` **directement**, sans repasser par `CRM_Utils_Mail::send()` — donc **sans jamais déclencher `alterMailParams()`**. Si ce mailer était enveloppé dans notre `ProxyMailer`, celui-ci consulterait le flag statique `$useAltMailerForNextSend`, qui contiendrait une valeur **périmée** issue d'un envoi précédent sans rapport. On détecte donc cet appel via `debug_backtrace()` (recherche de la frame `CRM_Admin_Form_Setting_Smtp::sendTest`) et on retourne sans envelopper le mailer — il reste alors le mailer natif brut, testant exactement la config du formulaire.

⚠️ Cette détection par backtrace est fragile aux changements internes de CiviCRM core. À surveiller lors des montées de version majeures.

### 2. `civi.flexmailer.run` → `Hook::onFlexMailerRun()`

Enregistré dans `multiplesmtp_civicrm_config()` :

```php
Civi::dispatcher()->addListener(
  \Civi\FlexMailer\FlexMailer::EVENT_RUN,
  ['CRM_Multiplesmtp_Hook', 'onFlexMailerRun'],
  200
);
```

Cet événement se déclenche **une fois par job de mailing traité** (aussi bien pour un envoi de test que pour un envoi normal), **avant** l'envoi effectif des messages du job. À ce stade, le worklow CiviMail garantit que `civicrm_mailing_event_queue` est déjà entièrement peuplée pour ce job (un job ne passe au statut `Running` qu'une fois toute sa queue de destinataires construite) — on peut donc compter les destinataires réels de manière fiable :

```php
$recipientCount = (int) CRM_Core_DAO::singleValueQuery(
  'SELECT COUNT(*) FROM civicrm_mailing_event_queue WHERE job_id = %1',
  [1 => [$job->id, 'Integer']]
);
self::$currentJobUseAltMailer = ($recipientCount > 0 && $recipientCount <= $limit);
```

Le résultat est stocké dans la propriété statique `$currentJobUseAltMailer`, consommée ensuite par `alterMailParams()` pour **chaque** destinataire de ce job (un seul calcul par job, pas par destinataire — évite `N` requêtes SQL redondantes).

**Limite connue** : si CiviCRM découpe un mailing en plusieurs jobs enfants parallèles (`mailerJobsMax` > 1), chaque job enfant est compté indépendamment — le seuil s'applique alors par sous-job, pas sur le total du mailing. Cas rare (config par défaut = un seul job), mais à documenter si le client active le parallélisme.

Le comptage se fait par `job_id` et non par `mailing_id`, volontairement : compter par `mailing_id` gonflerait artificiellement le total pour un envoi de test sur un mailing déjà partiellement envoyé en masse (les lignes de la queue du job réel s'additionneraient à celles du job de test).

### 3. `hook_civicrm_alterMailParams` → `Hook::alterMailParams()`

Déclenché juste avant chaque envoi individuel (un appel par destinataire). Détermine, pour **ce** message précis, s'il doit utiliser le SMTP transactionnel :

```php
$isMailingContext = in_array($context, ['civimail', 'flexmailer', 'testEmail'], TRUE)
  || !empty($params['headers']['List-Unsubscribe']);

if ($isMailingContext) {
  // Décision déjà calculée par onFlexMailerRun() pour ce job.
  self::$useAltMailerForNextSend = self::$currentJobUseAltMailer;
}
else {
  // Envoi hors CiviMail (transactionnel unitaire, tâche "Envoyer un email"...) :
  // on compte les destinataires de CE message (to+cc+bcc) et on applique le même seuil.
  $recipientCount = self::countRecipients($params);
  self::$useAltMailerForNextSend = ($recipientCount > 0 && $recipientCount <= $limit);
}
```

Le seuil utilisé est le réglage **natif** CiviCRM `simple_mail_limit` (`Civi::settings()->get('simple_mail_limit')`) — **pas** un réglage propre à cette extension. Ce même réglage sert nativement à CiviCRM pour décider si une tâche "Envoyer un email" doit être forcée vers un vrai mailing CiviMail (au-delà du seuil) ou envoyée directement (en-deçà) : notre extension réutilise donc un seuil déjà familier à l'admin.

Toute la méthode est enveloppée dans un `try/catch (\Throwable $e)` : en cas d'erreur imprévue (ex. absence de table, edge-case Mosaico, etc.), on journalise via `Civi::log()->error()` et on se **replie systématiquement sur le SMTP principal** plutôt que de faire échouer l'envoi. Même précaution dans `onFlexMailerRun()`.

### 4. `CRM_Multiplesmtp_ProxyMailer` — routage effectif

```php
public function send($recipients, $headers, $body, $originalValues = []) {
  $target = $this->defaultMailer;
  if (CRM_Multiplesmtp_Hook::$useAltMailerForNextSend) {
    $alt = CRM_Multiplesmtp_Hook::buildAlternativeMailerPublic();
    if ($alt !== NULL) {
      $target = $alt;
    }
  }
  CRM_Multiplesmtp_Hook::$useAltMailerForNextSend = FALSE; // reset systématique
  return $target->send($recipients, $headers, $body, $originalValues);
}
```

Un nouveau mailer PEAR (`Mail::factory('smtp', ...)`) est reconstruit **à chaque envoi transactionnel** via `buildAlternativeMailer()` (pas de mise en cache de la connexion). Le flag `$useAltMailerForNextSend` est remis à `FALSE` immédiatement après consommation, par sécurité (au cas où `alterMailParams` ne serait pas rappelé avant le prochain `send()`).

Les méthodes non définies (`getDriver()`, `disconnect()`, etc.) sont déléguées au mailer par défaut via `__call()`.

### Stockage des réglages

Tous les réglages sont préfixés `multiplesmtp_` et stockés via `Civi::settings()` (pas de table dédiée, pas de fichier `settings/*.setting.php` formel — les settings sont écrits/lus dynamiquement, sans métadonnées déclarées). Champs définis dans `Hook::$fields` :

| Clé (préfixée `multiplesmtp_`) | Type    | Notes |
|---|---|---|
| `enabled` | checkbox | Active/désactive toute la fonctionnalité. Si décochée : tous les autres settings `multiplesmtp_*` sont mis à `NULL` (voir `postProcess()`). |
| `smtp_server` | text | Peut inclure `ssl://` en préfixe. |
| `smtp_port` | text | Défaut `587` si vide (voir `buildAlternativeMailer()`). |
| `smtp_auth` | radio (Oui/Non) | Stocké en `int` (0/1). |
| `smtp_username` | text | |
| `smtp_password` | password | Chiffré via `CRM_Utils_Crypt::encrypt()` si disponible, sinon fallback `base64` (⚠️ pas un chiffrement réel, juste un obscurcissement — à améliorer si `CRM_Utils_Crypt` n'est pas garanti disponible en prod). |

Le mot de passe n'est **jamais** ré-affiché en clair dans le formulaire : un placeholder `(mot de passe enregistré)` s'affiche si une valeur existe déjà, et le champ n'est mis à jour que si l'admin saisit une nouvelle valeur (`postProcess()`, bloc `smtp_password`).

### Formulaire (`Hook::buildForm()` / `Hook::postProcess()`)

Les champs sont injectés dynamiquement dans `CRM_Admin_Form_Setting_Smtp` via :
- `hook_civicrm_buildForm` → ajoute les éléments de formulaire.
- Un template additionnel (`SmtpAltFields.tpl`) injecté dans la région `page-body` (`CRM_Core_Region::instance('page-body')->add(...)`), qui affiche ces champs dans une table dédiée.
- `js/multiplesmtp.js` gère l'affichage conditionnel (show/hide selon la case « Configurer un flux transactionnel »).

`postProcess()` gère aussi le bouton « Enregistrer et tester » (`multiplesmtp_test` dans `$values`), qui appelle `sendTestEmail()` — un envoi construit et expédié **directement** via `Mail::factory()` (donc, comme pour le test natif, **hors** de `CRM_Utils_Mail::send()`/`alterMailParams()` — pas d'interférence possible avec la logique de routage).

### Points d'attention / dette technique

1. **`isNativeSmtpTestCall()` via `debug_backtrace()`** : fragile face aux refactors internes de `CRM_Admin_Form_Setting_Smtp` dans de futures versions majeures de CiviCRM. À valider après chaque montée de version.
2. **Pas de cache de connexion SMTP** : chaque envoi transactionnel reconstruit une connexion `Mail::factory()`. Acceptable en volume faible (c'est précisément le cas d'usage visé), à revoir si le volume "transactionnel" grossit significativement.
3. **`mailerJobsMax` > 1** : le comptage par `job_id` ne reflète que le sous-job, pas le mailing complet, si le parallélisme CiviMail est activé.
4. **Chiffrement du mot de passe** : dépend de la disponibilité de `CRM_Utils_Crypt`, avec fallback `base64_encode` non sécurisé. Documenter/monitorer ce point si la classe venait à ne pas être chargée dans un contexte donné.
5. **Pas de settings déclarés formellement** (`settings/*.setting.php`) malgré le mixin `setting-php@1.0.0` présent dans `info.xml` — les valeurs passent par `Civi::settings()->set()/get()` sans métadonnées enregistrées. Fonctionnel mais moins "propre" que la déclaration standard CiviCRM.

### Point d'entrée pour étendre le comportement

- Pour ajouter un nouveau contexte "mailing" à reconnaître : étendre le tableau `['civimail', 'flexmailer', 'testEmail']` dans `alterMailParams()`.
- Pour changer la source du seuil (ex. un réglage propre à l'extension au lieu du `simple_mail_limit` natif) : modifier les deux appels `Civi::settings()->get('simple_mail_limit')` dans `onFlexMailerRun()` et `alterMailParams()`.
- Pour ajouter un nouveau champ de configuration SMTP : l'ajouter dans `Hook::$fields`, il sera automatiquement rendu par `buildForm()` et sauvegardé par `postProcess()` (sauf types nécessitant un traitement spécial, cf. blocs `smtp_auth`/`smtp_password`).
