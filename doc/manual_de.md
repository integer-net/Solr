IntegerNet_Solr
===============
Benutzer-/Entwickler-Handbuch

Allgemein
-----
IntegerNet_Solr ist ein Magento 1.x-Modul, das mit Hilfe von Apache Solr als Engine eine deutlich verbesserte Suche
in Magento-Shops bietet. Die Kernfunktionen sind ein Suchvorschaufenster mit Produkt- und Suchwortvorschlägen sowie
bessere Suchergebnisse in punkto Qualität und Geschwindigkeit.

Features
--------
#### Allgemein
- Rechtschreibkorrektur, unscharfe Suche
- Zeigt zuerst exakte Suchergebnisse an und anschließend Suchergebnisse zu ähnlichen Suchbegriffen
- Vollständige Unterstützung der Multistore-Funktionalität von Magento
- Kompatibel zu den Magento-Standard-Themes default, modern und rwd
- Nutzung eines einzigen Solr-Kerns für mehrere Magento-Storeviews oder mehrerer Kerne
- Nutzung eines separaten Solr-Kerns nur für die Indizierung mit anschließendem Tausch der Kerne "on the fly" ("Swap")
- Erlaubt Logging aller Solr-Requests
- Prüft Erreichbarkeit und Konfiguration des Solr-Servers

#### Suchvorschlagsfenster
- Erscheint nach dem Eintippen der ersten Buchstaben in das Suchfeld
- Zeigt Produktvorschläge, Kategorievorschläge, Attributvorschläge und Suchwortvorschläge
- Anzahl der Suchvorschläge jeden Typs ist in der Magento-Konfiguration einstellbar
- Anzuzeigende Attribute können in der Konfiguration definiert werden
- Für eine schnellere Suchvorschau werden die Requests an Magento vorbeigeleitet

#### Suchergebnisse
- Unterstützt alle Magento-Standardfunktionen wie Sortierung, Paginierung und Filter
- Verwendet Suchergebnisse aus Solr für bessere Performance und Qualität
- Rendert Produkt-HTML-Blöcke bereits bei der Indizierung für schnellere Darstellung
- Ermöglicht die Konfiguration der Preisfilter-Schritte
- Der Solr-Index wird beim Erstellen/Bearbeiten/Löschen von Produkten automatisch aktualisiert

#### Modifizierung der Suchergebnisse
- Anpassung der Unschärfe ("Fuzzyness")
- Erlaubt das Boosten bestimmter Produkte und Attribute
- Stellt Events zur Modifizierung des Indizierungsprozesses und der Suchergebnisse bereit

Systemvoraussetzungen
------------
- **Magento Community Edition** 1.6 bis 1.9 oder **Magento Enterprise Edition** 1.11 bis 1.14
- **Solr** 4.x oder 5.x
- **PHP** 5.3 bis 5.5 (5.5 empfohlen)

Installation
------------
1. Installieren Sie **Solr** und mindestens einen funktionsfähigen **Solr-Kern** ("Core")
2. Kopieren Sie die Dateien vom Verzeichnis `solr_conf` des Modul-Pakets in das Verzeichnis `conf` des Solr-Kerns / der 
Solr-Kerne.
3. Laden Sie den Solr-Kern neu (oder Solr komplett)
4. (Falls aktiviert: Deaktivieren Sie die Magento-Komilierung (Compiler).)
5. Kopieren Sie die Dateien und Verzeichnisse des `src`-Verzeichnisses des Modul-Pakets in Ihre Magento-Installation. 
Falls Sie **modman** und/oder **composer** nutzen, können Sie die Dateien `modman` bzw. `composer.json` im Hauptverzeichnis 
nutzen.
6. Leeren Sie den Magento-Cache.
7. (Starten Sie den Komplilierungsprozess und reaktivieren Sie die Magento-Kompilierung - wir empfehlen, die 
Kompilierungsfunktion von Magento nicht zu nutzen, unabhängig von diesem Modul.)
8. Gehen Sie in den Administrationsbereich von Magento zu `System -> Konfiguration -> Solr` (weit unten).
9. Geben Sie die Solr-Zugangsdaten ein und konfigurieren Sie das Modul (siehe unten).
10. Klicken Sie auf "Konfiguration speichern". Die Verbindung zum Solr-Server wird automatisch getestet. Sie erhalten 
entsprechende Erfolgs- und/oder Fehlermeldungen.
11. Wenn Sie die Magento Enterprise Edition nutzen, müssen Sie die integrierte Solr-Suche ausschalten.
Setzen Sie `System -> Konfiguration -> Katalog -> Katalogsuche -> Search Engine` auf `MySql Fulltext`.
12. Reindizieren Sie den Index von IntegerNet_Solr. Wir empfehlen, dies über die Kommandozeile zu machen. Gehen Sie
in das Verzeichnis `shell` und rufen Sie den Befehl `php -f indexer.php -- --reindex integernet_solr` auf.
13. Versuchen Sie, ein paar Buchstaben in das Suchfeld im Frontend einzutippen. Ein Fenster mit Produkt- und Suchwort-
vorschlägen sollte erscheinen.
 
