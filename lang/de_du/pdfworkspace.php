<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * German strings for the 108design visibility additions.
 *
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['audiencepermissions_help'] = 'Lege fest, welche Zielgruppen Teilnehmer/innen und Trainer/innen beim Erstellen wählen können. Alle Antworten einer Unterhaltung behalten die Zielgruppe der Anmerkung.';
$string['error:annotationaudience'] = 'Diese Zielgruppe ist für deine Rolle in dieser Aktivität nicht erlaubt.';
$string['error:audiencenone'] = 'Wähle für jede Rolle mindestens eine Zielgruppe.';
$string['error:annotationrecipient'] = 'Wähle vor dem Erstellen der Anmerkung eine/n eingeschriebene/n Teilnehmer/in aus.';
$string['noCommentsYet'] = 'Noch kein Kommentar. Du kannst einen hinzufügen.';
$string['documentdeleteunconfirmed'] = 'Markiere die Lösch-Checkbox erneut und bestätige die Warnung.';
$string['workspacefiles_help'] = 'Wähle die PDFs für den gemeinsamen Download aus und lege ihre Reihenfolge fest. Diese Reihenfolge gilt für beide Downloadvarianten; die PDF-Tabs bleiben unverändert. Neue Aktivitäten enthalten zunächst alle PDFs. Später hinzugefügte PDFs sind zunächst nicht ausgewählt. Speichere neue Uploads zuerst, um sie anschließend hier auszuwählen.';
$string['error:workspaceorder'] = 'Bitte gib eine gültige Position in der PDF-Liste an.';
$string['courseimportsections_help'] = 'Wähle einen oder mehrere Abschnitte. Beim Speichern werden alle auswählbaren PDFs daraus übernommen.';
$string['courseimportfiles_help'] = 'Klappe einen Abschnitt auf und markiere die PDFs, die du übernehmen möchtest. Der Zähler hier bezieht sich auf diese Dateiauswahl. „Ganze Kursabschnitte übernehmen“ gilt zusätzlich.';
$string['courseimporthideskipped'] = 'Einige Quellen bleiben sichtbar, weil nur ein Teil ihrer Dateien übernommen wurde, sich die Quelle geändert hat oder dir das Recht zum Verbergen fehlt.';
$string['courseimportunavailable'] = 'Mindestens eine ausgewählte Datei oder ein Abschnitt ist nicht mehr für die Übernahme verfügbar. Prüfe deine Auswahl.';
$string['adminhelp_courseimport_help'] = 'Die Option verbirgt Datei- und Verzeichnisressourcen, wenn alle ihre Dateien übernommen wurden und du sie verbergen darfst. Quell-Workspaces bleiben sichtbar.';
$string['adminhelp_courseimport_article'] = '<p>Über „Ganze Kursabschnitte übernehmen“ findest du direkt einen Abschnitt. Unter „PDFs im Kurs auswählen“ markierst du einzelne Dateien in Abschnitten, Verzeichnissen und Workspaces. Beide Einstiege lassen sich kombinieren. Eine PDF, die du über beide auswählst, wird einmal übernommen.</p><p>Ausgeblendete Quellen und Quellen mit Zugangsbeschränkungen fehlen in der Auswahl. Aus anderen Workspaces kannst du PDFs übernehmen, wenn du die Aktivität sehen und ihre Dateien verwalten darfst. Markierungen und Diskussionen bleiben im Quell-Workspace. Der Ziel-Workspace speichert eine eigene Kopie der PDF.</p><p>Wenn du die Option zum Verbergen nutzt, bleiben Quell-Workspaces und Verzeichnisse mit weiteren Dateien im Kurs sichtbar. Verborgene Dateien und Verzeichnisse kannst du später über das Kursmenü wieder anzeigen.</p>';
$string['documentalreadyexists'] = '„{$a}“ ist bereits vorhanden. Entferne zuerst das PDF ohne Anmerkungen oder wähle einen anderen Dateinamen.';
$string['nomyposts'] = 'Du hast in dieser Aktivität noch keine Beiträge verfasst.';
$string['documentsupload_help'] = 'Füge dieser Aktivität PDFs hinzu. Dokumente mit vorhandenen Anmerkungen können nicht ersetzt oder entfernt werden.';
$string['setting_fileupload_help'] = 'Füge dieser Aktivität PDFs hinzu. Dokumente mit vorhandenen Anmerkungen können nicht ersetzt oder entfernt werden.';
$string['aiquestions_provider_desc'] = 'Wähle eine konfigurierte Textanbieter-Instanz für die KI-Fragenanalyse oder überlasse die Auswahl der Moodle-Reihenfolge.';
$string['restricteduploadselectionchanged'] = 'Die Auswahl stammt aus einer früheren Version des Formulars. Setze den Haken für alle neuen PDFs oder lade die Einstellungen neu.';
$string['adminhelp_restrictedupload_help'] = 'Das Kästchen gilt für alle Dateien im Uploadfeld darüber. Lade reguläre und geschützte PDFs getrennt hoch und speichere nach jedem Upload.';
$string['adminhelp_restrictedupload_article'] = '<p>Lade zuerst die regulären PDFs hoch und speichere die Einstellungen. Öffne sie erneut, lade die geschützten PDFs hoch und aktiviere <strong>Alle neu hochgeladenen PDFs schützen</strong>. Speichere erneut.</p><p>Moodle bereitet die Seiten im Hintergrund auf. Lade die Aktivität neu, um die fertigen Dokumente zu sehen. Die Einstellungen zeigen ausstehende Uploads und Fehler bei der Verarbeitung. Dort kannst du solche Uploads entfernen und Ersatz hochladen.</p><p>Bereits gespeicherte PDFs behalten ihren Schutzstatus. Für Dateien aus <strong>PDFs im Kurs auswählen</strong> gilt diese Option nicht.</p>';
$string['restrictedremoveconfirm'] = 'Möchtest du „{$a}“ und seine Seitenansicht aus diesem Workspace entfernen?';
$string['restrictedinvalidtitle'] = 'Gib einen Anzeigenamen mit höchstens 160 Zeichen ein.';
$string['restrictedinvalidpdf'] = 'Wähle eine PDF-Datei bis 64 MiB aus.';
$string['restrictedrenderfailed'] = 'Die Seitenansicht konnte nicht erzeugt werden. Prüfe, ob sich die PDF ohne Passwort öffnen lässt.';
$string['adminhelp_restricteddocuments_article'] = '<p>Ein Schloss kennzeichnet geschützte PDFs in der Dokumentleiste. Markiere die Seiten wie gewohnt und klicke auf eine Markierung, um ihre Kommentare zu öffnen. Mit dem Textmarker kannst du frei zeichnen. Eine Textauswahl ist auf Seitenbildern nicht möglich.</p><p>Mit dem separaten Downloadrecht findest du das Original im Downloadmenü.</p>';
$string['restrictedmarklimit'] = 'Auf dieser Seite kannst du bis zu 200 eigene Markierungen speichern.';
$string['restrictedmarkforbidden'] = 'Du darfst dieses Werkzeug hier nicht verwenden.';
$string['restrictedmarkinvalidtext'] = 'Gib für das Textfeld bis zu 160 Zeichen ein.';
$string['restrictedmarkinvalidcomment'] = 'Gib einen Kommentar mit bis zu 2000 Zeichen ein.';
$string['adminhelp_restrictedsetup_article'] = '<p>Die Website-Administration hinterlegt den Pfad zu Popplers <code>pdftoppm</code>. Berechtigte Personen laden PDFs in den Aktivitätseinstellungen hoch und aktivieren <strong>Alle neu hochgeladenen PDFs schützen</strong>. Bereits anderswo veröffentlichte Kopien behalten ihre bisherigen Rechte.</p><p>Geschützte PDFs und ihre Kommentare bleiben außerhalb von Kursimporten, Workspace-Exporten und portablen Moodle-Backups. Sichere die Moodle-Datenbank und den Dateispeicher gemeinsam.</p>';
$string['aiquestions_policy'] = 'Stimme der Moodle-KI-Richtlinie zu und wählst du die Aktion erneut.';
$string['aiquestions_unavailable'] = 'Die Fragenanalyse ist derzeit nicht verfügbar. Prüfe die Freigaben und die Moodle-KI-Einstellungen.';
$string['adminhelp_aipilot_article'] = '<p>Die Website-Administration erlaubt die KI-Fragenanalyse und wählt den Anbieter. Öffne dann <strong>Kurs → Einstellungen</strong> und speichere die Freigabe im Abschnitt <strong>KI-Fragenanalyse</strong>. Öffne anschließend die <strong>Einstellungen</strong> des PDF Workspace und aktiviere sie dort im gleichnamigen Abschnitt. Wird eine höhere Freigabe ausgeschaltet, pausieren die darunter gespeicherten Freigaben.</p><p>Eine Analyse startet erst bei Auswahl einer Aktion. Moodle übermittelt bis zu 24 ausgewählte Fragetexte an den konfigurierten KI-Anbieter und speichert Prompt und Antwort im KI-Subsystem. Private, verborgene und gelöschte Fragen, PDFs sowie Anhänge sind von der Auswahl ausgeschlossen. Prüfe die vorgeschlagenen Themen anhand der verlinkten Fragen.</p>';
$string['modulename_help'] = 'Lade ein oder mehrere PDFs hoch. Teilnehmer/innen und Trainer/innen können darin Markierungen und Kommentare mit den erlaubten Zielgruppen teilen.';
$string['decision:overlappingAnnotation'] = 'Hier liegen mehrere Anmerkungen übereinander. Wähle die gewünschte aus.';
$string['deletingAnnotation_student'] = 'Die Anmerkung und alle zugehörigen Kommentare werden gelöscht. Eigene Anmerkungen kannst du löschen, solange andere noch nicht darauf geantwortet haben.';
$string['private_comments_help'] = 'Nur für dich sichtbar.';
$string['protected_comments_help'] = 'Nur für dich und Trainer/innen sichtbar.';
$string['questionstabicon_help'] = 'Fragen in dieser Aktivität anzeigen. Du kannst nach offenen, geschlossenen oder allen Fragen filtern.';
$string['answerstabicon_help'] = 'Antworten in dieser Aktivität anzeigen, wahlweise alle oder nur Antworten auf Fragen, denen du folgst.';
$string['ownpoststabicon_help'] = 'Deine Beiträge in dieser Aktivität anzeigen.';
$string['min0Chars'] = 'Bitte gib einen Text ein.';
$string['clicktoopen2'] = 'Öffne die Datei über den Link {$a}.';
$string['deletingQuestion_manager'] = 'Der Kommentar wird gelöscht. Wenn du auch alle Antworten entfernen willst, löschst du die zugehörige Markierung im PDF.';
$string['likeForbidden'] = 'Du darfst diesen Kommentar nicht bewerten.';
$string['recipient_help'] = 'Für mehrere Personen hältst du Strg gedrückt.';
$string['recipientrequired'] = 'Wähle mindestens eine empfangende Person aus.';
$string['studentdrawingforbidden'] = 'Zeichnen ist in dieser PDF Workspace Aktivität für deine Rolle nicht erlaubt.';
$string['studenttextboxforbidden'] = 'Textfelder sind in dieser PDF Workspace Aktivität für deine Rolle nicht erlaubt.';
$string['subtitleforreportcommentform'] = 'Deine Nachricht an die Kursleitung';
$string['voteshelpicontwo_help'] = 'So oft wurden deine Beiträge als hilfreich bewertet.';
$string['error:subscribe'] = 'Du konntest dieser Frage nicht folgen.';
$string['error:unsupportedextension'] = 'Dieser Dateityp wird nicht unterstützt. Wähle einen anderen.';
$string['successfullySubscribed'] = 'Du folgst dieser Frage jetzt.';
$string['setting_alternative_name_help'] = 'Wähle den Namen, der im Kurs und im Browsertitel erscheinen soll.';
$string['setting_useprint_help'] = 'Lege fest, ob Teilnehmer/innen Dokumente und Kommentare herunterladen dürfen.';
$string['messageforwardform'] = 'Deine Nachricht an die Empfänger/innen';
$string['messageprovider:forwardedquestion'] = 'Eine Frage wurde an dich weitergeleitet';
$string['forwardedquestionhtml'] = '{$a->sender} hat dir folgende Frage weitergeleitet:<br><br>„{$a->questioncontent}“<br><br>Nachricht:<br><br>„{$a->message}“<br><br><a href="{$a->urltoquestion}">Frage öffnen</a>.';
$string['forwardedquestiontext'] = '{$a->sender} hat dir folgende Frage weitergeleitet: „{$a->questioncontent}“ Nachricht: „{$a->message}“ Frage: {$a->urltoquestion}';
$string['unsubscribe_notification'] = 'Wenn du keine Benachrichtigungen mehr erhalten möchtest, <a href="{$a}">beendest du das Abo hier</a>.';
$string['adminhelp_overview_article'] = '<p>Hier verwaltest du die websiteweiten Einstellungen für PDF Workspace. Dokumente, Sichtbarkeit und erlaubte Downloadvarianten werden zusätzlich in der jeweiligen Aktivität festgelegt.</p><p>Prüfe Änderungen an einer passenden Testaktivität. Vorhandene Kommentare und ihre Zielgruppen werden durch Anzeigevorgaben nicht nachträglich umgeschrieben.</p>';
$string['adminhelp_exports_article'] = '<p>Für zusammengeführte PDF-Exporte wird eine passende Python-Umgebung auf dem Server benötigt. Trage den Pfad zum eingerichteten Interpreter ein und beachte die Installationshinweise des Plugins.</p><p>Die Freigabe einer Downloadvariante bleibt von den Einstellungen und Berechtigungen der Aktivität abhängig. Zum Testen eignen sich eigene Beispieldokumente.</p>';
$string['adminhelp_overview_help'] = 'Diese Einstellungen legen die Vorgaben der Website fest. Zielgruppe und erlaubte Werkzeuge prüfst du zusätzlich in jeder Aktivität.';
$string['adminhelp_courseoverview_help'] = 'Öffne eine PDF-Aktivität aus dieser Liste. Sichtbarkeit und Bearbeitungsaktionen richten sich nach deinen Kursrechten.';
$string['adminhelp_courseoverview_article'] = '<p>Diese Übersicht zeigt die für dich verfügbaren PDF-Workspace-Aktivitäten des Kurses, ihre Beschreibungen und letzte Änderungen. Wähle den Namen einer Aktivität, um ihre Dokumente und Diskussionen zu öffnen.</p><p>Bearbeitung und Sichtbarkeit lassen sich nur mit den entsprechenden Rechten ändern. Downloads und Annotationswerkzeuge werden für jede Aktivität gesondert eingestellt.</p>';
$string['adminhelp_viewer_help'] = 'Navigiere mit dem Cursor oder wähle Pin, Rahmen, Text, Stift oder Marker, soweit Rolle und Aktivität das erlauben.';
$string['adminhelp_viewer_article'] = '<p>Wähle einen Dokumentreiter und nutze Zoom und Seitensteuerung zum Lesen. Vollbild vergrößert den Arbeitsbereich; Escape führt zur Seite und zum vorherigen Fokus zurück.</p><p>Zu Pins und Rahmen kannst du eine Diskussion führen. Text, Stift und Marker hängen von den Aktivitätseinstellungen und deinen Rechten ab. Mit Shift zeichnest du eine gerade Stift- oder Markerlinie. Rückgängig und Wiederholen umfassen bis zu 20 eigene neue Zeichnungen und Textfelder in diesem Tab.</p>';
$string['adminhelp_discussion_help'] = 'Prüfe vor dem Senden die Zielgruppe. Antworten behalten die Zielgruppe ihrer Markierung; persönliche Notizen siehst nur du.';
$string['adminhelp_discussion_article'] = '<p>Klicke auf eine Markierung, um ihre Diskussion zu lesen oder zu beantworten. Prüfe vor dem Senden die gewählte Zielgruppe. Die verfügbaren Zielgruppen richten sich nach Aktivität und Rolle; Antworten behalten die Zielgruppe der Markierung.</p><p>Mit Fragenfiltern und Suche findest du Diskussionen. Persönliche Notizen sind nur für die verfassende Person sichtbar. Die Zielgruppe für Trainer/innen umfasst die verfassende Person und berechtigte Trainer/innen. Die Zielgruppe für ausgewählte Teilnehmer/innen ist nur für die verfassende und die empfangenden Personen sichtbar. Für ausgeblendete Kommentare gelten zusätzliche Moderationsrechte.</p>';
$string['adminhelp_downloads_help'] = 'Das Downloadmenü zeigt die für deine Rolle und diese Aktivität verfügbaren Varianten. Arbeitsbereichsdownloads verwenden die gewählten Dokumente und gespeicherte Exportreihenfolge.';
$string['adminhelp_downloads_article'] = '<p>Das Downloadmenü bietet Originaldokument, Kommentare und kombinierte Varianten an, soweit Einstellungen und Rechte das erlauben. Exporte enthalten nur Markierungen und Kommentare, die du sehen darfst. Bereits in den Original-PDFs vorhandene Inhalte bleiben erhalten.</p><p>Arbeitsbereichsdownloads nutzen die in der Aktivität gewählten Dokumente und die Exportreihenfolge, unabhängig von den Dokumentreitern. Kombinierte Exporte brauchen die eingerichteten lokalen Python-Werkzeuge; Formeln verwenden den lokalen Moodle-TeX-Renderer.</p>';
$string['adminhelp_moderation_help'] = 'Nutze Meldungsübersicht und Moderationsaktionen entsprechend deiner Rolle. Ausblenden und Löschen haben unterschiedliche Folgen.';
$string['adminhelp_moderation_article'] = '<p>Gemeldete Kommentare erscheinen in der Meldungsübersicht, soweit deine Rolle das erlaubt. Prüfe die Diskussion vor einer Aktion. Ausblenden beschränkt die Anzeige nach den Moderationsregeln; Löschen entfernt den Kommentar über den vorgesehenen Ablauf.</p><p>Berechtigte Trainer/innen können weitere eingeschränkte Diskussionen sehen. Persönliche Notizen bleiben persönlich. Moderationsaktionen ändern die Zielgruppe bestehender Threads nicht.</p>';
$string['adminhelp_activityoverview_help'] = 'Wähle Übersichtsreiter, Status und Dokumentfilter und klickst auf Anzeigen. Ein Ergebnis öffnet seine Markierung im PDF.';
$string['adminhelp_activityoverview_article'] = '<p>Fragen, eigene Beiträge und weitere für deine Rolle verfügbare Reiter zeigen die Diskussionen, die du sehen darfst. Die Statusauswahl hängt vom Reiter ab. Bei mehreren PDFs erscheint ein Dokumentfilter. Die Einträge pro Seite steuern die Seiteneinteilung.</p><p>Klicke auf Anzeigen, um die Filter anzuwenden, oder auf Zurücksetzen für die Ausgangsansicht. Sortierbare Spaltenüberschriften ändern die Reihenfolge. Dokument oder Kommentarvorschau öffnen die zugehörige PDF-Seite und Diskussion.</p><p>Mit der Glocke in der Antwortenliste folgst du einer Diskussion oder beendest das Abonnement. Bei Fragen kannst du die Weiterleitung nutzen, wenn deine Rolle und die Sichtbarkeit der Diskussion es erlauben.</p>';
$string['norecentownanswers'] = 'In dieser Auswahl gibt es keine neuen Antworten auf deine Fragen.';
$string['adminhelp_questiondashboard_article'] = '<p>Offene Fragen ohne Antwort erscheinen in der Liste mit der ältesten Frage zuerst. Eine bereits beantwortete Frage kann weiterhin offen sein, bis sie geschlossen wird.</p><p>Die Balken zeigen bis zu fünf Seiten, auf denen mindestens zwei Personen Fragen gestellt haben. Sie berücksichtigen offene und geschlossene Fragen aus dem gesamten Workspace. Private Notizen, ausgeblendete und gelöschte Beiträge zählen nicht mit. Mit Zurücksetzen entfernst du den Seitenfilter.</p>';
$string['adminhelp_recentanswers_help'] = 'Hier findest du Antworten anderer Personen aus den letzten sieben Tagen, auch wenn du die Frage nicht abonniert hast.';
$string['adminhelp_recentanswers_article'] = '<p>Die Ansicht sortiert nach dem Zeitpunkt der Antwort. Öffne den Text einer Antwort, um direkt zur Diskussion im PDF zu springen. Die Glocke steuert das Abonnement der Frage.</p><p>Wenn du ältere Antworten suchst, wählst du im Statusfilter „alle“. Antworten zu anderen Fragen, denen du folgst, finde unter „Antworten auf abonnierte Fragen“.</p>';
$string['adminhelp_activitysettings_help'] = 'Wähle Dokumente, Zielgruppen, Werkzeuge und Downloads. Ein PDF mit bestehenden Markierungen kann nicht ersetzt oder entfernt werden.';
$string['adminhelp_activitysettings_article'] = '<p>Füge PDFs hinzu und speichere die Aktivität, bevor du Auswahl und Reihenfolge für Arbeitsbereichsexporte änderst. Die Zeilensteuerung legt die Exportreihenfolge fest; Dokumentreiter behalten ihre eigene Reihenfolge.</p><p>Über die Checkbox links am Dokument markierst du PDFs zum Entfernen. Bei PDFs mit Markierungen können Website-Administrator/innen und Rollen mit dem Recht „PDFs mit Markierungen und Diskussionen entfernen“ die Warnung bestätigen. Beim Speichern werden diese PDFs samt Markierungen und Diskussionen gelöscht. Mindestens eine PDF muss im Workspace bleiben.</p><p>Erlaubte Zielgruppen und die Standardzielgruppe gelten für neue Markierungen. Sie ändern bestehende Diskussionen nicht. Aktiviere die benötigten Werkzeuge und Downloadvarianten für Teilnehmer/innen; zusätzlich gelten die jeweiligen Rechte.</p>';
$string['overviewfilterstatus_help'] = 'Wähle den Status oder die Abonnementansicht für diesen Reiter und klicke auf Anzeigen.';
$string['overviewfilterdocument_help'] = 'Beschränke die Ergebnisse auf ein PDF und klicke auf Anzeigen.';
$string['overviewfiltersize_help'] = 'Wähle die Anzahl der Ergebnisse pro Seite und klicke auf Anzeigen.';
$string['audiencedefault_student_help'] = 'Wähle eine erlaubte Standardzielgruppe für neue Markierungen von Teilnehmer/innen. Bestehende Markierungen und Antworten behalten ihre Zielgruppe.';
$string['audiencedefault_staff_help'] = 'Wähle eine erlaubte Standardzielgruppe für neue Markierungen von Trainer/innen. Bestehende Markierungen und Antworten behalten ihre Zielgruppe.';
$string['restrictedkeeplastready'] = 'Lass ein verfügbares Dokument bestehen, bis die neue geschützte PDF bereitsteht.';
$string['restrictedoutputlimit'] = 'Die Seitenbilder sind zusammen größer als 256 MiB. Verwende eine kürzere PDF oder verkleinere die eingebetteten Bilder.';
$string['restrictedprocessing'] = 'Moodle bereitet die Seiten einer geschützten PDF auf. Lade diese Seite neu, sobald das Dokument bereitsteht.';
$string['restrictedqueuechanged'] = 'Der Uploadstatus hat sich geändert. Öffne die Aktivitätseinstellungen erneut und prüfe die Dokumentliste.';
$string['restrictedrendertimeout'] = 'Die Umwandlung hat zu lange gedauert. Teile das Dokument in kleinere PDFs auf.';
$string['restrictedrenderunavailable'] = 'Moodle konnte diesen Upload nicht fertig verarbeiten. Bitte die Website-Administration, das Aufgabenprotokoll zu prüfen.';
