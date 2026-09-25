# Architektur – Data Protection Center

## Verantwortung

Das Data Protection Center ist die eigenständige Laufzeit-App für den
öffentlichen, versionierten Datenschutzprovidervertrag. Fachapps bleiben
Eigentümerinnen ihrer Daten und liefern ausschließlich kontrollierte
Projektionen.

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

Jede Vorschau entdeckt Provider lazy über den öffentlichen V1-Registry-Event
und fixiert den Bewertungszeitpunkt über alle Folgeseiten. Provider behalten
Datenzugriff und Cursorinterpretation; das Datenschutz-Center aggregiert nur
datenminimierte DTOs und opake Fortsetzungen. Fehlende, deaktivierte,
doppelte oder inkompatible Provider bleiben sichtbar beziehungsweise
unvollständig und werden niemals durch direkten Tabellen-, Datei-, AppConfig-
oder Reflection-Zugriff ersetzt.

Fachliche Datenschutzkonfiguration einschließlich app-eigener
Aufbewahrungsfristen ist ausschließlich Mitgliedern der dedizierten
Nextcloud-Gruppe `Datenschutzbeauftragte` erlaubt. Diese bestehende Gruppen-ID
ist der kanonische Default; die nur für Entwicklungsumgebungen verwendete
Bezeichnung `privacy-officer` ist keine Produktrolle. Native
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
spätere Umsetzung muss jede Policyversion und ihren Wirksamkeitszeitpunkt
24 Monate auditierbar halten; die Gruppe `Datenschutzbeauftragte` überprüft
die Konfiguration mindestens jährlich.

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
inhaltsarme technische Fehlernachweis wird nach 30 Tagen gelöscht. Dieser
Ausführungsvertrag ist nicht implementiert: Policyversion und
Wirksamkeitszeitpunkt, Reihenfolge, Atomarität, Nebenläufigkeit, Idempotenz,
Backupgrenze, Sperrdurchsetzung, Auditvollständigkeit, Fehlerrückbau sowie
Provider-/Consumer-Verhalten müssen vor jeder destruktiven Maßnahme
freigegeben und positiv wie negativ getestet werden. Das Datenschutz-Center
koordiniert nur öffentliche Provider; es liest oder löscht niemals direkt in
Fremdtabellen, fremden Dateien oder fremden App-Speichern.
