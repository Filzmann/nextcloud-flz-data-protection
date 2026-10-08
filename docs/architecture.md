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

### Öffentlicher Risikoscope-Vertrag V1

Das Datenschutz-Center besitzt zusätzlich den neutralen öffentlichen
`ScopeAuthorizationQueryEvent` V1 für ausdrücklich risikoreiche Funktionen.
Normale Fachfunktionen hängen nicht von diesem Vertrag ab. Eine Fachapp fragt
nur ihre eigene App-ID, eine geschlossene Scope-ID und die erwartete
Vertragsversion ab. Die Antwort enthält ausschließlich den neutralen Zustand
`authorized`, `denied`, `incompatible` oder `unanswered`; Policyrevision,
Evidenzreferenz, Wirksamkeitszeitraum, DPO-Bestätigung und andere
Kundeninformationen verlassen das Datenschutz-Center nicht.

Der erste und derzeit einzige Scope ist
`flzroom.secretariat_foreign_booking_intervention` mit `flzroom` als einzigem
Consumer. Fehlende, deaktivierte, noch nicht wirksame, abgelaufene,
beschädigte oder versionsinkompatible Konfigurationen bleiben fail-closed.
Ein aktiver Scope ersetzt weder Fachrolle noch Objektprüfung, Begründung,
Audit oder Benachrichtigung der Fachapp. Er eröffnet insbesondere keinen
allgemeinen Auswertungs- oder Fremddatenzugriff.

Die kundenlokale Konfiguration liegt ausschließlich in der app-eigenen
Tabelle `flz_dp_risk_scope_auth` und wird additiv als revisionsgesicherte
Historie gespeichert. Der eindeutige Datenbankschlüssel aus Scope und
Revision macht den Append atomar: Bei zwei Schreibvorgängen auf derselben
Ausgangsrevision gewinnt genau einer, der andere wird als Konflikt abgewiesen.
Vorhandene Revisionen besitzen keinen Update- oder Löschpfad. Das auslieferbare
Paket enthält keine Kunden-, Vereinbarungs-, Rechtsgrundlagen- oder
DPO-Werte. Ausschließlich aktuelle Mitglieder von
`Datenschutzbeauftragte` dürfen über die CSRF-geschützte API
`/api/v1/risk-scope-authorizations` eine Revision mit Policy- und
Evidenzreferenz, Wirksamkeitsbeginn, Ablaufzeitpunkt und ausdrücklicher
DPO-Bestätigung anlegen. Policy- und Evidenzreferenzen sind opake,
personenfreie Kennungen und keine Freitextfelder. Die öffentliche
Entscheidung projiziert davon nur das Boolean-Ergebnis. Das Verbot von
Leistungs- und Verhaltenskontrolle ist
eine nicht konfigurierbare Produktgrenze; entsprechende Kennzahlen, Exporte,
Rankings oder Zweckwechsel sind kein Scope.

Die Tabelle wird additiv ab Version `0.1.4` angelegt. Der zuvor untersuchte
AppConfig-Entwurf war kein veröffentlichter oder zu erhaltender Stand; deshalb
gibt es keinen AppConfig-Import und keine destruktive Datenmigration. Ein
Code-Rollback lässt die ungenutzte Tabelle bestehen. Fehlt der kompatible
Provider nach einem Rollback, bleiben Consumer fail-closed.

## Processing-Metadaten

Der kanonische app-eigene Katalog liegt unter
`resources/privacy-processing.json`. Er beschreibt derzeit die Verarbeitungen
`article_15_aggregation` und `temporary_admin_full_access`. Bekannte Zwecke
und technische Schutzgrenzen stammen aus der bestehenden App-Architektur;
noch offene Rechtsgrundlagen, fachliche Verantwortlichkeiten sowie weitere
noch nicht entschiedene Datenschutz- und Betriebsfragen sind strukturiert als
`PRIVACY-DECISION-REQUIRED` ausgewiesen. Die Risikoscope-Konfiguration führt
keine Personenkennung oder sonstige personenbezogene Laufzeitklasse; eine
spätere Akteurs- oder Freigabeauditierung mit Personenbezug müsste den Katalog
und den app-eigenen `PersonalDataProvider` im selben Auftrag erweitern.

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

