# Architektur – Data Protection Center

## Verantwortung

Das Data Protection Center ist die eigenständige Laufzeit-App für den
öffentlichen, versionierten Datenschutzprovidervertrag. Fachapps bleiben
Eigentümerinnen ihrer Daten und liefern ausschließlich kontrollierte
Projektionen. Datenschutzbeauftragte sind Business Owner des zentralen
Auskunfts-, Policy- und Adminfreigabedienstes. Die IKT-Administration
verantwortet nur Plattformbetrieb und Sicherung und erhält daraus keinen
fachlichen Datenzugriff.

## Providergrenze

- Die Registry entdeckt aktivierte, kompatible Provider über den öffentlichen
  Vertrag; fehlende oder fehlerhafte Provider bleiben sichtbar.
- Die zentrale App liest niemals Tabellen, Dateien, AppData, AppConfig oder
  interne Klassen einer Fachapp direkt.
- Self-Service erzeugt den Subject-Bezug `nextcloud-user` ausschließlich aus
  der authentifizierten Session.
- Providerfehler werden isoliert und dürfen einen Teilbericht nicht als
  vollständige Instanzauskunft erscheinen lassen.
- Der additive öffentliche V1-`ProcessingMetadataProvider` liefert pro App
  ausschließlich deren app-eigenen Katalog. Descriptor- und Katalog-App-ID
  müssen übereinstimmen; inkompatible, doppelte oder fehlerhafte Provider
  werden isoliert abgewiesen. Der Vertrag enthält keine personenbezogenen
  Laufzeitdaten und erweitert den bestehenden `PersonalDataProvider` nicht.

## Processing-Metadaten

Der kanonische app-eigene Katalog liegt unter
`resources/privacy-processing.json`. Er beschreibt derzeit die Verarbeitungen
`article_15_aggregation` und `temporary_admin_full_access`. Bekannte Zwecke
und technische Schutzgrenzen stammen aus der bestehenden App-Architektur;
noch offene Rechtsgrundlagen, fachliche Verantwortlichkeiten sowie weitere
noch nicht entschiedene Datenschutz- und Betriebsfragen sind strukturiert als
`PRIVACY-DECISION-REQUIRED` ausgewiesen.

Die zentrale administrative Auskunft ist ausschließlich der kanonischen
Nextcloud-Gruppe `Datenschutzbeauftragte` zugänglich. Nativer Adminstatus und
eine temporäre fachliche Adminfreigabe ersetzen diese Mitgliedschaft nicht.
Der sessiongebundene Self-Service der betroffenen Person bleibt davon
getrennt.

Die öffentliche Registry liest Kataloge ausschließlich über registrierte
Provider. Sie durchsucht weder andere App-Verzeichnisse noch fremde Tabellen,
Dateien oder Konfigurationen. Root bleibt Eigentümer des gemeinsamen Schemas;
die App besitzt ihren fachlichen Katalog und die Runtime-DTOs ihres
öffentlichen Providervertrags.

## Persistenz, Rechte und Retention

Berichtsinhalte werden nicht zentral persistiert. App-lokal gespeichert wird
nur die sicherheitsrelevante Historie zeitlich begrenzter
Adminvollzugriffsfreigaben. Retention bleibt ein read-only Preview-Vertrag;
Ausführung, Löschung oder Anonymisierung benötigen einen getrennten,
freigegebenen Lebenszyklusvertrag.

Auch perspektivisch besitzt das Datenschutz-Center keine globale
Beschäftigten-Lifecycle- oder Löschhoheit. Bis eine verlässliche
Beschäftigtenquelle und vollständig getestete app-lokale Providerverträge
vorliegen, führen ausschließlich die datenbesitzenden Fachapps ihre jeweiligen
Policies aus; das Center registriert, prüft und koordiniert sie.

Jede Vorschau entdeckt Provider lazy über den öffentlichen V1-Registry-Event
und fixiert den Bewertungszeitpunkt über alle Folgeseiten. Provider behalten
Datenzugriff und Cursorinterpretation; das Datenschutz-Center aggregiert nur
datenminimierte DTOs und opake Fortsetzungen. Fehlende, deaktivierte,
doppelte oder inkompatible Provider bleiben sichtbar beziehungsweise
unvollständig und werden niemals durch direkten Tabellen-, Datei-, AppConfig-
oder Reflection-Zugriff ersetzt.

