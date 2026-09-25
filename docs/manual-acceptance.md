# Manuelle Abnahme – Data Protection Center

Dieses Formular dokumentiert ausschließlich wiederholbare manuelle Prüfungen.
Es ist keine Produktivfreigabe und enthält keine echten Auskunftsdaten.

## Umgebung

- Datum:
- Nextcloud-Version:
- App-Version und Commit:
- Prüfer*in:

## Kernablauf

- [ ] Self-Service bindet ausschließlich das angemeldete Konto.
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
- [ ] Tastatur, Fokus, kleine Viewports und Scrollverhalten wurden geprüft.

## Ergebnis

- Ergebnis:
- Abweichungen und reproduzierbare Schritte:
- Belege ohne Echtdaten:

## Automatisierte lokale Vorprüfung

Vor der manuellen Abnahme werden ausschließlich lokale, nicht mutierende
Prüfungen ausgeführt und ihre Ergebnisse zusammen mit dem geprüften Commit im
Ergebnisfeld dokumentiert:

| Prüfung | Ergebnis | Aussagegrenze |
|---|---|---|
| `php tests/run.php` | einzutragen | Prüft die app-lokalen PHP-Verträge für Registrierung, Aggregation, Rechte, Privacy, Processing-Metadata, Self-Service, Retention und temporären Adminzugriff. |
| `node tests/run-js.mjs` | einzutragen | Prüft die JavaScript-/App-Verträge sowie Security-, Report-, Retention- und Layout-Smokes. |
| `git diff --check` | einzutragen | Prüft die ausstehenden Änderungen auf Whitespacefehler. |

Diese Vorprüfung verändert weder DDEV-/`occ`-Zustand noch Installation,
App-Aktivierung, Providerlaufzeitdaten oder Retention-Daten. Sie ersetzt weder
die manuelle Bedien- und Integrationsprüfung noch die offene Entscheidung zur
produktiven Retention-Ausführung.