Berichtsinhalte werden nicht zentral persistiert. App-lokal gespeichert
werden sicherheitsrelevante Adminfreigaben, Policy- und technische
Aktivierungsrevisionen sowie optionale, davon getrennte
Kundendokumentation. Das Datenschutz-Center besitzt keine fremden Daten und
keine globale Beschäftigten-Lifecycle-Hoheit. Es koordiniert ausschließlich
öffentliche Provider; jede datenbesitzende App prüft und löscht ihre eigenen
Datensätze.

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

Die eigene Adminfreigabehistorie wird nach der empfohlenen Policy sechs Monate
ab ihrem tatsächlichen Ende vollständig gelöscht, ohne anonymisierten oder
statistischen Restbestand. Aktive Holds blockieren die Löschung. Nach einem
Restore wird der technische Schutzstand erneut verifiziert, die Frist aus dem
ursprünglichen Ende neu bewertet und niemals eine fachliche Freigabe
reaktiviert.

V1 bleibt der read-only Vorschauvertrag und besitzt keine `execute()`-Methode.
Der getrennte öffentliche V2-Vertrag registriert DELETE-Provider lazy und
versionssicher. Der stündliche Koordinator berücksichtigt nur die in der
technischen Aktivierungsrevision fest zugelassenen Policies. Provider prüfen
Kandidat, Token, Policyversion, Fälligkeit und Hold unmittelbar vor der
Löschung erneut und führen die Änderung innerhalb ihrer eigenen
Transaktionsgrenze aus. Fehler, unbekannte Zustände, Versionskonflikte und
inkompatible Provider stoppen fail-closed; das Center greift niemals direkt
auf fremde Tabellen, Dateien oder App-Speicher zu.

## DP-07-Ausführungspilot V2

### Status, Aktivierung und Verantwortungsgrenze

Der V2-Pilot ist implementiert, aber noch nicht auf der unterstützten realen
Datenbank- und Nextcloud-Runtime vollständig abgenommen. Er startet nach einer
Installation oder einem Upgrade deaktiviert. Nur ein aktuelles natives
Nextcloud-Administrationskonto darf ihn über die CSRF-geschützte technische
Konfiguration aktivieren oder wieder deaktivieren. Jede Änderung erzeugt eine
neue, revisionsgesicherte Zeile; eine konkurrierend veraltete Revision wird
ohne Nebenwirkung abgewiesen.

Diese technische Aktivierung ist von fachlicher Datenschutzkonfiguration
getrennt. Sie verlangt und speichert weder Rechtsgrundlage noch Betriebs- oder
Dienstvereinbarung, DPO-/Betriebsratsbestätigung, Kunden- oder
Evidenzreferenz. Solche Entscheidungen und Nachweise verantwortet jeder
Betreiber außerhalb des Produktpakets. Ihr Fehlen blockiert den technischen
DELETE-Pfad nicht. Eine vorhandene optionale DPO-Profilhistorie bleibt eine
getrennte Dokumentation und wird vom V2-Koordinator weder gelesen noch als
Freigabe interpretiert.

Die Aktivierung lässt ausschließlich die fest im Produkt vorgeschlagenen
Pilot-Policies zu:

- `flz_data_protection:temporary_admin_access_history_delete` mit `P6M`
  ab tatsächlichem Ende der Freigabe;
- `flzroom:temporary_admin_access_history_delete` mit `P6M` ab tatsächlichem
  Ende der Freigabe;
- `flzroom:room_booking_delete` mit `P1Y` ab Buchungsende.

Die Vorschläge sind keine kundenlokale Rechts- oder Beteiligungsentscheidung.
Es gibt keine benutzerbedienbare Einzellöschung und keine Auswahl einzelner
Personen oder Datensätze durch native Administration. Die tatsächliche
Ausführung erfolgt ausschließlich durch den Hintergrundjob unter technischer
Systemidentität.

### Technische Fail-closed-Gates

Eine fehlende, deaktivierte, unbekannte, beschädigte, künftig datierte,
veraltete oder konkurrierend überholte Aktivierungsrevision ergibt `REVIEW`
und keine Löschung. Dasselbe gilt bei abweichendem Policy-Scope, unbekannter
oder inkompatibler Provider-/Vertragsversion, ungültigem oder geändertem
Kandidaten, fehlender Fälligkeit, aktiver Sperre, Integritätskonflikt oder
nicht atomar ausführbarer Löschung.