Fachliche Datenschutzkonfiguration einschließlich app-eigener
Aufbewahrungsfristen ist ausschließlich Mitgliedern der dedizierten
Nextcloud-Gruppe `Datenschutzbeauftragte` erlaubt. Fehlt sie, legt eine
versionierte Migration sie bei frischer Installation beziehungsweise beim
nächsten Upgrade einer bereits aktivierten App idempotent über die native
Nextcloud-Gruppenverwaltung an. Vorhandene Gruppen und Mitgliedschaften
bleiben unverändert. Diese Gruppen-ID ist der kanonische Default; die nur für
Entwicklungsumgebungen verwendete Bezeichnung `privacy-officer` ist keine
Produktrolle. Native
Nextcloud-Administration bleibt für technische App-Verwaltung zuständig,
erteilt aber kein Recht zur fachlichen Datenschutzkonfiguration.

Die app-lokale Freigabesteuerung folgt derselben harten Rollentrennung:
Ausschließlich aktuell bestätigte Mitglieder von `Datenschutzbeauftragte`
dürfen einem aktuellen nativen Nextcloud-Administrationskonto einen
UID-genauen fachlichen Vollzugriff von höchstens 24 Stunden erteilen oder ihn
widerrufen. Dafür ist kein eigener nativer Adminstatus erforderlich. Die
Steuerung liegt im authentifizierten App-Einstieg und nicht im technischen
Nextcloud-Adminbereich. Ein natives Administrationskonto ohne aktive Freigabe
erhält dort eine zustandssichere Meldung; der Direktlink zur Steuerung
erscheint nur bei zusätzlicher Mitgliedschaft in `Datenschutzbeauftragte`.
Gewöhnliche und andere unberechtigte Konten erhalten weder diesen
administrativen Zustand noch den Link. API und fachlicher REVIEW-Zugriff
prüfen die jeweilige Rolle beziehungsweise Freigabe unabhängig von der UI
serverseitig und deny by default.

Änderungen einer Aufbewahrungsfrist gelten auch für bereits vorhandene Daten
und werden aus deren ursprünglichem fachlichem Trigger neu berechnet. Die
eigene Adminhistorien-Policy führt jede Konfiguration mit fortlaufender
Revision, Wirksamkeitszeitpunkt und Akteur und verweigert unberechtigte,
veraltete oder beschädigte Änderungen ohne Nebenwirkung. Sie bleibt mindestens
24 Monate auditierbar; die Gruppe `Datenschutzbeauftragte` überprüft die
Konfiguration mindestens jährlich. Bis zur ersten dokumentierten
Konfiguration oder Prüfung gilt der Review als fällig.

Die eigene Adminfreigabehistorie wird standardmäßig sechs Monate ab ihrem
tatsächlichen Ende aufbewahrt und danach vollständig gelöscht, ohne
anonymisierten oder statistischen Restbestand. Eine aktive rechtliche oder
datenschutzrechtliche Sperre blockiert die Löschung; nur
`Datenschutzbeauftragte` dürfen sie begründet und auditiert aufheben. Nach
einem Restore wird die Frist aus dem ursprünglichen Ende neu bewertet,
abgelaufene ungesperrte Historie erneut zur Löschung eingeplant und niemals
eine fachliche Freigabe reaktiviert.

Die künftige Löschung läuft automatisch ohne manuelle Einzelfreigabe. Nach
automatischen Wiederholungsversuchen wird `Datenschutzbeauftragte` nur mit
App, Datenklasse, Zeitpunkt und technischer Referenz benachrichtigt. Der
inhaltsarme technische Fehlernachweis wird nach 30 Tagen gelöscht. Der
öffentliche V1-Provider projiziert die jeweils aktuelle Policyrevision,
bewertet das ursprüngliche tatsächliche Freigabeende erneut und liefert nur
datenminimierte `REVIEW`-Kandidaten. Er besitzt keine `execute()`-Methode.
Der Ausführungsvertrag ist nicht implementiert: Policyversion und
Wirksamkeitszeitpunkt, Reihenfolge, Atomarität, Nebenläufigkeit, Idempotenz,
Backupgrenze, Sperrdurchsetzung, Auditvollständigkeit, Fehlerrückbau sowie
Provider-/Consumer-Verhalten müssen vor jeder destruktiven Maßnahme
freigegeben und positiv wie negativ getestet werden. Insbesondere fehlen ein
technisch durchgesetzter Hold-Datensatz samt Setzen/Aufheben/Audit und
Prüftermin, der betriebliche Nachweis der beschlossenen Backupgrenze sowie der
nebenläufigkeits- und fehlerrückbaufeste Ausführungs- und
Wiederholungsnachweis. Das Datenschutz-Center koordiniert nur öffentliche
Provider; es liest oder löscht niemals direkt in Fremdtabellen, fremden
Dateien oder fremden App-Speichern.

