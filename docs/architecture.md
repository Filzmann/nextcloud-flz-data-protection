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
fehlende Rechtsgrundlagen, fachliche Verantwortlichkeiten, Retention- sowie
Backup-/Restore-Entscheidungen sind strukturiert als
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
