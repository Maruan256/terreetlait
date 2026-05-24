# 4.0.8
- Behoben: übermäßige Log-Einträge in dem scheduled task zur Autorisierung von Bestellungen.

# 4.0.7
- Der Autorisierungs-Callback wurde verbessert und korrigiert.
- Logs wurden hinzugefügt.
- Für das Zenit-Theme wurde die Kompatibilität für die Anzeige der Logos im checkout hinzugefügt.

# 4.0.6
- Kompatibilität für die Anzeige von Zahlungsnamen wurde für das Zenit-Theme hinzugefügt.
- Verbesserte Ausnahmebehandlung für den Bestellvalidator wurde hinzugefügt.

# 4.0.5
- SIWK fix: Ein Fehler tritt auf, wenn ein Kunde dasselbe Konto auf mehreren Verkaufskanälen verwendet.

# 4.0.4
- Behoben: Der Express-Knopf wurde auch bei nicht verfügbaren Produkten angezeigt.
- Erstellt: Eine automatische Prüfung, die Bestellungen mit falschem Zahlungsstatus korrigiert.

# 4.0.3
- Behoben: Aktualisierung von SW6 auf SW7 nicht möglich

# 4.0.2
- Fehlerbehebung für das Composable Frontend: Bei Bestellungen mit mehreren Zahlungsversuchen wurden Änderungen in die falsche Transaktion geschrieben.

# 4.0.1
- Behoben: Anzeige des Badges im Footer

# 4.0.0
- Alle Zahlungsmethoden wurden zu einer einzigen zusammengefasst: „Pay with Klarna“.
- Der Installationswizard wurde entfernt.

# 3.4.5
- Fehlende Labels in den Einstellungen wurden behoben.

# 3.4.4
- Fix Express-Checkout: „acceptedDataProtection“ hinzufügen
- Fix SIWK: „acceptedDataProtection“ hinzufügen

# 3.4.3
- Neue Einstellung: Das Dropdown-Feld „Rechtsform“ ausblenden
- Neue Einstellung: Handelsregisternummer-Feld ausblenden

# 3.3.3
- Fix: Initialisiere die klarna-session nicht, wenn der Warenkorb in Composable Frontends leer ist.
- Fix OrderValidator: Leere Lieferadressen nicht validieren.

# 3.3.2
- Behoben: Fehlende variabel erlaubt keine Bestellung im Composable Frontend

# 3.3.1
- Logout nach Zahlung im Composable Frontend behoben
- Klarna Hosted Payment Page bei Auswahl anderer Zahlungsmethode im Composable Frontend behoben

# 3.3.0
- Neue Plugin-Konfiguration: Positionsprüfung deaktivieren

# 3.2.1
- Verbessertes Logging

# 3.2.0
- Sign in with Klarna für 6.7 fertiggestellt
- Standardwerte in einigen API-Anfragen hinzugefügt

# 3.0.2
- Unerwünschte Composable Frontends logs wurden entfernt
- Behoben: Knopf wird nicht deaktiviert, wenn der aktuelle Warenkorb Fehler enthält

# 3.0.1
- Composable Frontends (SW 6.7): Verwendung von store-api routes statt Controller nach Hosted Payment Page redirect

# 3.0.0
- Kompatibilität mit Shopware Version 6.7 hergestellt

# 2.3.2
- Composable Frontends: Verwendung von store-api routes statt Controller nach Hosted Payment Page redirect

# 2.3.1
- Unerwünschte Composable Frontends logs wurden entfernt

# 2.3.0
- Kompatibilität mit Composable Frontend (Headless Shopware) hinzugefügt.
- Problem behoben: der Express Checkout Button bleibt deaktiviert solange bis ein Produktvariante ausgewählt wurde.
- Problem behoben: es konnte vorkommen, dass im Checkout bei den Klarna Zahlungsarten der Name nicht angezeigt wurde.

# 2.2.8
- Problem behoben: veralteten Code entfernt

# 2.2.7
- Die Möglichkeit hinzugefügt, die Adressvalidierung zwischen Shopware und Klarna zudeaktivieren. 

# 2.2.6
- Bei manchen Bestellungen wurde der authorisation Callback mehrmals getriggert.