Technischer Ablauf
------------------

### Indizierung
Für jede Kombination aus Produkt und StoreView wird ein Solr-Dokument auf dem Solr-Server erzeugt. Dies erfolgt durch
den Indizierungs-Mechanismus von Magento, der es erlaubt, auf jede Änderung am Produkt zu reagieren. Entweder können
Sie eine komplette Neuindizierung vornehmen, die alle Produkte effizient (in Blöcken von je 1.000 Produkten, 
konfigurierbar) bearbeitet, oder eine laufende partielle Neuindizierung. Die partielle Neuindizierung wird ausgeführt, wenn 
ein beliebiges Produkt erstellt, geändert oder gelöscht wird und erneuert das entsprechende Dokument im Solr-Server.
Dies passiert nur für die betroffenen Produkte, so dass der Solr-Index immer aktuell ist.

Die in Solr gespeicherten Daten beinhalten die folgenden Informationen:

- Produkt-ID
- Store-ID
- Kategorie-IDs
- Inhalt aller Produktattribute, die in Magento als "durchsuchbar" markiert sind
- Generiertes HTML für das Suchvorschau-Fenster, das die anzuzeigenden Daten und das Layout beinhaltet (z.B. Name, 
Preis, Produktbild, ...)
- Falls konfiguriert: Generiertes HTML für die Suchergebnisseite, einmal für den Gitter-Modus (Grid) und einmal
für den Listen-Modus (List)
- IDs aller Optionen der filterbaren Attribute für die Filternavigation

Wenn Sie regelmäßig eine komplette Neuindizierung vornehmen, empfehlen wir Ihnen Die **Swap**-Funktionalität.
Sie können das Modul so komfigurieren, dass es einen unterschiedlichen Solr-Kern zur Indizierung nutzt und dass
anschließend die Kerne getauscht werden (`System -> Konfiguration -> Solr -> 
Indizierung -> Cores tauschen nach vollständiger Neuindizierung`).  

### Suchvorschläge
Wenn Sie die Suchvorschlags-Funktionalität nutzen, gibt es jedes Mal, wenn ein Kunde die ersten Buchstaben ins Suchfeld
im Frontend eingetippt hat, einen AJAX-Aufruf. Die Antwort davon beinhaltet den HTML-Code des Suchvorschau-Fensters,
das Produktdaten, Suchwortvorschläge, passende Kategorien und/oder Attribute anzeigt. Die Ziel-URL des AJAX-Aufrufs 
ist unterschiedlich je nach der Konfigurationseinstellung unter `System -> Konfiguration -> Solr -> Suchvorschlags-Box 
-> Methode zum Ermitteln von Suchvorschlags-Informationen`:

#### Magento-Controller
Das ist die Basismethode, die ausschließlich Magento-Methoden einsetzt, so wie es auch die Standard-Suchfunktion
von Magento macht oder die Solr-Funktionalität der Magento Enterprise-Edition. Diese Methode ist die langsamste, aber
auch die flexibelste. Sie ist vorgesehen als Fallback, falls die anderen Methoden aus welchen Gründen auch immer
nicht eingesetzt werden können.

