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


### Special case: Donation Receipts (de.systopia.donrec)


If the **Donation Receipts** extension (`de.systopia.donrec`) is installed and enabled, an additional option appears:


- **"Force sending of Donation Receipts via the transactional SMTP"** checkbox.


When this option is checked:
- **All** donation receipts sent via the Donation Receipts extension are routed through the **transactional SMTP**, regardless of the `simple_mail_limit`.
- This applies even though Donation Receipts sends emails individually (one email per recipient), which would normally be treated as "small sends" anyway.


This option is useful when:
- You want to **guarantee** that all donation receipts use the transactional SMTP (e.g., for better deliverability, specific tracking, or compliance reasons).
- Your transactional SMTP has higher sending limits or better reputation for critical transactional emails.


If this option is **not checked**, donation receipts follow the standard rule based on `simple_mail_limit` (which, since they're sent individually, will typically route them through the transactional SMTP anyway if the limit is > 1).


## Configuration


1. Go to **Administer > System Settings > Outbound Mail**.
2. Below the primary SMTP settings, a new section appears: **"Configure a transactional flow"**.
3. Check the box to enable the feature.
4. Fill in:
   - **Transactional SMTP server**: your server's address (e.g. `smtp.myprovider.com`). Prefix it with `ssl://` if your server requires it (e.g. `ssl://smtp.myprovider.com`).
   - **Transactional SMTP port**: usually 25, 465, or 587 depending on your provider.
   - **Authentication required**: Yes/No depending on whether your server requires credentials.
   - **Username** and **Password** for SMTP (if authentication is required). The password is stored encrypted.
5. If the **Donation Receipts** extension (`de.systopia.donrec`) is installed, an additional checkbox appears:
   - **"Force sending of Donation Receipts via the transactional SMTP"**. Check this if you want all donation receipts to be sent via the transactional SMTP, regardless of recipient count.
6. Click **"Save & Test"** to validate your configuration: a test email is sent to your own address via the transactional SMTP.
7. Don't forget to fill in (or check) the **"Simple mail limit"** field further up on the same page — that's the number used as the switching threshold.


## Frequently asked questions


**I unchecked "Configure a transactional flow" — what happens to my settings?**
All transactional SMTP settings (server, port, credentials...) are cleared. You'll need to start over if you re-enable it later.


**What if my transactional SMTP is down?**
The test button on the settings page lets you check the connection at any time. If an error occurs during an actual send, the email isn't silently lost — it fails, like any other SMTP failure in CiviCRM.


**How do I know a mailing went out through the right SMTP?**
There's no direct visual indicator in the interface. The simplest way is to compare the number of recipients of the mailing against the limit configured under *Outbound Mail*.


**I don't see the "Force sending of Donation Receipts" checkbox — why?**
This checkbox only appears if:
1. The **Donation Receipts** extension (`de.systopia.donrec`) is installed and active.
2. You have checked **"Configure a transactional flow"**.

If Donation Receipts is not installed, this option is hidden (it wouldn't make sense).


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


### Cas particulier : Donation Receipts (de.systopia.donrec)


Si l'extension **Donation Receipts** (`de.systopia.donrec`) est installée et activée, une option supplémentaire apparaît :


- Une case à cocher **« Forcer l'envoi des Donation Receipts via le SMTP transactionnel »**.


Lorsque cette option est cochée :
- **Tous** les reçus fiscaux envoyés via l'extension Donation Receipts sont routés vers le **SMTP transactionnel**, indépendamment de la limite `simple_mail_limit`.
- Cela s'applique même si Donation Receipts envoie les emails individuellement (un email par destinataire), ce qui les ferait normalement passer de toute façon comme « petits envois ».


Cette option est utile lorsque :
- Vous souhaitez **garantir** que tous les reçus fiscaux utilisent le SMTP transactionnel (par exemple pour une meilleure délivrabilité, un suivi spécifique, ou des raisons de conformité).
- Votre SMTP transactionnel a des limites d'envoi plus élevées ou une meilleure réputation pour les emails transactionnels critiques.


Si cette option **n'est pas cochée**, les reçus fiscaux suivent la règle standard basée sur `simple_mail_limit` (ce qui, comme ils sont envoyés individuellement, les routera typiquement vers le SMTP transactionnel de toute façon si la limite est > 1).


## Configuration


1. Allez sur **Administer > System Settings > Outbound Mail**.
2. Sous les réglages du SMTP principal, une nouvelle section apparaît : **« Configurer un flux transactionnel »**.
3. Cochez la case pour activer la fonctionnalité.
4. Renseignez :
   - **Serveur SMTP transactionnel** : l'adresse de votre serveur (ex. `smtp.monfournisseur.com`). Ajoutez `ssl://` devant si votre serveur l'exige (ex. `ssl://smtp.monfournisseur.com`).
   - **Port SMTP transactionnel** : généralement 25, 465 ou 587 selon votre fournisseur.
   - **Authentification requise** : Oui/Non selon si votre serveur demande un identifiant.
   - **Nom d'utilisateur** et **Mot de passe** SMTP (si authentification requise). Le mot de passe est stocké chiffré.
5. Si l'extension **Donation Receipts** (`de.systopia.donrec`) est installée, une case supplémentaire apparaît :
   - **« Forcer l'envoi des Donation Receipts via le SMTP transactionnel »**. Cochez-la si vous souhaitez que tous les reçus fiscaux soient envoyés via le SMTP transactionnel, indépendamment du nombre de destinataires.
6. Cliquez sur **« Enregistrer et tester »** pour valider votre configuration : un email de test est envoyé à votre propre adresse via le SMTP transactionnel.
7. N'oubliez pas de renseigner (ou vérifier) le champ **« Nombre de destinataires maximum pour un envoi simple »** plus haut sur la même page — c'est ce nombre qui sert de seuil de basculement.


## Questions fréquentes


**J'ai décoché la case « Configurer un flux transactionnel », que se passe-t-il à mes réglages ?**
Tous les réglages du SMTP transactionnel (serveur, port, identifiants...) sont effacés. Vous repartez de zéro si vous réactivez plus tard.


**Et si mon SMTP transactionnel est en panne ?**
Le test depuis la page de configuration vous permet de vérifier la connexion à tout moment. En cas d'erreur au moment d'un envoi réel, l'email n'est pas silencieusement perdu : il tombe en erreur, comme n'importe quel échec SMTP dans CiviCRM.


**Comment je sais qu'un mailing est bien parti par le bon SMTP ?**
Il n'y a pas d'indicateur visuel direct dans l'interface. Le plus simple est de comparer le nombre de destinataires du mailing avec la limite configurée dans *Outbound Mail*.


**Je ne vois pas la case « Forcer l'envoi des Donation Receipts » — pourquoi ?**
Cette case n'apparaît que si :
1. L'extension **Donation Receipts** (`de.systopia.donrec`) est installée et active.
2. Vous avez coché **« Configurer un flux transactionnel »**.

Si Donation Receipts n'est pas installée, cette option est masquée (elle n'aurait pas de sens).



_______________________________________________________________________________________________________________________________________



# Multiple SMTP — Developer Guide


## Technical goal


CiviCRM only builds its PEAR mailer (`pear_mail`) **once per request**, via `CRM_Utils_Mail::createMailer()`. There's no native mechanism to dynamically route to a different mailer per message depending on context. This extension fills that gap by hooking into two CiviCRM hooks and one FlexMailer event.


## Architecture