# 2.2.5
- Wenn bei der Registrierung die Telefonnummer erforderlich ist, konnte man keine Bestellung über den Express Checkout durchführen.

# 2.2.4
- Bei Bestellungen mit Klarna konnte es vorkommen, dass die Versandkosten mehrmals angezeigt werden.
- Wenn man Bestellungen im Backend nach Lieferstatus "offen" filtert, werden fälschlicher Weise auch Bestellungen im Status "versandt" angezeigt. 

# 2.2.3
- Problembehebung Order-Update API

# 2.2.2
- Problembehebung Hash validierung

# 2.2.1
- Klarna Log-Einträge erweitert

# 2.2.0
- Salutations bei den Addressen entfernt (löst das Problem beim Laden der Klarna-Details)
- Problembehebung: Sobald die Rechnungsadresse keine europäische Adresse ist, wurde der Kaufbutton gesperrt.
- Logs erweitert

# 2.1.2
- Problembehebung Carthash validierung

# 2.1.1
- Sobald die Postleitzahl fehlt, kommt es zu einen Fehler.
- Sobald die Bestellung teilweise gecaptured ist und dann voll gecaptured wird, wird der Status nicht von teilweise bezahlt auf bezahlt geändert.
- Bei der Validierung der Adresse kam es zu Problemen mit der Session.
- Bestellungen mit digitalen Produkten konnten nicht ordnungsgemäß durchgeführt werden.

# 2.1.0
- Behebung des Validierungsproblems beim updaten/ändern der Lieferadresse

# 2.0.1
- Fehler behoben: Artikel konnten nicht in der Administration storniert werden.

# 2.0.0
- Kompatibilit�t mit Shopware Version 6.6 hergestellt

# 1.14.0
- Korrektur eines Fehlers im Checkout bei fehlenden API-Daten
- Optimierung der Ermittlung des korrekten Endpunkts für Anfragen an die Klarna-API
- Neue Zahlungsart hinzugefügt welche automatisch aktiv wird, sobald das Händlerkonto von Klarna migriert wurde

# 1.13.0
- Entfernung des Klarna Express Button
- Klarna Express Checkout hinzugefügt
- Korrektur der Darstellung des Klarna Logo bei den Zahlungsarten im Accountbereich
- Korrektur der Anzeige von Zusatzfeldern in der Administration
- Ergänzung des Retourenstatus von SwagCommercial im Modal für die Rückerstattung
- Korrektur der Übersetzung des Versandmethodennamens im Bestellprozess
- Dekoration des CheckoutController für die Kompatibilität zu anderen Plugins angepasst

# 1.12.1
- Korrektur der Debug-Log mit Shopware 6.5.4+
- Korrektur der Kompatibilität zu anderen Plugins, die den CheckoutController dekorieren
- Korrektur der Kompatibilität beim erneuten Installieren des Plugins
- Korrektur des Zahlungsartwechsel zu Klarna bei einer bestehenden Bestellung

# 1.12.0
- Speicherung von Custom Fields an Bestelladressen für Shops unter 6.4.14.0 hinzugefügt
- Ergänzung einer Integration mit Billie zur Handhabung von B2B Bestellungen
- Die Klarna-Rechnungsadresse wird nun beim Bestellabschluss zu Shopware synchronisiert. Änderungen an der Rechnungsadresse in Shopware werden nun nicht mehr mit Klarna synchronisiert.

# 1.11.0
- Ergänzung des Autorisierungs-Callback
- Korrektur der Einschränkung auf eine Zahlungsart bei Verwendung von Klarna Express
- Änderung an dem Veröffentlichungsprozess zur Erstellung einer Plugin-Version, die mit Shopware 6.4 und Shopware 6.5 kompatibel ist