Zur Aktivierung gehören ausschließlich technische Betriebswerte:

- reguläre Backup-Aufbewahrung von 1 bis 365 ganzen Kalendertagen;
- technischer Puffer von 0 bis 5 ganzen Kalendertagen, insgesamt höchstens
  370 Tage;
- bereits vergangene Zeitpunkte der letzten Backup- und Restore-Prüfung;
- ein zukünftiger nächster Prüftermin, der nach beiden Prüfzeitpunkten liegt
  und spätestens ein Jahr nach der Aktivierungsrevision fällig ist.

Zukünftige Prüfzeitpunkte, ein fälliger oder logisch widersprüchlicher
Prüftermin und Werte außerhalb dieser Grenzen blockieren fail-closed. Die App
prüft damit Konsistenz und Aktualität der technischen Betreiberangabe; sie
kann externe Sicherungsmedien nicht selbst untersuchen und behauptet keinen
vollständigen Entfall aus Backups.

### V1-/V2-Vertrag, Provider und Holds

V1 bleibt der read-only Vorschauvertrag und erhält keine `execute()`-Methode.
V2 registriert ausführende Provider lazy, versionsgeprüft und ohne direkten
Zugriff des Datenschutz-Centers auf fremde Tabellen, Dateien oder
Konfigurationen. Jede Datenowner-App ermittelt ihre Kandidaten, prüft Trigger,
Policyversion, Ausführungstoken, Fälligkeit und Hold unmittelbar vor der
Mutation erneut und löscht innerhalb ihrer eigenen Transaktionsgrenze. Ein
Fehler darf nicht als erfolgreiche Löschung protokolliert werden.

Ein Hold gilt nur für den konkret benannten Datensatz und die Policy. Er
enthält einen begrenzten Grundcode, eine opake Referenz und einen spätestens
nach 90 Tagen fälligen Prüftermin. Ein überfälliger Prüftermin hebt ihn niemals
automatisch auf. Nur die dafür autorisierte Datenschutzrolle der
Datenowner-App darf ihn setzen oder aufheben; eine kundenlokale
Betriebsratsbeteiligung wird nicht als Produktrolle hardcodiert.

Der Koordinator fixiert den Bewertungszeitpunkt. Provider müssen eine
veraltete Policyversion, einen veränderten Kandidaten, ein ungültiges Token,
einen zwischenzeitlich gesetzten Hold und konkurrierende Ausführung ohne
verbotene Nebenwirkung abweisen. Die wirksame Löschung und ihr minimaler
technischer Nachweis bilden eine atomare Datensatzgrenze; gelöschte UIDs,
Fachinhalte und personenbeziehbare Reststatistiken werden nicht als zentraler
Nachweis gespeichert.

### Backup, Restore und offene Runtime-Abnahme

Mit dem Commit ist die Löschung im aktiven Nextcloud-System wirksam. Ein
Backup kann den zuvor enthaltenen Datensatz bis zum Ende der technisch
konfigurierten Aufbewahrung von höchstens 365 plus 5 Tagen enthalten. Nach
einem Restore werden ursprünglicher Trigger, aktuelle Policy, Holds,
Providerkompatibilität und technische Aktivierung erneut bewertet. Eine
wiederhergestellte Adminfreigabe wird niemals reaktiviert; abgelaufene,
ungesperrte Daten werden wieder zur Löschung eingeplant. Unklarer oder
veralteter Restore-/Prüfstatus stoppt weitere DELETE-Läufe.

Die lokalen Unit- und Contract-Tests ersetzen nicht die noch offene
Runtime-Abnahme. Vor einem Releaseurteil sind mindestens frische Installation
beziehungsweise der nach der Entwicklungsphasenregel erforderliche Reinstall,
Upgrade, reale Migration, Job-Wiederanlauf, echte Datenbanktransaktion,
Nebenläufigkeit, Rollback vor Commit, Provider-/Consumer-Kombination und
Restore-Quarantäne auf der unterstützten Datenbankmatrix nachzuweisen. Bis
diese Nachweise vorliegen, ist der Pilot nicht als releasefertig oder
vollständig runtimeverifiziert zu bezeichnen.