## DP-07-Pilotvertrag für die eigene Adminfreigabehistorie

### Status, Scope und kanonische Quellen

Dieser Vertrag ist das freigegebene Zielbild für den ersten ausführenden
DP-07-Piloten. Die additive Tabelle `fdp_retention_profile` und der
DPO-geschützte Konfigurationsweg setzen inzwischen ausschließlich das
geschlossene deutsche Rechts-/Backup-Profil um. Ein leerer Installations- oder
Upgradestand enthält keine Kundenauswahl und bleibt `REVIEW`; jede Änderung
erzeugt eine neue, optimistisch revisionsgesicherte Auditzeile. Dieser Schritt
aktiviert keine Löschung, keinen Hintergrundjob und keine `execute()`-Methode.
Die Profilrevisionshistorie ist eine eigene Verarbeitung und erbt weder die
Sechsmonatsfrist noch den späteren Löschpfad der Adminfreigabehistorie. Ihre
Rechtsgrundlage, Aufbewahrung, Holds und Backupbehandlung bleiben im
Processing-Katalog ausdrücklich `PRIVACY-DECISION-REQUIRED`; bis zu einer
gesonderten Entscheidung existiert für sie kein Löschpfad.
Alle nachfolgenden Ausführungsbestandteile bleiben gesondert freizugeben.

Der Pilot ist ausschließlich für die app-eigene Tabelle
`fdp_admin_access` und die Datenklasse der zeitlich begrenzten
Adminfreigabehistorie zuständig. Er darf keine Berichte, Daten anderer Apps,
Nextcloud-Konten, Gruppenmitgliedschaften, Dateien oder fremde Speicher
löschen. Die kanonischen fachlichen Quellen sind:

- die unveränderte Freigabehistorie für Beginn, geplantes Ende und wirksamen
  Widerruf;
- die revisionsgeführte app-eigene Retention-Policy für Dauer,
  Wirksamkeitszeitpunkt und jährliche Prüfung;
- ein noch zu implementierender app-eigener Hold-Speicher für Sperren;
- ein noch zu implementierendes, datenminimiertes Ausführungsjournal für
  Nebenläufigkeit, Idempotenz, Retry und Fehlerdiagnostik;
- ein betrieblich gesetzter Restore-Zeitpunkt samt erfolgreich abgeschlossenem
  Restore-Abgleich als Sicherheitsbarriere.

Keine dieser Quellen darf aus dem Zustand eines Nextcloud-Kontos, einer
UI-Sichtbarkeit oder einem fremden App-Speicher abgeleitet werden. Der heutige
Offset-Cursor der V1-Vorschau ist keine zulässige Ausführungsreihenfolge; die
Ausführung benötigt eine stabile Keyset-Reihenfolge.

### Deutsches Kunden- und Policyprofil

Das auslieferbare Produkt ist zunächst ausschließlich für Verantwortliche in
Deutschland vorgesehen. Rechtsgrundlage, Kollektivvereinbarung und
Backupbetrieb bleiben kundeneigene Governance- und Betriebsentscheidungen;
die App prüft weder deren rechtliche Wirksamkeit noch zertifiziert sie die
Angemessenheit einer Interessenabwägung. Sie darf nur Vollständigkeit,
Revision, Gültigkeit und Freigabestatus eines geschlossenen technischen
Profils prüfen und bleibt bei jeder Lücke fail-closed bei `REVIEW`.

Der erste Pilot kennt genau die folgenden Rechtsgrundlagenprofile:

| Profil-ID | Zulässiger Einsatz und Pflichtnachweise |
| --- | --- |
| `employment_collective_agreement_de` | Beschäftigtenverarbeitung auf Grundlage von § 26 Abs. 4 BDSG in Verbindung mit Art. 88 DSGVO und einer beim Kunden geltenden Betriebs-, Dienst- oder Kollektivvereinbarung. Erforderlich sind die konkrete Vereinbarungsreferenz, deren Revision, Geltungsbereich, Wirksamkeitsdatum, nächster Prüftermin, die Bestätigung, dass alle vom Profil erfassten Administrationskonten Beschäftigtenkonten im Geltungsbereich sind, sowie die dokumentierte Bestätigung durch `Datenschutzbeauftragte`. |
| `legitimate_interest_it_security_de` | Enger Auffangtatbestand für einen deutschen Verantwortlichen nur bei dokumentierter Interessenabwägung zu Art. 6 Abs. 1 lit. f DSGVO. Erforderlich sind konkreter IT-Sicherheitszweck, Erforderlichkeitsprüfung, benanntes berechtigtes Interesse, geprüfte Auswirkungen und Gegeninteressen, Schutzmaßnahmen, betroffene Kontenkategorien, Revision, Wirksamkeitsdatum, nächster Prüftermin sowie die dokumentierte Bestätigung durch `Datenschutzbeauftragte`. Das Profil darf eine fehlende oder nicht einschlägige Kollektivvereinbarung nicht lediglich durch eine Auswahl im UI ersetzen. |

Freie Profil-IDs, freie Rechtsgrundlagen, pauschale Artikelangaben und bloße
Freitextbegründungen sind unzulässig. Ein Profilwechsel ist eine neue,
nicht rückdatierbare Policyrevision. Ein abgelaufener Prüftermin, eine
geänderte Kontenkategorie, ein verlorener Geltungsbereich oder eine fehlende
DPO-Bestätigung blockiert die Ausführung ohne Nebenwirkung. Erweiterungen auf
andere Rechtsordnungen, weitere Rechtsgrundlagen oder gehostete Betriebsmodelle
benötigen eine neue Produkt- und Datenschutzentscheidung; sie sind nicht Teil
dieses deutschen ersten Modells.

Die Auswahl eines Profils und sämtliche Vereinbarungs-, Abwägungs-,
Geltungs-, Evidenz- und Verantwortlichenreferenzen sind Daten der konkreten
Kundeninstanz. Sie dürfen weder als Kundenvorgabe im auslieferbaren
App-Paket, im Processing-Katalog noch in Produktdokumentation festgeschrieben
werden. Das Paket liefert nur Profildefinitionen, Validierungsgrenzen und
den deaktivierten Default. Eine Instanz darf die Ausführung erst nach
vollständiger app-lokaler Konfiguration und DPO-Bestätigung aktivieren.

Unabhängig vom gewählten Profil erzwingt das Produkt dauerhaft folgende
Grenzen: keine Leistungs- oder Verhaltenskontrolle, keine darauf gerichteten
Exporte, Kennzahlen, Verknüpfungen oder Zweckwechsel; serverseitige
Privacy-Autorisierung; Datensparsamkeit; Holds; atomare und idempotente
Ausführung; Retry- und Auditgrenzen; Restore-Quarantäne sowie keine direkte
Löschung in fremden Apps. Diese Grenzen sind nicht kundenseitig abschaltbar.

### DPO-Retentionfall und späteres Beschäftigungsereignis

Bis eine autoritative Personalquelle angeschlossen ist, darf ausschließlich
die Gruppe `Datenschutzbeauftragte` einen app-eigenen Retentionfall erfassen.
Dieser Fall bestätigt nur, dass eine Datenschutzprüfung mit einem bestimmten
Wirksamkeitszeitpunkt vorliegt. Er behauptet ausdrücklich weder
`employment_ended` noch ein anderes Personalereignis und wird nicht aus
Kontodeaktivierung, Kontolöschung oder Gruppenentzug abgeleitet.

Ein solcher Fall beziehungsweise eine Korrektur enthält ausschließlich:

| Feld | Vertrag |
| --- | --- |
| `case_id` | stabile, opake ID des Retentionfalls |
| `event_id` und `revision` | unveränderliches Ereignis und streng steigende Revision innerhalb des Falls |
| `subject` | typisierte Subject-Referenz; im ersten Modell ausschließlich `nextcloud-user` mit UID |
| `event_kind` | ausschließlich `DPO_RETENTION_CASE_EFFECTIVE`, niemals `EMPLOYMENT_ENDED` |
| `effective_at` | fachlich bestätigter Wirksamkeitszeitpunkt |
| `recorded_at` und `recorded_by` | serverseitiger Erfassungszeitpunkt und aktuelle UID eines Mitglieds von `Datenschutzbeauftragte` |
| `reason_code` | begrenzter fachlicher Grundcode ohne freie Personal- oder Falldetails |
| `source_reference` | minimale externe Referenz; keine Dokumentkopie und kein Freitextsachverhalt |
| `policy_scope` | ausdrücklich benannte app-eigene Policy-IDs; keine globale oder implizite Löschfreigabe |
| `correction_of` | vorherige `event_id`, wenn ein Zeitpunkt oder Scope korrigiert wird |
| `status` | `ACTIVE`, `CORRECTED` oder `WITHDRAWN`; Korrektur und Widerruf erfolgen append-only |

