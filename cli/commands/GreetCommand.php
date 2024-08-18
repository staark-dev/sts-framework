<?php

namespace STS\cli\commands;

/**
 * Descriere: Comandă pentru a afișa un mesaj de salut
 */

//Descriere: Comandă pentru a afișa un mesaj de salut
class GreetCommand implements CommandInterface
{
    public function execute(array $args): void
    {
        $name = $args[0] ?? 'Guest';
        echo "Salut, $name!" . PHP_EOL;
    }
}