#### Magento mit separater PHP-Datei
Hierdurch wird per AJAX die separate PHP-Datei `autosuggest-mage.php` im Magento-Hauptverzeichnis direkt aufgerufen.
Dadurch wird der Routing-Prozess von Magento übergangen, die Inhalte werden schneller ausgeliefert. Dennoch sollten
alle Magento-Funktionen funktionieren. Außer der Geschwindigkeit (siehe unten) haben wir bisher keine Nachteile dieser
Methode gefunden, sie kann also bedenkenlos eingesetzt werden.

#### PHP ohne Magento-Instanziierung
Mit dieser Methode wird eine andere PHP-Datei `autosuggest.php` im Magento-Hauptverzeichnis per AJAX direkt aufgerufen.
Ein Großteil der Magento-Funktionalität wird dabei nicht verwendet, wodurch sie in den meisten Umgebunden deutlich
schneller ist. Da dabei keine Datenbank-Abfragen ausgeführt werden, müssen alle Daten, die für das Suchvorschaufenster
benötigt werden, entweder direkt vom Solr-Server oder aus einer Textdatei kommen. Das Modul generiert automatisch
Textdateien, die die Informationen enthalten, die von der Suchvorschaufunktion benötigt werden:

- Die Solr-Konfiguration (z.B. Zugangsdaten)
- Ein paar zusätzliche Konfigurationswerte
- Alle Kategoriedaten (Namen, IDs und URLs)
- Alle Attributdaten, die in der Konfiguration eingestellt sind (Optionsnamen, IDs und URLs)
- Einige Zusatzinformation wie die Base-URL oder der Dateiname der Templatedatei (s.u.)
- Eine Kopie der Datei `template/integernet/solr/result/autosuggest.phtml`, die in Ihrem Theme verwendet wird. Alle
Übersetzungstexte sind darin bereits in die korrekte Sprache übersetzt.

Die Informationen werden in der Datei `var/integernet_solr/store_x/config.txt` als serialisiertes Array gespeichert bzw. 
befinden sich in der Datei `var/integernet_solr/store_x/autosuggest.phtml`. Diese Dateien werden automatisch in einem
der folgenden Fälle neu erzeugt:

- AJAX-Aufruf im Frontend, während die Datei `var/integernet_solr/store_x/config.txt` nicht existiert.
- Die Konfiguration des Solr-Moduls wird gespeichert
- Der Cache wird geleert.

Wenn Sie also die gespeicherten Informationen erneuern lassen wollen, lösen Sie einen der drei obigen Fälle aus.

Beachten sie, dass Sie nicht alle Magento-Funktionen zur Verfügung haben werden, wenn Sie diese Methode verwenden. 
Versuchen Sie, sich an die Methoden zu halten, die in 
`app/design/frontend/base/default/template/integernet/solr/autosuggest.phtml` verwendet werden. Z.B. können Sie 
keine Statischen Blocks oder andere externen Informationen ohne zusätzliche Erweiterung verwenden.

Konfiguration
-------------

--- Folgt ---

Modifikation der Reihenfolge der Suchergebnisse
-------------

--- Folgt ---

Template-Anpassungen
--------------------

Wenn Sie ein Nicht-Standard-Template verwenden, müssen voraussichtlich ein paar Anpassungen gemacht werden. Das Template
des Suchvorschaufensters und der Suchergebnisseite ist in `app/design/frontend/base/default/template/integernet/solr/`
(PHTML-Dateien) definiert sowie in `skin/frontend/base/default/integernet/solr/` für die CSS-Datei, die auf jeder Seite
eingebunden wird.

### Suchergebnisseite
Sehr wahrscheinlich haben Sie bereits ein Template für die Suchergebnisseite. Üblicherweise ist es in 
`template/catalog/product/list.phtml` in Ihrem Theme-Verzeichnis zu finden. Um den passenden Inhalt für die PHTML-Dateien
des IntegerNet_Solr-Moduls zu erstellen, müssen Sie den Inhalt Ihrer Datei in drei Teile teilen:

- Die Teile innerhalb von  `<li class="item...">` werden in `template/integernet/solr/result/list/item.phtml` und   
`template/integernet/solr/result/grid/item.phtml` eingefügt, abhängig davon, um welchen Modus (Grid oder List) es sich
handelt.
- Der Rest wird in `template/integernet/solr/result.phtml` eingefügt. Die vorher ausgeschnittenen Teile müssen mit dem 
folgenden Code ersetzt werden:

    <?php echo $this
        ->getChild('item')
        ->setProduct($_product)
        ->setListType('list')
        ->toHtml() ?>