Unbekannte, widersprüchliche, widerrufene oder außerhalb ihres Scopes liegende
Fälle erlauben höchstens `REVIEW`. Eine Policy darf einen DPO-Retentionfall nur
verwenden, wenn sie diesen Ereignistyp ausdrücklich als Trigger deklariert.
Die Adminfreigabehistorie des ersten Piloten verwendet ihn nicht: Ihr Trigger
bleibt ausschließlich das tatsächliche Ende der jeweiligen Freigabe.

Eine spätere Personalabteilung oder Lohnbuchhaltung liefert
`EMPLOYMENT_ENDED` und dessen Korrekturen über einen getrennten,
versionsgeprüften und ausdrücklich autorisierten Provider. Dieses Ereignis
ersetzt oder überschreibt keinen DPO-Retentionfall. Bevor der DPO-Retentionfall
selbst persistiert wird, müssen seine eigene Aufbewahrung, Berichtigung,
Auskunft, Sperre und Löschung als gesonderte Verarbeitung entschieden werden.

### Autorisierung und Deny-by-default

- Nur aktuelle Mitglieder von `Datenschutzbeauftragte` dürfen Policyrevisionen
  erfassen oder prüfen sowie Retentionfälle und Holds setzen, korrigieren oder
  aufheben. Jede Mutation ist CSRF-geschützt, serverseitig autorisiert und
  revisionsgesichert.
- Nextcloud-Adminstatus, temporärer app-lokaler Adminvollzugriff und
  `retention.review` erteilen kein Recht, einen Löschlauf zu starten, zu
  erzwingen, zu überspringen oder einen Hold aufzuheben.
- Die Ausführung erfolgt ausschließlich durch einen app-eigenen Hintergrundjob
  unter technischer Systemidentität. Es gibt im Pilot weder eine
  benutzerbedienbare Einzellöschung noch einen Controller-Endpunkt für
  `execute`.
- Technische Administration darf den Job betreiben und den Restore-Abgleich
  auslösen, wählt aber keine Personen, Datensätze oder fachlichen Ausnahmen
  aus. Eine manuelle Einzelfreigabe vor jedem Lauf ist nicht vorgesehen.
- Fehlende Rolle, fällige Jahresprüfung, beschädigte oder unbekannte Policy,
  offene einschlägige Datenschutzentscheidung, unbestätigte Backupgrenze,
  offener Restore-Abgleich, Hold, inkonsistenter Datensatz oder veraltete
  Revision führen ohne Nebenwirkung zu `REVIEW` beziehungsweise blockierter
  Ausführung.

### Policy, Wirksamkeit, Eignung und Holds

Die ausführende Policy erhält eine neue, ausdrücklich versionierte
Vertragsidentität; die bestehende Policy
`temporary_admin_access_history_review` wird nicht still von `REVIEW` auf
`DELETE` umgedeutet. Der Standard bleibt `P6M`. Das tatsächliche Ende ist der
frühere Zeitpunkt aus geplantem Ende und wirksamem Widerruf. Ein Datensatz ist
genau dann grundsätzlich löschbar, wenn sein tatsächliches Ende kleiner oder
gleich dem aus festem `evaluated_at` und Policydauer berechneten Stichtag ist.

Ein Policywechsel erhält eine neue Revision mit serverseitigem,
nicht rückdatierbarem `effective_at`. Ab diesem Zeitpunkt werden auch alle
vorhandenen Datensätze anhand ihres ursprünglichen tatsächlichen Endes neu
berechnet. Eine Verkürzung kann sie neu löschbar machen, eine Verlängerung
entzieht ihnen die Löschbarkeit wieder. Jeder Lauf fixiert Policy-ID,
Revision, `effective_at` und `evaluated_at`; jede dazwischenliegende
Policyrevision verwirft den noch nicht ausgeführten Previewstand und stoppt
weitere Löschungen dieses Laufs.