# 1.10.0
- Überprüfung auf ausgewähltes Klarna Express eingebaut, bevor automatisch eine Klarna-Zahlungsmethode ausgewählt wird
- Korrektur der Rundung von Beträgen
- Korrektur der Überschreibung der Beschreibung von Zahlungsarten
- Korrektur der Darstellung von individuellen Bildern für Zahlungsarten
- Korrektur der Hinzufügung der Cookies in der erforderlichen Gruppe
- Korrektur der Handhabung, wenn die Zahlungsart einer Bestellung geändert wird
- Nutzung von Custom Fields bei Bestellungen auf dedizierte EntityExtension umgestellt, um Probleme mit anderen Plugins zu vermeiden
- Anpassung der Synchronisierung mit Klarna, damit Bestellpositionen nicht mehr synchronisiert werden, sobald die Bestellung erfasst wurde

# 1.9.0
- Kompatibilität zu Shopware 6.5.0.0 hergestellt
- Anpassung der angezeigten Zahlungsartnamen im Checkout

# 1.8.0
- Übersetzung der Fehlermeldung bei Bestellanpassung in der Administration behoben
- Produktvarianteninformation im Modal für Erfassung und Rückerstattung hinzugefügt
- Fehler beim Schließen vom Detailmodal der Bestellhistorie korrigiert
- Codeoptimierungen um mögliche Fehler zu reduzieren
- Umgebungsvariable `APP_SECRET` via Symfony Service Definition in jeweiligen Services injected, um die LogicException im Checkout zu beheben.
- Kompatibilität für USA hinzugefügt
- Überschreibung der Administrations-Komponente `sw-order-detail-base` für weitere Plugins korrigiert

# 1.7.0
- Login via Klarna Express im Warenkorb ermöglicht
- Ergänzung der Session-Aktualisierung um die Session-Erstellung mit Klarna zu reduzieren
- Korrektur der Validierung der AGB-Checkbox im Checkout

# 1.6.3
- Hinzufügen einer Prüfung, ob die AGB im Checkout akzeptiert wurden

# 1.6.2
- Kompatibilität zum CSRF-Modus "ajax" hergestellt

# 1.6.1
- Kompatibilität zu Shopware 6.4.10.0 hergestellt

# 1.6.0
- Kompatibilität zu NetiNextEasyCoupon hinzugefügt. Gutscheine werden jetzt als Geschenkgutscheine an Klarna übertragen.
- Anpassung der Minimalversion zu Shopware 6.3.0.0
- Hinweis zur Vererbung der Plugin-Konfiguration für Shopware-Versionen unter 6.3.4.0 hinzugefügt
- Korrektur der De- und Aktivierung des Buttons zum Bestellabschluss mit Klarna Zahlungsarten
- Korrektur des Statuswechsels von `Autorisiert` zu `Bezahlt` bei manueller Erfassung
- Entfernen des Regel-Installers, da Kundenspezifische Regeln überschrieben wurden
- Entfernen der expliziten Einschränkungen der Zahlungsmethoden
- Korrektur der Cover-URL für Bestell-Anfragen zur Klarna API
- Hinzufügen der Produkt-URL für Bestell-Anfragen zur Klarna API 
- Korrektur der angezeigten Zahlungsarten basierend auf der Antwort der Klarna API

# 1.5.0
- Sprachdateien an neue Position verschoben
- Irland als verfügbares Land hinzugefügt
- Darstellung der Vererbung der Plugin-Konfiguration optimiert
- Wechsel des Zahlungsstatus auf Authorisiert, falls die Zahlung von Klarna authorisiert wurde
- Verwenden des ausgewählten Versandartnamens für die Bestellposition der Klarna Bestellung
- Unterstützung von Shopware Custom Products hinzugefügt

# 1.4.4
- Behebung eines Fehlers mit Multi-Steuer Gutscheinen
- Behebung von Problemen, wenn das Zahlungsabschluss Modal geschlossen wird.
- Das Klarna Widget wird im Checkout nun über der Positionstabelle dargestellt, wenn mindestens Shopware 6.4 verwendet wird
- Korrektur des Buttons zum Bestellabschluss wenn das Klarna Modal geschlossen wird
- Behebung von ESLint Fehlermeldung mit Shopware 6.4.5.x

# 1.4.3
- Behebung der Deinstallation des Plugins, wenn die gespeicherten Daten gelöscht werden sollen

# 1.4.2
- Unterstützung von PHP 8 hinzugefügt
- Anpassung der Warenkorb-Hash-Logik zur Verifikation von nachträglichen Bestellanpassungen

