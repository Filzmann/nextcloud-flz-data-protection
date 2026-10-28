# Manuelle Abnahme – Data Protection Center

Dieses Formular dokumentiert ausschließlich wiederholbare manuelle Prüfungen.
Es ist keine Produktivfreigabe und enthält keine echten Auskunftsdaten.

## Umgebung

- Datum:
- Nextcloud-Version:
- App-Version und Commit:
- Prüfer*in:

## Kernablauf

- [ ] Der persönliche Nextcloud-Einstieg `Datenschutz` zeigt den Art.-15-
      Self-Service und bindet ausschließlich das angemeldete Konto.
- [ ] Der direkte App-Einstieg zeigt keine persönliche Art.-15-Auskunft;
      gewöhnliche Konten erhalten dort keine fachlichen Steuerelemente.
- [ ] Fehlende, inkompatible und fehlerhafte Provider werden sichtbar ausgewiesen.
- [ ] Teilberichte werden nicht als vollständig bezeichnet.
- [ ] Fresh Install und Upgrade einer bereits aktivierten App legen eine fehlende
      Gruppe `Datenschutzbeauftragte` an; eine vorhandene Gruppe und ihre
      Mitgliedschaften bleiben unverändert.
- [ ] Ein Mitglied von `Datenschutzbeauftragte` ohne nativen Adminstatus kann
      die app-lokale Freigabesteuerung öffnen, einem bestätigten Admin für
      höchstens 24 Stunden freigeben und widerrufen.
- [ ] Ein nativer Admin ohne Mitgliedschaft in `Datenschutzbeauftragte` kann
      weder Freigaben noch Historie verwalten und erhält ohne aktive Freigabe
      nur die sichere Eintrittsmeldung ohne Direktlink.
- [ ] Ein Konto mit beiden Rollen erhält in der Eintrittsmeldung den
      Direktlink; gewöhnliche Konten sehen weder Meldung noch Link.
- [ ] Unbekannte Zielkonten, Laufzeiten über 24 Stunden und manipulierte
      Widerrufe bleiben ohne Freigabe- oder Historienmutation.
- [ ] Retention-Preview verändert keine Daten.
- [ ] Retention-Folgeseiten behalten den ursprünglichen Bewertungszeitpunkt,
      ersetzen veraltete Leer-/Teilstatusanzeigen und enthalten keine UIDs
      oder freien Fachtexte.
- [ ] Installiert/aktiv, deaktiviert, fehlend, inkompatibel, Update,
      Deinstallation/Neuinstallation und Rollback wurden für jeden migrierten
      Consumer im freigegebenen Lifecycle-Harness geprüft.
- [ ] Die Bereichslinks im App-Einstieg sind vollständig per Tastatur
      erreichbar und besitzen einen sichtbaren Fokus.
- [ ] Persönliche Einstellung und App-Bereiche funktionieren bei kleinen
      Viewports ohne horizontalen Seitenüberlauf; breite Tabellen scrollen
      nur in ihrem inneren Wrapper und der App-Root vertikal.

## Ergebnis

- Ergebnis:
- Abweichungen und reproduzierbare Schritte:
- Belege ohne Echtdaten:

## Vorbereitung

Vor der manuellen Abnahme die vorgesehenen lokalen Prüfungen ausführen und ihre
Ergebnisse zusammen mit dem geprüften Commit im Ergebnisfeld dokumentieren.
Die Vorprüfung ersetzt weder die manuelle Bedien- und Integrationsprüfung noch
eine produktive Retention-Entscheidung.