Ein Hold gilt nur für den konkret benannten Datensatz und die Policy. Er
enthält eine opake Hold-ID, Policy-ID, interne Datensatzreferenz, begrenzten
Grundcode, minimale Quellenreferenz, setzende DPO-UID, Setzzeitpunkt,
Prüftermin sowie bei Aufhebung DPO-UID, Zeitpunkt und Grundcode. Ein
überschrittener Prüftermin hebt den Hold niemals automatisch auf. Setzen,
Korrigieren und Aufheben bleiben auditierbar; nur eine ausdrücklich
aufgehobene Sperre gibt den Datensatz bei der nächsten Neubewertung frei.
Personenbeziehbare Hold- und Ausführungshilfsdaten werden bei erfolgreicher
Löschung des Bezugsdatensatzes ebenfalls entfernt, soweit keine fortbestehende
Sperre die Löschung ohnehin verhindert.

### Ablauf, Atomarität und Nebenläufigkeit

Ein automatischer Lauf folgt dieser Reihenfolge:

1. Er verweigert den Start, solange Freigabegates offen sind, und erwirbt eine
   eindeutige Lease für Policy und Revision.
2. Er fixiert `evaluated_at`, Policyrevision und Restore-Epoch, erzeugt einen
   vollständigen Dry Run und bindet ihn an einen kurzlebigen
   Integritätsbezug. Dafür ist keine manuelle Bestätigung erforderlich.
3. Er liest Kandidaten stabil nach tatsächlichem Ende und Primärschlüssel in
   aufsteigender Keyset-Reihenfolge. Neue oder veränderte Datensätze dürfen
   keine bereits gelesenen Kandidaten überspringen oder doppelt löschen.
4. Für jeden Kandidaten prüft dieselbe Datenbanktransaktion Datensatz,
   tatsächliches Ende, unveränderte Policyrevision, Restore-Epoch und das
   Fehlen eines Holds erneut. Sie schreibt einen eindeutigen
   Idempotenznachweis und löscht den Historieneintrag atomar. Abhängige
   personenbeziehbare Hilfsdaten werden in derselben Transaktion entfernt.
5. Ein Commit ist die Grenze der wirksamen Löschung im aktiven System. Vor
   dem Commit bewirkt jeder Fehler einen vollständigen Rollback dieses
   Kandidaten; nach dem Commit gibt es weder Soft Delete noch fachlichen Undo.
6. Der Lauf wiederholt die Abfrage, bis für seinen fixierten Stand kein
   löschbarer Kandidat mehr vorhanden ist, und gibt anschließend die Lease
   frei.

Hold-Mutation und Löschung müssen auf derselben Datensatzgrenze serialisiert
werden. Gewinnt der Hold, wird nicht gelöscht. Commitet die Löschung zuerst,
muss ein nachfolgendes Hold-Setzen mit „Datensatz nicht vorhanden“ enden und
darf keinen Scheinschutz behaupten. Parallele Worker teilen einen eindeutigen
Idempotenzschlüssel; höchstens einer darf löschen, alle weiteren enden als
erfolgreicher No-op nur dann, wenn der atomare Erfolgsnachweis den früheren
Commit belegt. Ein ohne diesen Nachweis fehlender Datensatz gilt als
Widerspruch und nicht als erfolgreiche Löschung.

Atomarität gilt pro Datensatz, nicht für den gesamten Batch. Ein fachlicher
Konflikt isoliert nur den Kandidaten. Ein Datenbank-, Policy-, Lease- oder
Restorefehler stoppt den übrigen Batch, damit kein Lauf mit unbekanntem
Schutzstand fortgesetzt wird.

### Wiederholung, Fehlerdiagnostik und Audit

Transiente Fehler werden automatisch mit begrenztem exponentiellem Backoff
erneut versucht. Nach fünf aufeinanderfolgenden Fehlversuchen oder spätestens
24 Stunden nach dem ersten noch ungelösten Fehler bleibt der Datensatz
unverändert löschbar beziehungsweise gesperrt, der Lauf erzwingt keine
Maßnahme und
`Datenschutzbeauftragte` erhält genau die datenminimierte Meldung aus App,
Datenklasse, Zeitpunkt und opaker technischer Referenz. Nach technischer
Behebung darf ausschließlich der automatische, erneut vollständig prüfende
Pfad fortsetzen; es gibt keinen Force-Delete-Bypass.

Fehler- und Idempotenznachweise enthalten keine UID, freien Inhalte,
Fallbegründungen oder vollständigen Requests. Zulässig sind Policy-ID und
Revision, Run-ID, technische Phase und Fehlerklasse, Versuchszahl,
Zeitpunkte, nächster Retry sowie eine nicht rückauflösbare technische
Referenz. Jeder einzelne technische Nachweis wird spätestens 30 Tage nach
seinem letzten Auftreten gelöscht. Ein weiterhin bestehender Fehler erzeugt
bei einem erneuten Versuch einen neuen, wiederum höchstens 30 Tage gültigen
Nachweis; er verlängert keinen alten Inhalt stillschweigend.

