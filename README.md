# Fronius Wattpilot Flex – IP-Symcon Modul

[![IPS Version](https://img.shields.io/badge/IP--Symcon-7.1%2B-blue)](https://www.symcon.de)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Lokale WebSocket-Integration des **Fronius Wattpilot Flex (C6)** für [IP-Symcon](https://www.symcon.de).  
Keine Cloud, keine API-Keys – vollständig lokal über persistente WebSocket-Verbindung.

---

## ⚡ Funktionen

- **Echtzeit-Daten** – Persistente WebSocket-Verbindung mit Push-Updates
- **Steuerung** – Lademodus, Force-Modus, Ladestrom, Phasenmodus direkt aus dem WebFront
- **Optionale Variablen** – Strom, Spannung, Temperaturen, System-Infos nur bei Bedarf aktivieren
- **Archivierung** – Ladeleistung (Standard) und Gesamtenergie (Zähler) optional loggen
- **Ladestrom-Begrenzung** – Automatisch an Wattpilot-Variante angepasst (11 kW → max 16 A, 22 kW → max 32 A)
- **Splitter/Device-Architektur** – Saubere Trennung von Verbindung und Datenverarbeitung
- **Throttle** – Gebündelte Variablen-Updates über konfigurierbaren Timer (verhindert WebFront-Flut)
- **bcrypt-Cache** – Teure Passwort-Berechnung nur einmalig

---

## 📋 Voraussetzungen

- IP-Symcon **7.1** oder höher
- Fronius Wattpilot Flex (C6) im lokalen Netzwerk erreichbar
- Wattpilot-Passwort (aus der Wattpilot-App → Einstellungen → WLAN-Passwort)

---

## 📦 Installation

### Über den IP-Symcon Module Store (empfohlen)

1. In der IP-Symcon Verwaltungskonsole den **Module Store** öffnen
2. Nach **„Fronius - Wattpilot flex C6"** suchen
3. Modul installieren – fertig!

### Alternativ: Manuelle Installation über GitHub-URL

1. **Kerninstanzen → Modules → Hinzufügen**
2. URL eintragen:
https://github.com/Kelevra-0815/WattpilotFlex
3. Bestätigen

---

## 🔧 Einrichtung

### 1. Splitter-Instanz (Wattpilot Verbindung)

Die Splitter-Instanz wird automatisch mit der Flex-Instanz erstellt. Konfiguration:

| Einstellung | Beschreibung | Standard |
|---|---|---|
| IP-Adresse | IP des Wattpilot im lokalen Netzwerk | – |
| Passwort | Wattpilot-Passwort aus der App | – |
| Reconnect-Check Intervall | Prüft periodisch ob Verbindung steht | 60 s |
| Variablen-Aktualisierung | Timer für gebündelte Updates an Variablen | 60 s |

### 2. Flex-Instanz (Wattpilot Flex)

#### Optionale Variablen

| Einstellung | Beschreibung | Standard |
|---|---|---|
| Strom (L1, L2, L3) | Stromwerte pro Phase anzeigen | ❌ |
| Spannung (L1, L2, L3) | Spannungswerte pro Phase anzeigen | ❌ |
| Temperaturen | Interne Temperatursensoren anzeigen | ❌ |
| Phasen-Details | Aktive Phasen einzeln anzeigen | ❌ |
| System | Firmware, Seriennummer, WLAN, Neustarts | ❌ |
| Extras | Kabel-Limit, 16A-Adapter, Transaktion, Energie-Limit | ❌ |

#### Archivierung / Logging

| Einstellung | Beschreibung | Standard |
|---|---|---|
| Logging Ladeleistung | Archivierung `WP_nrg_ptotal` (Standard-Aggregation) | ❌ |
| Logging Gesamtenergie | Archivierung `WP_eto` (Zähler-Aggregation) | ❌ |

> **Hinweis:** Deaktivierte Variablen werden beim Speichern gelöscht. Leere Kategorien werden automatisch aufgeräumt.

---

## 📊 Variablen

### Immer aktiv

| Kategorie | Variable | Beschreibung | Steuerbar |
|---|---|---|---|
| Fahrzeug & Laden | WP_car | Fahrzeugstatus (Bereit / Lädt / Warte / Fertig / Fehler) | |
| | WP_alw | Laden erlaubt | |
| | WP_frc | Force-Modus (Neutral / Aus / Ein) | ✅ |
| | WP_lmo | Lademodus (Standard / ECO / AutoStop) | ✅ |
| | WP_psm | Phasenmodus (Auto / 1-phasig / 3-phasig) | ✅ |
| | WP_amp | Ladestrom gesetzt (6–32 A) | ✅ |
| | WP_acu | Ladestrom erlaubt (A) | |
| | WP_modelStatus | Modellstatus | |
| | WP_err | Fehlerstatus | |
| Energie | WP_wh | Session-Energie (Wh) | |
| | WP_eto | Gesamtenergie (Wh) | |
| | WP_etop | Gesamtenergie personalisiert (Wh) | |
| Messwerte | WP_nrg_ptotal | Ladeleistung gesamt (W) | |
| | WP_nrg_pl1 / pl2 / pl3 | Leistung L1 / L2 / L3 (W) | |

### Optional (über Konfiguration aktivierbar)

| Gruppe | Variablen | Beschreibung |
|---|---|---|
| Strom | WP_nrg_il1, il2, il3 | Strom L1/L2/L3 (A) |
| Spannung | WP_nrg_ul1, ul2, ul3 | Spannung L1/L2/L3 (V) |
| Temperaturen | WP_tma1, tma2, tma3 | Interne Temperatursensoren (°C) |
| Phasen-Details | WP_pha_l1, l2, l3, WP_pnp | Aktive Phasen einzeln + Anzahl |
| System | WP_fwv, sse, var, rbc, rbt, rssi | Firmware, Serial, Variante, Neustarts, Laufzeit, WLAN |
| Extras | WP_cbl, adi, trx, dwo | Kabel-Limit, 16A-Adapter, Transaktion, Energie-Limit |

---

## 💻 Skript-Funktionen

### Steuerung

```php
// Lademodus setzen (3=Standard, 4=ECO/PV-Überschuss, 5=AutoStop)
WP_SetLademodus($id, 4);

// Force-Modus (0=Neutral, 1=Aus, 2=Ein)
WP_SetForceModus($id, 2);

// Ladestrom setzen (6–32 A, wird automatisch an Variante begrenzt)
WP_SetLadestrom($id, 16);

// Phasenmodus (0=Auto, 1=1-phasig, 2=3-phasig)
WP_SetPhasenmodus($id, 0);

// Schnell-Befehle
WP_StartLaden($id);
WP_StopLaden($id);

// Beliebigen Wattpilot-Key setzen
WP_SetWPValue($id, 'amp', 10);
Status
// Alle Variablen manuell aktualisieren (fordert FullStatus vom Splitter an)
WP_UpdateStatus($id);
Splitter
// Verbindung manuell neu aufbauen
WPSP_Reconnect($id);

// Verbindung prüfen (wird auch vom Timer aufgerufen)
WPSP_CheckConnection($id);

// Verbindungstest (Ausgabe im Formular)
WPSP_TestConnection($id);
________________________________________
🏗️ Architektur
┌──────────────────────────────────────────────────────┐
│  Fronius Wattpilot (Hardware)                        │
│  ws://<IP>/ws                                        │
└────────────────────────┬─────────────────────────────┘
                         │ WebSocket (Push)
┌────────────────────────▼─────────────────────────────┐
│  WebSocket Client (IPS I/O)                          │
└────────────────────────┬─────────────────────────────┘
                         │
┌────────────────────────▼─────────────────────────────┐
│  Wattpilot Splitter                                  │
│  • Authentifizierung (bcrypt + SHA256)               │
│  • Status-Verwaltung (FullStatus + DeltaBuffer)      │
│  • Reconnect-Logik                                   │
│  • Timer: gebündelte Updates an Children             │
│  • Response auf Steuerbefehle → sofort pushen        │
└────────────────────────┬─────────────────────────────┘
                         │
┌────────────────────────▼─────────────────────────────┐
│  Wattpilot Flex (Device)                             │
│  • Variablen-Erstellung (mit Dummy-Kategorien)       │
│  • Optionale Variablengruppen                        │
│  • Steuerung (RequestAction → WebFront)              │
│  • Archivierung (Standard + Zähler)                  │
│  • Ladestrom-Begrenzung nach Variante               │
└──────────────────────────────────────────────────────┘
Datenfluss
Quelle	Verhalten	Latenz
deltaStatus (Messwerte)	Im DeltaBuffer gesammelt → Timer pusht gebündelt	max. UpdateInterval
response (Steuerbefehl-Feedback)	Sofort an Device gepusht	~0 ms
fullStatus (Verbindungsaufbau / ForceUpdate)	Sofort an Device gepusht	~0 ms
________________________________________
📁 Modulstruktur
WattpilotFlex/
├── library.json
├── README.md
├── LICENSE
├── WattpilotSplitter/
│   ├── module.json
│   ├── module.php
│   └── form.json
└── WattpilotFlex/
    ├── module.json
    ├── module.php
    └── form.json
________________________________________
⚠️ Hinweise
•	Das Variablen-Aktualisierungsintervall im Splitter (Standard: 60s) bündelt eingehende Messwerte. Steuerbefehle werden immer sofort zurückgemeldet.
•	Bei der 11 kW Variante wird der Ladestrom automatisch auf max. 16 A begrenzt (Profil + Befehl).
•	Variablen werden nur bei tatsächlicher Wertänderung geschrieben – unnötige WebFront-Aktualisierungen und Archiv-Einträge werden vermieden.
•	WP_nrg_ptotal wird bei ForceUpdate / fullStatus immer geschrieben (auch bei gleichem Wert), damit Archive einen Datenpunkt erhalten.
________________________________________
🙏 Danksagung
Das WebSocket-Protokoll des Fronius Wattpilot ist nicht offiziell dokumentiert.
Die Implementierung basiert auf der Vorarbeit folgender Projekte:
- [mk-maddin/wattpilot](https://github.com/mk-maddin/wattpilot) – Python-Implementierung
- [joscha82/wattpilot](https://github.com/joscha82/wattpilot/pkgs/container/wattpilot) – Container-Integration
________________________________________
👨‍💻 Entwicklung
Entwickelt von Kelevra26 (Kelevra-0815) mit Unterstützung von KI.
Die KI unterstützte bei WebSocket-Protokoll, PHP-Architektur und Dokumentation.
Idee, Anforderungen und Tests stammen vom Autor.

---
## Lizenz
MIT License – siehe [LICENSE](LICENSE)
