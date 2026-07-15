<?php

declare(strict_types=1);

namespace exAuth\Commands\Utils;

use CodeIgniter\CLI\CLI;

class InputOutput
{
    public function prompt(string $field, $options = null, $validation = null): string
    {
        return CLI::prompt($field, $options, $validation);
    }

    public function write(string $text = '', ?string $foreground = null, ?string $background = null): void
    {
        CLI::write($text, $foreground, $background);
    }

    public function error(string $text, string $foreground = 'light_red', ?string $background = null): void
    {
        CLI::error($text, $foreground, $background);
    }
}