Erfolgreiche Ausführung darf innerhalb des 24-monatigen Policy-Auditfensters
nur auf Laufebene mit Policy-ID, Revision, `evaluated_at`, Beginn, Ende und
Ergebnis belegt werden. Sie speichert weder gelöschte IDs oder UIDs noch
Kandidatenzahl, Personenhash, Integritätsbezug oder sonstige aus der
gelöschten Historie abgeleitete Statistik. Policyänderungen und -prüfungen
folgen demselben Auditvertrag. Der technische Rückbau endet am Commit: Eine
selektive Wiederherstellung gelöschter Personenhistorie ist kein zulässiger
Rollbackpfad.

### Backup- und Restoregrenze

Mit dem Commit ist die Löschung im aktiven Nextcloud-System wirksam. Die
betriebliche Sicherung darf den zuvor enthaltenen Datensatz ab diesem
Zeitpunkt höchstens 35 Kalendertage halten: 30 Tage reguläre
Backupaufbewahrung plus höchstens fünf Tage technischer Puffer. Die App legt
keine eigene Sicherung oder Exportkopie an. Vor Aktivierung des Piloten muss
der konkrete Betrieb diese Obergrenze, das Löschen abgelaufener Medien und
den Restore-Ablauf nachweisen; eine nur dokumentierte Sollfrist genügt nicht.
Der heutige Processing-Katalog bleibt bis zu diesem Betriebsnachweis und der
gesondert freigegebenen Umsetzung wahrheitsgemäß bei
`PRIVACY-DECISION-REQUIRED`; der Zielwert allein ist keine Behauptung über den
aktuellen Sicherungsbetrieb.

Jeder Restore läuft vor Freigabe des Systems durch eine Fail-closed-Barriere:

1. Nextcloud und die app-lokale Freigabeprüfung bleiben während des
   Restore-Abgleichs gesperrt.
2. Ein außerhalb des zurückgespielten Stands gesetzter Restore-Epoch macht
   alle zurückgespielten Adminfreigaben unwirksam. Keine frühere Freigabe wird
   durch den Restore reaktiviert; neue Freigaben sind erst nach erfolgreichem
   Abgleich zulässig.
3. Wiederhergestellte Policy- und Holdstände werden als prüfbedürftig
   behandelt. Vor einer Löschung wird die aktuelle Policy durch
   `Datenschutzbeauftragte` bestätigt; ein wiederhergestellter Hold bleibt
   sicherheitshalber wirksam, bis er ausdrücklich aufgehoben wird.
4. Alle wiederhergestellten Historieneinträge werden vom ursprünglichen
   tatsächlichen Ende aus neu bewertet. Bereits abgelaufene und ungesperrte
   Datensätze werden automatisch erneut zur Löschung vorgemerkt.
5. Ein schon vor dem Restore gelöschter Datensatz darf weder in den normalen
   Betrieb noch in eine neue Backupgeneration übernommen werden. Der erneute
   Löschlauf wahrt die ursprüngliche 35-Tage-Grenze; ein Restore startet diese
   Frist nicht neu.

Kann der Betrieb Restore-Epoch, Quarantäne oder die 35-Tage-Grenze nicht
garantieren, bleibt die Ausführung deaktiviert und die Vorschau bei `REVIEW`.

Die Backupgrenze darf später app-lokal als geschlossenes deutsches
Betriebsprofil konfiguriert werden. Bei einbezogenen Daten sind ausschließlich
eine reguläre Aufbewahrung von 1 bis 30 ganzen Kalendertagen und ein
technischer Puffer von 0 bis 5 ganzen Kalendertagen zulässig; die Summe darf
35 Kalendertage nie überschreiten. Eine Verlängerung über diese Obergrenzen,
eine freie Einheit oder ein unbefristeter Wert sind nicht konfigurierbar. Eine
Verkürzung gilt erst mit einer neuen, nicht rückdatierten Policyrevision und
einem positiven Betriebsnachweis.