Ersetzen Sie entsprechend `list` durch `grid`, abhängig davon, welchen Teil Sie ersetzen.
Während Sie die Template-Dateien anpassen, sollten sie die Konfigurationsoption `Suchergebnisse -> HTML vom Solr-Index 
verwenden` ausschalten. Wenn Sie die Option aktiviert haben, benötigen Sie eine komplette Neuindizierung nach jeder
Änderung einer Template-Datei. Aktivieren Sie die Option nach Fertigstellung der Anpassungen wieder und führen
Sie eine Neuindizierung durch.

### Suchvorschaufenster
Sie können die Dateien `template/integernet/solr/autosuggest.phtml` und `template/integernet/solr/autosuggest/item.phtml`
bearbeiten, um das Erscheinungsbild des Suchvorschaufensters anzupassen. Achtung: Da der generierte HTML-Code für jedes
Produkt im Solr-Index gespeichert ist, müssen Sie nach Änderungen an der Datei 
`template/integernet/solr/autosuggest/item.phtml` eine Neuindizierung vornehmen.

Bitte beachten Sie: Wenn die Suchvorschaufunktion nicht von Magento, sondern von einer blanken PHP-Version ausgeliefert
wird (Standard, siehe oben), können Sie in Ihrer `template/integernet/solr/result/autosuggest.phtml` nicht alle Magento-
Funktionen verwenden. Versuchen Sie, sich an die in 
`app/design/frontend/base/default/template/integernet/solr/result/autosuggest.phtml`
genutzten Funktionen zu halten. Da der HTML-Code für die einzelnen Produkte von Magento generiert wird, können Sie dort
hingegen alle Magento-Funktionen verwenden.

Wenn Sie Produkt-, Kategorie-, Attribut- oder Suchwortvorschläge in der Suchvorschaufunktion nicht verwenden, schalten
Sie sie bitte auch in der Konfiguration aus, um die Performance zu verbessern.

Mögliche Probleme und Lösungsansätze
-------------------------------------
1. **Rewrite-Konflikte mit Modulen, die die Filternavigation beeinflussen**
    Sie können dies nicht vermeiden, wenn Sie ein entsprechendes Modul einsetzen. Sie können aber die Konflikte 
    auflösen. Bitte sehen Sie, wie wir einen Konflikt mit einem solchen Modul aufgelöst haben, in der Datei 
    `app/code/community/IntegerNet/Solr/Model/Resource/Catalog/Layer/Filter/Price.php`.
    
2. **Das Speichern von Produkten im Backend dauert lange**
    Das kann passieren, wenn Sie viele Store Views haben, da für die beim Speichern stattfindende Indizierung für jeden
    Store View eine eigene Anfrage an Solr gesendet werden muss. Wir empfehlen, in diesem Fall den Indizierungs-Modus
    des `integernet_solr`-Index auf "manuell" zu stellen und jede Nacht eine komplette Reindizierung per Cronjob
    vorzunehmen, wenn möglich.
    
3. **Die Produktdaten auf der Suchergebnis-Seite solltes für verschiedene Kundengruppen unterschiedlich aussehen, sehen
    aber überall gleich aus**
    Schalten Sie `Suchergebnisse -> HTML vom Solr-Index verwenden` in der Konfiguration aus. Dadurch wird der HTML-Code
    bei jedem Aufruf neu generiert. Beachten Sie bitte, dass das die Performance der Suchergebnisseite beeinflusst.

4. **Die Produktdaten im Suchvorschaufenster sollten für verschiedene Kundengruppen unterschiedlich aussehen, sehen
    aber überall gleich aus**
    Da der produktabhängige HTML-Code immer im Solr-Index gespeichert wird, ist das leider nicht möglich. Versuchen Sie,
    das HTML in `template/integernet/solr/autosuggest/item.phtml` so anzupassen, dass es keine kundenspezifischen 
    Informationen (z.B. Preise) mehr enthält.
