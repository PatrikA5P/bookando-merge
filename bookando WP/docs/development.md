# Entwicklung

## Statische Analyse (PHPStan)

* Neue Prüfschalter sorgen für strengere Ausnahmen- und Namespace-Kontrollen (`checkMissingThrowableCatch`, `checkClassCaseSensitivity`).
* Starte die Analyse mit `composer phpstan`. Der Befehl nutzt dieselben Pfade wie `lint:phpstan` und muss vor einem Commit sauber durchlaufen.
* Bei Zeit- oder Datumsberechnungen sollten bevorzugt exception-freie Helfer wie `DateInterval::createFromDateString()` und interne Wrapper verwendet werden, damit PHPStan keine fehlenden `@throws`-Annotationen meldet.