Zur Aktivierung gehören mindestens verantwortliche Betriebsstelle,
Backup-System beziehungsweise Scope, Evidenzreferenz, Nachweiszeitpunkt,
nächster Prüftermin und DPO-Bestätigung. Der Nachweis muss das tatsächliche
Auslaufen beziehungsweise Löschen abgelaufener Sicherungen und einen
erfolgreichen Restore-Test mit Restore-Epoch, Quarantäne und erneuter
Löschvormerkung abdecken. Eine eingetragene Frist, ein Herstellerdatenblatt
oder eine Selbstauskunft der App ist kein Betriebsnachweis. Fehlt oder
verfällt ein Pflichtwert oder überschreitet eine Änderung die Produktgrenze,
bleibt beziehungsweise wechselt die Ausführung fail-closed zu blockiert. Der
Kunde verantwortet Policy, Sicherungsbetrieb, Nachweise und regelmäßige
Prüfung; die App kann externe Sicherungsmedien weder löschen noch deren
tatsächlichen Entfall selbst feststellen.

Auch konkrete Backupwerte und Nachweise sind ausschließlich
Instanzkonfiguration. Das auslieferbare Paket enthält keine vorausgewählte
Kundenfrist, Evidenzreferenz oder Behauptung über einen bestehenden
Sicherungsbetrieb.

### Provider-/Consumer-Grenze und Implementierungsfreigabe

Der erste Pilot bleibt app-lokal: Der Hintergrundjob ruft keinen fremden
Provider auf, und kein Consumer erhält einen Löschbefehl. V1 liefert weiterhin
nur Policies und Vorschauen. Ein späterer öffentlicher Ausführungsvertrag ist
eine neue Vertragsversion mit Aktivierungs- und Versionshandshake,
Provider-/Consumer-Contract-Tests und eigener repositoryübergreifender
Freigabe; er darf nicht als additive `execute()`-Methode in V1 erscheinen.

Eine spätere Implementierung beginnt erst nach erneuter ausdrücklicher
Freigabe für Schema/Migration, Hold- und Ausführungsdaten, Hintergrundjob und
reale Löschung. Vor ihrer Aktivierung müssen außerdem die Rechtsgrundlage der
Adminfreigabehistorie im Processing-Katalog entschieden, die reale
Backup-/Restoregrenze belegt und alle folgenden Nachweise grün sein:

- Policy: Standard und Grenzzeitpunkt, Verkürzung und Verlängerung für
  Bestandsdaten, fällige Jahresprüfung, beschädigte Konfiguration und
  Revisionwechsel während eines Laufs;
- Berechtigung: DPO-Allow sowie Deny ohne Mitgliedschaft, für nativen Admin,
  temporär freigegebenen Admin und manipulierte Revision jeweils ohne
  Nebenwirkung;
- Eignung: aktiver, noch nicht fälliger, widerrufener, exakt am Stichtag
  fälliger, inkonsistenter und bereits fehlender Datensatz;
- Holds: Setzen, Korrigieren, Prüftermin, Aufheben und Rennen gegen die
  Löschung; ein überfälliger Hold bleibt wirksam;
- Ausführung: Dry-Run-Bindung, stabile Keyset-Seiten, atomarer
  Löschen-plus-Idempotenz-Commit, Rollback vor Commit, wiederholter Lauf,
  parallele Worker und verlorene Antwort nach erfolgreichem Commit;
- Fehler: Provider-, Datenbank-, Lease- und Benachrichtigungsfehler,
  automatischer Backoff, Meldung nach fünf Fehlversuchen, keine verbotene
  Nebenwirkung sowie Entfernung jedes technischen Nachweises nach 30 Tagen;
- Datenschutz: PersonalData-Auskunft enthält nach Löschung keinen gelöschten
  Bezug; Logs, Audit und Meldung enthalten weder UID noch Fachinhalt oder
  rückauflösbare Reststatistik;
- Restore: keine reaktivierte Freigabe, blockierter Zugriff vor Abschluss,
  fortbestehender Hold, erneute Einplanung abgelaufener ungesperrter Daten und
  Einhaltung der ursprünglichen 35-Tage-Grenze;
- Plattform: frische Installation beziehungsweise der nach der geltenden
  Entwicklungsphasenregel erforderliche Reinstall, PostgreSQL-Integration,
  Job-Wiederanlauf und die unveränderte V1-Provider-/Consumer-Matrix.

Die Tests werden bei der Umsetzung vor dem Produktivcode als beobachtbare
Red-Nachweise angelegt. Ein rein statischer oder gemockter Test ersetzt weder
die echte Datenbanktransaktion und Nebenläufigkeit noch den betrieblichen
Backup-/Restore-Nachweis.