# 1.4.1
- Kompatibilität zu Shopware 6.4 hergestellt

# 1.4.0
- Instant Shopping entfernt.
- Übersetzungen der Zahlungsarten hinzugefügt.
- Behebung eines Fehlers im Internet Explorer der einen Bestellabschluss verhinderte

# 1.3.8
- Die Regel für Klarna Zahlungsarten im Rule-Builder wurde entfernt.
- Italien als unterstütztes Land ergänzt. Hinweis: Instant Shopping ist nicht für Italien verfügbar.
- Behebung eines seltenen Fehlers durch ungültige Werte bei der Speicherung der Pluginkonfiguration.
- Eine Bestellung wird nur noch automatisch eingezogen, wenn die Betrugsprüfung erfolgreich war.
- Der automatische Einzug ist nun auch mit dem Bestell- und Lieferstatus offen möglich.

# 1.3.7
- Behebung von Fehlern in der Pluginkonfiguration
- Bitte beachten Sie: Ein Fehlverhalten bei vererbten Konfigurationswerten ist bekannt und in der Dokumentation näher beschrieben.

# 1.3.6
- Optimierung der Fehlerbehandlung bei der Verwendung von Instant Shopping
- Korrektur der fehlenden Bezeichnungen in der Plugin-Konfiguration
- Hinweis in der Dokumentation (https://klarna.kellerkinder.de/de/1-3/index.html#h-uebersetzung-der-zahlarten) zur Übersetzung von Zahlungsarten ergänzt.

# 1.3.5
- Ergänzung eines Standardnamens für die Zahlungsarten für die Installation unter einer anderen Systemsprache

# 1.3.4
- Korrektur der Handhabung von Gutscheinen im Warenkorb

# 1.3.3
- Korrektur des automatischen Packens des ZIP-Archiv um kompilierte Dateien wieder zu ergänzen
- Korrektur der Verwendung einer falschen Exception-Klasse

# 1.3.2
- Korrektur der Darstellung vom Instant Shopping Button
- Korrektur eines Fehlers bei der Aktualisierung der Bestellung wenn die Bestellung bereits eingezogen wurde
- Optimierung der Performance im Checkout und beim Aktualisieren der Bestellung

# 1.3.1
- Korrektur der Selektierung einer Bestellung aus den Suchergebnissen

# 1.3.0
- Sprache und Währung zur Verfügbarkeitsregel von Klarna Zahlarten hinzugefügt
- Übermittlung von Informationen zur Versandverfolgung an das Klarna Händlerportal  
- Korrektur der Installation, wenn eine Sprache nicht gefunden werden konnte
- Korrektur von Instant Shopping für Gäste bei konfigurierten erforderlichen Einstellungen für die Registrierung
- Optimierung der Validierung von API Zugangsdaten

# 1.2.1
- Korrektur der Anzeige von Lieferinformationen auf Produktdetailseiten
- Korrektur von Bestellaktualisierungen in der Administration mit anderen Zahlungsmethoden

# 1.2.0
- Kompatibilität zu Shopware 6.2 hergestellt

# 1.1.0
- Implementierung von Klarna Instant Shopping
- Unterstützung von reinen Nettopreisen hinzugefügt (ab Shopware 6.2.0)

# 1.0.4
- Korrektur des Bestellabschluss-Buttons für andere Zahlungsmethoden

# 1.0.3
- Korrektur des Namens der Rechnungsadresse in der Klarna Payment Session

# 1.0.2
- Kombinierte Pay Now-Zahlungsart und Kreditkarte ergänzt
- Bestellanpassungen in der Administration werden durch den Versions-Manager nun vor dem Speichern mit Klarna abgeglichen
- Anpassung an den Adressübergaben
- Zahlungskategorien können nun in der Pluginkonfiguration deaktiviert werden
- Abgleich von Bestellungsanpassungen während dem Bestellprozess werden nun verhindert, solange die Bestellung noch nicht bei Klarna angelegt wurde

# 1.0.1
- Rundungsfehler bei Klarna-Aufrufen behoben

# 1.0.0
- Erste Version der Klarna Payment Integration für Shopware 6.1
