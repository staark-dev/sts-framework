<?php
namespace STS\cli\commands;

interface CommandInterface
{
    public function execute(array $args): void;
}