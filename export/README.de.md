# Servereinrichtung für PDF Workspace

PDF Workspace. Copyright 2026 Andreas Giesen <andreas@108design.com>.
GPL v3 oder neuer.

## Geschützte PDFs einrichten

1. Die Serveradministration installiert Popplers `pdftoppm` auf dem Moodle-Server.
   Unter [Debian](https://packages.debian.org/stable/poppler-utils) und
   [Ubuntu](https://packages.ubuntu.com/noble/poppler-utils) gehört es zum Paket
   `poppler-utils`. Prüfe anschließend den Pfad und die installierte Version.

   ```sh
   sudo apt install poppler-utils
   command -v pdftoppm
   pdftoppm -v
   ```

2. Die Moodle-Website-Administration trägt den absoluten Pfad, meist
   `/usr/bin/pdftoppm`, unter **Website-Administration → Plugins → Aktivitäten →
   PDF Workspace → PDF-Renderer für geschützte Dokumente**
   (`mod_pdfworkspace/restrictedpdftoppm`) ein.
3. Prüfe, ob Moodles Cron das Programm mit `proc_open` starten kann. Wenn
   auf dem Linux-Server `open_basedir` aktiv ist, prüfe in den PHP-Einstellungen
   den Zugriff auf das Programm, Moodles temporäres Verzeichnis und
   `/dev/null` für die Standardstreams des Renderers. Lade in einer Testaktivität
   ein kleines geschütztes PDF hoch und öffne nach Abschluss der Hintergrundaufgabe die Seiten im Browser.

Verwende in den Aktivitätseinstellungen das normale PDF-Uploadfeld. Setze den
Haken bei **Alle neu hochgeladenen PDFs schützen** und speichere. Das Kästchen
gilt für alle neuen Dateien dieses Uploads. Lade reguläre und geschützte PDFs
in getrennten Schritten hoch und speichere jeweils. Pro geschützter Datei gelten
500 Seiten, 64 MiB für das Original und 256 MiB für die Seitenbilder als
Obergrenzen. Die Uploadgrenzen von Moodle und PHP gelten zusätzlich.

Moodles Cron verarbeitet pro Website jeweils eine geschützte PDF im Hintergrund.
Die Aktivitätseinstellungen zeigen den Status und gegebenenfalls den Fehlergrund.
Fertige JPEG-Seitenbilder werden beim Lesen seitenweise geladen. Ein Schloss im Dokumenttab
kennzeichnet das geschützte PDF. Zeichenwerkzeuge und Diskussionen an den
Markierungen funktionieren im gemeinsamen Viewer. Die Seitenbilder unterstützen
weder Textauswahl noch PDF-Textsuche. Bildschirmaufnahmen bleiben möglich.

Standardmäßig hat nur die Website-Administration die folgenden Rechte. Du kannst
sie bei Bedarf getrennt an weitere Rollen vergeben.

- **Geschützte Dokumente anlegen und entfernen**
  (`mod/pdfworkspace:managerestricteddocuments`)
- **Originale geschützter Dokumente herunterladen**
  (`mod/pdfworkspace:downloadrestricteddocuments`). Dieses Recht gilt unabhängig
  von der regulären Download-Freigabe der Aktivität.

Reguläre Exporte, Kursimporte und portable Moodle-Kurssicherungen lassen
geschützte Dokumente und ihre Anmerkungen aus. Sichere für ein Server-Backup
die Moodle-Datenbank und den Moodle-Dateispeicher gemeinsam.

## Kombinierte PDF-Downloads

Die normale PDF-Ansicht, der Original-PDF-Download und der reine Kommentar-Download
benötigen kein Python. Für PDFs mit Markierungen und Kommentaren sowie für den
gemeinsamen Workspace-Download braucht der Zielserver
Python 3.11 oder neuer, das Modul `venv` sowie pypdf, ReportLab und die
AES-Unterstützung durch cryptography. Auf Debian/Ubuntu wird das Venv-Modul
üblicherweise über das Betriebssystempaket `python3-venv` bereitgestellt.

Der Einzeldownload kombiniert ein reguläres PDF mit den sichtbaren Markierungen
und Kommentaren. Für den Workspace-Download legst Du in der PDF-Liste der
Aktivitätseinstellungen fest, welche regulären Dateien in welcher Reihenfolge
zusammengeführt werden.
Die Tab-Reihenfolge bleibt unabhängig davon. Beide Varianten nutzen diese Auswahl;
mit Kommentaren werden ausschließlich die für den herunterladenden Nutzer sichtbaren
Markierungen und Kommentare aufgenommen. PDF-Lesezeichen tragen die Dokumentnamen.
Die Rechte `mod/pdfworkspace:downloadworkspace` und
`mod/pdfworkspace:downloadworkspacecomments` werden unabhängig geprüft. Teilnehmer
brauchen zusätzlich die jeweilige Freigabe in der Aktivität; ein ausdrückliches
Berechtigungsverbot bleibt wirksam. Es sind keine zusätzlichen Python-Bibliotheken nötig.

## Separate Python-Umgebung einrichten

Lege eine eigene Venv **außerhalb aller Webroots** an. Interpreter, Venv und deren
Bibliotheken gehören nicht in das Moodle-Plugin-ZIP. Sie hängen vom Betriebssystem
ab; eine Venv muss auf jedem Zielserver neu erstellt werden.

Mit `--copies` bleibt der Interpreter innerhalb der Venv. Bei PHP `open_basedir`
kann ein Symlink nach `/usr/bin/python` die Datei- und Zugriffsprüfung des Plugins
verhindern. Der Venv-Pfad muss innerhalb der für PHP erlaubten Verzeichnisse liegen;
die Beschränkung sollte dafür nicht global gelockert werden.

Prüfe als Server-Admin zuerst, ob `python3` mindestens Version 3.11 hat.
Passe beide absoluten Pfade an Deine Installation an. Bei Moodle mit separatem
Webverzeichnis führt der Pluginpfad über `public/mod/pdfworkspace`.

```sh
python3 --version
python3 -m venv --copies /srv/pdfworkspace-export/venv
/srv/pdfworkspace-export/venv/bin/python -m pip install -r /absoluter/pfad/zu/moodle/mod/pdfworkspace/export/requirements.txt
/srv/pdfworkspace-export/venv/bin/python -m pip check
```

`requirements.txt` legt die pypdf- und ReportLab-Versionen fest. Der Zusatz
`pypdf[crypto]` installiert auch die benötigte AES-Bibliothek. Führe die Einrichtung
mit dem Konto durch, das diese Umgebung verwaltet.

Das Konto, unter dem Moodle-PHP läuft, muss die Verzeichnisse betreten, die
Bibliotheken und das Exportskript lesen und den Interpreter ausführen können.
Außerdem benötigt es Schreibzugriff auf Moodles temporäres Verzeichnis. PHP muss
`proc_open` erlauben und auf den Interpreter-Pfad zugreifen können; beachte dabei
auch `open_basedir`. Das Exportskript hat pro Aufruf ein Zeitlimit von 120 Sekunden.

## Interpreter in Moodle hinterlegen

Trage unter **Website-Administration → Plugins → Aktivitäten → PDF Workspace →
Python für kombinierten PDF-Export** den absoluten Interpreter-Pfad ein, zum Beispiel
`/srv/pdfworkspace-export/venv/bin/python`. Die Einstellung heißt technisch
`mod_pdfworkspace/exportpython`.

Alternativ kannst Du sie über Moodles CLI setzen. Führe den Befehl mit dem
Moodle-Servicekonto im Moodle-Verzeichnis mit `admin/cli` aus:

```sh
php admin/cli/cfg.php --component=mod_pdfworkspace --name=exportpython --set=/srv/pdfworkspace-export/venv/bin/python
```

Prüfe die Bibliotheken ebenfalls **mit dem Moodle-Servicekonto**:

```sh
/srv/pdfworkspace-export/venv/bin/python -c 'import pypdf, reportlab, cryptography; from cryptography.hazmat.primitives.ciphers import Cipher, algorithms; print("PDF-Export-Bibliotheken verfügbar")'
```

Der kombinierte Download wird angeboten, wenn der konfigurierte Interpreter
ausführbar ist, PHP Prozesse starten darf und die bestehenden Download-Berechtigungen
es zulassen. Nach Plugin-Updates prüfst Du `requirements.txt` und installierst
geänderte Abhängigkeiten in dieser separaten Umgebung. Sichere die Pfadeinstellung
und dokumentiere die installierten Bibliotheksversionen. Die Installation des
Plugin-ZIPs allein richtet keine Python-Bibliotheken ein.

## Verschlüsselte PDFs

Verschlüsselte PDFs, die sich ohne Öffnungspasswort lesen lassen, werden akzeptiert,
wenn die Entschlüsselung mit einem leeren Passwort gelingt.
PDFs, die tatsächlich ein Öffnungspasswort benötigen, werden weiter abgewiesen;
eine Passworteingabe ist im Plugin nicht vorgesehen. Für AES ist die oben genannte
cryptography-Bibliothek nötig.

Der Download erzeugt eine neue PDF-Datei; die gespeicherte Originaldatei bleibt
unverändert. Kommentare erscheinen als PDF-Notizen mit Text, Autor und Datum.
Formatierungen und eingebettete Bilder werden in diesen Notizen nicht übernommen.
Für Textfelder nutzt das Skript DejaVu Sans oder Liberation Sans, falls auf dem
Server vorhanden, sonst Helvetica. Unicode-Schriftarten sind daher empfehlenswert.

Für einen funktionalen Exporttest kannst Du `tests/combined_export_test.py` mit der
gleichen Python-Umgebung ausführen und einen kombinierten Download im Browser prüfen.
