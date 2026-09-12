# Demo ledger — alquiler-equipos-sonido

Ejercicio in-memory. No toca Doctrine ni `Clarity\\Nucleo\\HoldPipe\\Pipe`.

Usa `OriginTrust` y añade cupo + ledger + detector + closer.

```bash
git fetch origin demo/ledger-recon && git checkout demo/ledger-recon
php bin/phpunit tests/Nucleo/DemoLedgerTest.php
```
