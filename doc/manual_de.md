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
- **PHP** 5.3 bis 5.5 (5.5 empfohlen), voraussichtlich kompatibel zu PHP 5.6 und 7.0 (noch nicht getestet)

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

Die Konfiguration befindet sich im Administrationsbereich von Magento unter *System -> Konfiguration -> Solr*:

![Konfigurations-Menü](http://www.integer-net.de/download/solr/integernet-solr-config-menu-de.png)

Im Folgenden werden die Konfigurationsoptionen aufgelistet und beschrieben

### Allgemein

![Allgemein](http://www.integer-net.de/download/solr/integernet-solr-config-general-de.png)

Im oberen Bereich werden Erfolgs-, Fehler-, Warn- und Informationsmeldungen ausgegeben. So wird automatisch geprüft, ob
das Modul aktiviert ist, Zugangsdaten zum Solr-Server eingetragen sind und ob diese auch korrekt funktionieren.

#### Ist aktiv

Wenn dieser Schalter auf "Nein" steht, wird das Suchmodul im Frontend nicht genutzt werden. Stattdessen greift dann
die Standardsuche von Magento. Sie haben die Möglichkeit, diesen Schalter für einzelne Websites oder StoreViews zu
setzen.

#### Lizenzschlüssel

Damit das Modul korrekt funktioniert, benötigen Sie einen funktionierenden Lizenzschlüssel. Sie erhalten diesen nach 
Kauf und Bezahlung des Moduls von uns. Kontaktieren Sie uns unter solr@integer-net.de, wenn Sie Probleme mit Ihrem
Lizenzschlüssel haben sollten.

Sie können das Modul zwei Wochen auch ohne Lizenzschlüssel testen. Erst anschließend ist der Lizenzschlüssel für
das Funktionieren des Moduls notwendig.

Ein Lizenzschlüssel gilt jeweils für eine Live-Instanz und beliebig viele zugehörige Entwicklungs-, Test- und Staging-
Systeme.

Achtung: Es wird keine Verbindung zu einem Lizenzserver o.ä. aufgebaut. Sobald der Lizenzschlüssel eingetragen ist,
funktioniert das Modul autark. 

#### Logging aktivieren

Wenn dieser Schalter aktiv ist, werden alle Anfragen zum Solr-Server gespeichert. Das betrifft sowohl die Suchvorschau
als auch die eigentliche Suchergebnisse. Sie finden die Logs anschließend im Verzeichnis `/var/log/` mit den Dateinamen 
`solr.log` bzw. `solr_suggestions.log`.

Die Logdateien werden ausschließlich zur Fehlersuche bzw. zur Optimierung der Suchergebnisse genutzt. Da die 
Datenmengen bei einer häufig genutzten Suchfunktion erheblich sein können, empfehlen wir, das Logging auf 
Produktivsystemen üblicherweise zu deaktivieren.

### Server

![Server](http://www.integer-net.de/download/solr/integernet-solr-config-server-de.png)

In diesem Bereich werden die Zugangsdaten zum Solr-Server eingetragen. Wenn die Daten korrekt sind, erscheinen im
oberen Bereich der Konfigurationsseite entsprechende Erfolgsmeldungen, andernfalls Fehlermeldungen.
Sollten Sie die Zugangsdaten nicht kennen, erhalten Sie diese von Ihrem Administrator bzw. Hoster, der den Solr-Server 
eingerichtet hat.

Wenn Sie Zugang zum Admin-Bereich des Solr-Servers haben, können Sie die Zugangsdaten wie folgt selbst herausfinden:

1. Wählen Sie links unten im Core-Selector den zu verwendenden Core aus:  
 ![Solr-Admin 1](http://www.integer-net.de/download/solr/solr-admin-1.png)  
2. Wählen Sie unterhalb des Core-Selectors "Query"  
 ![Solr-Admin 2](http://www.integer-net.de/download/solr/solr-admin-2.png)  
3. Klicken Sie "Execute Query"  
 ![Solr-Admin 3](http://www.integer-net.de/download/solr/solr-admin-3.png)  
4. Im oberen Bereich auf der rechten Seite sehen Sie jetzt die für Ihren Beispiel-Request verwendeten URL:  
 ![Solr-Admin 4](http://www.integer-net.de/download/solr/solr-admin-4.png)  

Die URL wird wie folgt in die einzelnen Teile aufgeteilt:

![Solr-Admin-URL](http://www.integer-net.de/download/solr/solr-config-server.png)

Die einzelnen Teile werden dann wie folgt in die Konfiguration eingetragen:

![Solr Server-Konfiguration](http://www.integer-net.de/download/solr/solr-server-config-de.png)

Achten Sie darauf, dass das Feld *Kern* keine Schrägstriche enthält, das Feld *Path* aber mindestens je einen 
Schrägstrich am Anfang und am Ende.

#### HTTP-Übertragungsmethode

Bleiben Sie hier bei der Standardmethode *cURL*, wenn Sie keine Fehlermeldung erhalten. Andernfalls können Sie auf
die Methode *file_get_contents* wechseln. Die Verfügbarkeit der Methoden hängt von den Server-Einstellungen des
Magento-Servers ab.

#### HTTP Basis-Authentifizierung

Tragen Sie hier Benutzername und Passwort ein, wenn diese für den Zugriff von Magento auf den Solr-Server notwendig
sein sollten.

### Indizierung 

![Indizierung](http://www.integer-net.de/download/solr/integernet-solr-config-indexing-de.png)

#### Anzahl Produkte pro Durchlauf

Die hier eingetragene Anzahl Produkte wird bei der Indizierung (s.o.) gleichzeitig verarbeitet, entsprechend viele
Produktdaten werden in einen einzigen Request zum Solr-Server aufgenommen. Von dieser Einstellung ist die Performance
der Indizierung stark abhängig. Reduzieren Sie den Wert testweise, falls sie Fehler bei der Indizierung erhalten.

#### Alle Solr-Indexeinträge vor Neuindizierung löschen

Diese Einstellung sollten Sie nur deaktivieren, wenn Sie nächtlich den Index komplett neu aufbauen, aber keinen 
SWAP-Kern (s.u.) nutzen. Wenn diese Einstellung aktiv ist, wird der Solr-Index zu Beginn einer vollständigen
Neuindizierung komplett geleert und anschließend neu erstellt.

#### Cores tauschen nach vollständiger Neuindizierung

Wenn Sie regelmäßig den Index neu aufbauen (z.B. nächtlich), ist es sinnvoll, die Funktion zum Tauschen der Kerne 
einzusetzen und einen zweiten Kern zu verwenden. Aktivieren Sie in dem Fall diese Option und tragen Sie im Feld 
*Name des Cores, mit dem der aktive Core getauscht werden soll* den Namen des zweiten Kerns ein.

### Unscharfe Suche 

![Unscharfe Suche](http://www.integer-net.de/download/solr/integernet-solr-config-fuzzy-de.png)

#### Ist aktiv für Suche

Wenn diese Einstellung ausgeschaltet ist, werden nur exakte Suchtreffer registriert. Eine Fehlerkorrektur findet dann
nicht mehr statt. Dafür ist die Suche schneller, wenn diese Einstellung deaktiviert ist.

#### Sensibilität für Suche

Hier können Sie eintragen, wie empfindlich die unscharfe Suche sein soll. Der Wert muss zwischen 0 und 1 liegen, mit dem 
Punkt (.) als Dezimaltrennzeichen, also z.B. *0.75*. Je niedriger der Wert, desto mehr Treffer werden Sie erhalten, da
Schreibfehler großzügiger korrigiert werden und z.B. für die Eingabe "rot" auch der Wert "rosa" akzeptiert wird, der 
von den Buchstaben her relativ ähnlich ist. Testen Sie hier einen möglichst guten Wert für Ihren Shop aus. Wir empfehlen
Werte zwischen 0.6 und 0.9.

#### Ist aktiv für Suchvorschläge

Wie oben, aber für die Suchvorschlags-Box (Autosuggest) individuell einstellbar. Es kann interessant sein, diese 
Funktion nur für die Suchvorschläge aus Performancegründen auszuschalten.

#### Sensibilität für Suchvorschläge

Wie oben, aber für die Suchvorschlags-Box (Autosuggest) individuell einstellbar.

### Suchergebnisse

![Suchergebnisse](http://www.integer-net.de/download/solr/integernet-solr-config-results-de.png)

#### HTML vom Solr-Index verwenden

Wenn diese Eigenschaft aktiviert ist, wird der HTML-Code, der bei den Suchergebnissen ein Produkt darstellt, bereits
bei der Indizierung erzeugt. Diese dauert dadurch natürlich etwas länger, dafür erfolgt die Ausgabe bei den Suchergebnissen
schneller, da dieser Teil nicht mehr (mehrfach, da für mehrere Produkte) berechnet werden muss.

Wir empfehlen daher, diese Einstellung zu aktivieren. Eine Ausnahme liegt vor, wenn die Daten in den Suchergebnissen
benutzer- oder benutzergruppenabhängig dargestellt werden müssen, also wenn z.B. die Preise je nach Kundengruppe
unterschiedlich sind. In diesem Fall deaktivieren Sie diese Einstellung bitte.

#### Such-Operator

Hier haben Sie die Wahl zwischen *UND* und *ODER*. Der Such-Operator wird eingesetzt, wenn es mehr als einen Suchbegriff
in der Anfrage gibt, z.B. "rotes Shirt". Bei *UND* werden nur Ergebnisse ausgegeben, die auf beide (bzw. alle) 
Suchbegriffe passen, bei *ODER* werden dafür auch Ergebnisse ausgegeben, die nur auf einen der Suchbegriffe passen. In 
den meisten Fällen ist *UND* die bessere Einstellung.

#### Größe der Preis-Schritte

Diese Einstellung ist für den Preisfilter wichtig. Hier kann man einstellen, in welchen Schritten die einzelnen 
Intervalle definiert sein sollen. So führt z.B. *10* zu den Intervallen *0,00-10,00*, *10,00-20,00*, *20,00-30,00* usw.
 
#### Obergrenze der Preis-Schritte

Auch diese Einstellung ist für die Steuerung des Preisfilters gedacht. Hierüber wird das oberste Intervall definiert.
Beim Wert *200* wäre das also *ab 200,00*. In diesem Intervall werden alle Produkte zusammen gefasst, die mehr als 
200,00 kosten.

#### Individuelle Preisintervalle verwenden
Wenn Sie keine lineare Einteilung der Intervalle wünschen und mindestens Solr 4.10 einsetzen, können Sie hier die 
gewünschten Intervallgrenzen für den Preisfilter individuell einstellen. Beim Beispiel *10,20,50,100,200,300,400,500* 
wären das die Schritte *0,00-10,00*, *10,00-20,00*, *20,00-50,00* usw. bis *400,00-500,00* und *ab 500,00*. 

### Suchvorschlags-Box

Die Suchvorschlags-Box wird auch als "Suchvorschau" oder "Autosuggest" bezeichnet.

![Suchvorschlags-Box](http://www.integer-net.de/download/solr/integernet-solr-config-autosuggest-de.png)

#### Ist aktiv

Bei Deaktivieren dieser Einstellung wird kein Suchvorschaufenster angezeigt.

#### Methode zum Ermitteln von Suchvorschlags-Informationen

Diese Einstellung wurde bereits oben im Kapitel "Technischer Ablauf" umfassend beschrieben.

#### Maximale Anzahl Suchwort-Vorschläge

Die Anzahl der Suchwort-Vorschläge in der Suchvorschlags-Box. Abhängig von Ihren Produkten wird der eingegebene
Suchbegriff um sinnvolle Varianten ergänzt. Bei Eingabe von "re" im Demo-Shop erscheinen z.B. die folgenden
Vorschläge: *regular…*, *resistant…*, *refined…*, *red…*.
 
#### Maximale Anzahl Produkt-Vorschläge

Die Anzahl der in der Suchvorschau angezeigten Produkte.

#### Maximale Anzahl Kategorie-Vorschläge

Die Anzahl der in der Suchvorschau angezeigten Kategorien. Dies sind die Kategorien, die am besten zu den gefundenen
Produkten passen.

#### Kompletten Kategorie-Pfad anzeigen

Ist diese Einstellung aktiv, werden nicht nur die Kategorienamen angezeigt, sondern auch deren Elternkategorien als
Pfad, beispielsweise "Electronics > Cameras > Accessories" statt nur "Accessoires".

#### Typ von Kategorie-Links

Hier geht es um den Link, der hinter den angezeigten Kategorien steht. Die Optionen sind:

- Suchergebnisseite mit gesetztem Kategoriefilter, sodass nur Produkte in der gewählten Kategorie angezeigt werden
- Kategorieseite

#### Attributfilter-Vorschläge

Hier können Sie beliebig viele Attribute eintragen, die in der Suchvorschau mit den am häufigsten vorkommenden 
Optionen dargestellt werden. Sie können jeweils das Attribut auswählen und die Anzahl der angezeigten Optionen 
definieren. Außerdem können Sie die Reihenfolge der Attribute bestimmen - das Attribut mit dem kleinsten Wert bei
"Sortierung" wird zuoberst angezeigt.

Es stehten nur Attribute zur Auswahl, die die Eigenschaft "Filternavigation auf Suchergebnisseiten verwenden" haben.

Modifikation der Reihenfolge der Suchergebnisse
-------------

Die Suchergebnisse werden bereits mit den Basiseinstellungen in eine Reihenfolge gebracht, die hauptsächlich von 
der Häufigkeit und der Position der Vorkommen der Suchbegriffe in den Produkteigenschaften abhängt. Erfahrungsgemäß 
werden damit bereits gute Ergebnisse erzielt - deutlich bessere als mit der Standardsuche von Magento.

Es gibt allerdings weitere Möglichkeiten der Anpassung:

### Boosting von Attributen

Wenn Suchbegriffe im Namen oder der Artikelnummer eines Artikels vorkommen, sollte dies höher gewertet werden als wenn
der gleiche Suchbegriff nur im Beschreibungstext vorkommt. Daher werden bereits im Standard manche Attribute höher
priorisiert als andere.

Die Priorisierung erfolgt anhand des Wertes "Solr-Priorität", die man jedem Produktattribut zuweisen kann. Diese neue
Eigenschaft kann man in der Auflistung der Attribute (unter *Katalog -> Attribute -> Attribute verwalten*) bereits
sehen:

![Attribut-Tabelle](http://www.integer-net.de/download/solr/integernet-solr-attribute-grid-de.png)

Die Tabelle ist hier bereits nach dem neuen Wert "Solr-Priorität" sortiert.
Den Wert können Sie in den Attributeigenschaften auch setzen:
 
![Attribut-Ansicht](http://www.integer-net.de/download/solr/integernet-solr-attribute-view-de.png)
 
Mit diesem Wert wird die errechnete Priorität des Suchbegriffes für das Produkt multipliziert, wenn der gesuchte Begriff
in dem Attribut gefunden wird. Daher entspricht *1.0* dem Standard - hier findet keine Modifikation statt. Somit können
Sie die Priorität von einzelnen Attributen erhöhen oder senken. Wir empfehlen Werte zwischen 0.5 und höchstens 10.

Beachten Sie, dass Sie nach der Anpassung der Suchpriorität den Solr-Index neu aufbauen müssen.

### Boosting von Produkten

Es kommt immer mal wieder vor, dass einzelne Produkte hervorgehoben werden sollen, sei es, weil sie die Topseller sind,
sei es, weil sie abverkauft werden sollen. Hierfür gibt es die Möglichkeit, die Priorität einzelner Produkt hoch- oder
herabzusetzen. 

Dafür gibt es das neue Produktattribut "Solr-Priorität" im Tab "Solr" der Produktansicht im Backend.

![Produkt-Ansicht](http://www.integer-net.de/download/solr/integernet-solr-product-boost-de.png)

Hierüber haben Sie die Möglichkeit, ein Produkt, sofern es zu den Suchbegriffen passt, weiter oben oder weiter unten
zu platzieren als seine Standard-Position. Wir empfehlen hier Werte zwischen 0.5 und höchstens 10. Der Mechanismus ist 
der gleiche wie beim Boosting von Attributen. Eine Neuindizierung ist nach der Anpassung nicht erforderlich, sofern
die Index-Aktualisierung aktiviert ist.

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