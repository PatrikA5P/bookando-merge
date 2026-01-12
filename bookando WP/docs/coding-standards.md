# Bookando Coding Standards

Dieser Leitfaden fasst die wichtigsten Konventionen für neue und bestehende PHP-Module zusammen.

## PHP

- **Strict Types:** Jedes Einstiegsscript (`Module.php`, `Capabilities.php`, vergleichbare Bootstrap-Dateien) muss mit `<?php declare(strict_types=1);` beginnen.
- **Typisierung:** Verwende native Parameter- und Rückgabe-Typen. Ergänze präzise PHPDoc-Annotationen (`@param`, `@return`, strukturierte Arrays), wenn WordPress-APIs oder gemischte Werte keine volle Signatur erlauben.
- **Null-Checks:** WordPress-Helfer wie `get_role()` können `null` zurückgeben. Prüfe diese Rückgabewerte, bevor du Methoden wie `add_cap()` aufrufst.
- **Filter & Hooks:** Callback-Signaturen sollen `callable(): void` sein und – wo möglich – mit statisch typisierten Closures (`static function (): void {}`) registriert werden.

## Tooling

- **PHPStan:** `composer lint:phpstan` prüft alle PHP-Dateien. Stelle sicher, dass neue Module keine Fehler erzeugen.
- **CI:** Der GitHub Actions Build führt PHPStan automatisch aus. Ein Merge ist nur erlaubt, wenn der Lauf erfolgreich ist.

## Struktur

- Module registrieren Capabilities ausschließlich über `BaseModule::registerCapabilities()`.
- Gemeinsame Funktionalität gehört in `Bookando\Core` und wird dort getestet/typisiert.

Halte diesen Leitfaden aktuell, wenn neue Anforderungen dazukommen.
