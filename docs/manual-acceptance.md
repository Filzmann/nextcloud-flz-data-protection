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
- [ ] Review-Rechte und temporärer Adminvollzugriff wurden positiv und negativ geprüft.
- [ ] Retention-Preview verändert keine Daten.
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
