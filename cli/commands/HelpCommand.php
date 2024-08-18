<?php

namespace STS\cli\commands;

class HelpCommand implements CommandInterface
{
    protected $availableCommands;

    public function __construct($availableCommands)
    {
        $this->availableCommands = $availableCommands;
    }

    /**
     * @throws \ReflectionException
     */
    public function execute(array $args): void
    {
        echo "Comenzi disponibile:\n\n";
        foreach ($this->availableCommands as $command => $class) {
            echo " " . str_pad($command, 15) . " - " . $this->getDescription($class) . PHP_EOL;
        }
    }

    /**
     * @throws \ReflectionException
     */
    private function getDescription(string $class): string
    {
        $reflection = new \ReflectionClass($class);
        $docComment = $reflection->getDocComment();
        if ($docComment) {
            $matches = [];
            preg_match('/\*\s(.*)$/m', $docComment, $matches);
            return $matches[1] ?? 'Fără descriere.';
        }
        return 'Fără descriere.';
    }
}
