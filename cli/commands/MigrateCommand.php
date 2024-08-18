<?php

namespace STS\cli\commands;

/**
 * Migrare tabele in baza de date
 */
class MigrateCommand implements CommandInterface
{
    public function execute(array $args): void
    {
        echo "Migrarea bazei de date a început..." . PHP_EOL;
        // Logica migrare
        echo "Migrarea bazei de date a fost realizată cu succes." . PHP_EOL;
    }
}