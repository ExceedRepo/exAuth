<?php

declare(strict_types=1);

namespace exAuth\Commands;

use CodeIgniter\CLI\BaseCommand as FrameworkBaseCommand;
use CodeIgniter\CLI\Commands;
use exAuth\Commands\Utils\InputOutput;
use Psr\Log\LoggerInterface;

abstract class BaseCommand extends FrameworkBaseCommand
{
    protected static ?InputOutput $io = null;

    protected $group = 'exAuth';

    public function __construct(LoggerInterface $logger, Commands $commands)
    {
        parent::__construct($logger, $commands);

        $this->ensureInputOutput();
    }

    protected function prompt(string $field, $options = null, $validation = null): string
    {
        return self::$io->prompt($field, $options, $validation);
    }

    protected function write(string $text = '', ?string $foreground = null, ?string $background = null): void
    {
        self::$io->write($text, $foreground, $background);
    }

    protected function error(string $text, string $foreground = 'light_red', ?string $background = null): void
    {
        self::$io->error($text, $foreground, $background);
    }

    protected function ensureInputOutput(): void
    {
        if (self::$io === null) {
            self::$io = new InputOutput();
        }
    }

    public static function setInputOutput(InputOutput $io): void
    {
        self::$io = $io;
    }

    public static function resetInputOutput(): void
    {
        self::$io = null;
    }
}
