<?php

declare(strict_types=1);

namespace exAuth\Commands;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Commands\Database\Migrate;
use exAuth\Commands\Setup\ContentReplacer;
use CodeIgniter\Test\Filters\CITestStreamFilter;
use Config\Autoload as AutoloadConfig;
use Config\Email as EmailConfig;
use Config\Services;

class Setup extends BaseCommand
{
    protected $name = 'exauth:setup';

    protected $description = 'Initial setup for exAuth.';

    protected $usage = 'exauth:setup';

    protected $arguments = [];

    protected $options = [
        '-f' => 'Force overwrite ALL existing files in destination.',
    ];

    protected string $sourcePath;

    protected string $distPath = APPPATH;

    private ContentReplacer $replacer;

    public function run(array $params): void
    {
        $this->replacer = new ContentReplacer();

        $this->sourcePath = __DIR__ . '/../';

        $this->publishConfig();
    }

    private function publishConfig(): void
    {
        $this->publishConfigAuth();
        $this->publishConfigAuthGroups();

        $this->setAutoloadHelpers();
        $this->setupRoutes();

        $this->setSecurityCSRF();
        $this->setupEmail();

        $this->runMigrations();
    }

    protected function copyAndReplace(string $file, array $replaces): void
    {
        $path    = "{$this->sourcePath}/{$file}";
        $content = file_get_contents($path);
        $content = $this->replacer->replace($content, $replaces);

        $this->writeFile($file, $content);
    }

    private function publishConfigAuth(): void
    {
        $file     = 'Config/exAuth.php';
        $replaces = [
            'namespace exAuth\Config' => 'namespace Config',
            'class exAuth'            => 'class exAuth extends \exAuth\Config\exAuth',
        ];

        $this->copyAndReplace($file, $replaces);
    }

    private function publishConfigAuthGroups(): void
    {
        $file     = 'Config/AuthGroups.php';
        $replaces = [
            'namespace exAuth\Config'            => 'namespace Config',
            'class AuthGroups'                   => 'class AuthGroups extends \exAuth\Config\AuthGroups',
        ];

        $this->copyAndReplace($file, $replaces);
    }

    protected function writeFile(string $file, string $content): void
    {
        $path      = $this->distPath . $file;
        $cleanPath = clean_path($path);

        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (file_exists($path)) {
            $overwrite = (bool) CLI::getOption('f');

            if (
                ! $overwrite
                && $this->prompt("  File '{$cleanPath}' already exists in destination. Overwrite?", ['n', 'y']) === 'n'
            ) {
                $this->error("  Skipped {$cleanPath}. If you wish to overwrite, please use the '-f' option or reply 'y' to the prompt.");

                return;
            }
        }

        if (write_file($path, $content)) {
            $this->write(CLI::color('  Created: ', 'green') . $cleanPath);
        } else {
            $this->error("  Error creating {$cleanPath}.");
        }
    }

    protected function add(string $file, string $code, string $pattern, string $replace): void
    {
        $path      = $this->distPath . $file;
        $cleanPath = clean_path($path);

        $content = file_get_contents($path);
        $output  = $this->replacer->add($content, $code, $pattern, $replace);

        if ($output === true) {
            $this->error("  Skipped {$cleanPath}. It has already been updated.");

            return;
        }
        if ($output === false) {
            $this->error("  Error checking {$cleanPath}.");

            return;
        }

        if (write_file($path, $output)) {
            $this->write(CLI::color('  Updated: ', 'green') . $cleanPath);
        } else {
            $this->error("  Error updating {$cleanPath}.");
        }
    }

    private function replace(string $file, array $replaces): bool
    {
        $path      = $this->distPath . $file;
        $cleanPath = clean_path($path);

        $content = file_get_contents($path);
        $output  = $this->replacer->replace($content, $replaces);

        if ($output === $content) {
            return false;
        }

        if (write_file($path, $output)) {
            $this->write(CLI::color('  Updated: ', 'green') . $cleanPath);

            return true;
        }

        $this->error("  Error updating {$cleanPath}.");

        return false;
    }

    private function setAutoloadHelpers(): void
    {
        $file = 'Config/Autoload.php';

        $path      = $this->distPath . $file;
        $cleanPath = clean_path($path);

        $config     = new AutoloadConfig();
        $helpers    = $config->helpers;
        $newHelpers = array_unique(array_merge($helpers, ['exAuth', 'setting']));

        $content = file_get_contents($path);
        $output  = $this->updateAutoloadHelpers($content, $newHelpers);

        if ($output === $content) {
            $this->write(CLI::color('  Autoload Setup: ', 'green') . 'Everything is fine.');

            return;
        }

        if (write_file($path, $output)) {
            $this->write(CLI::color('  Updated: ', 'green') . $cleanPath);

            $this->removeHelperLoadingInBaseController();
        } else {
            $this->error("  Error updating file '{$cleanPath}'.");
        }
    }

    private function updateAutoloadHelpers(string $content, array $newHelpers): string
    {
        $pattern = '/^    public \$helpers = \[.*?\];/msu';
        $replace = '    public $helpers = [\'' . implode("', '", $newHelpers) . '\'];';

        return preg_replace($pattern, $replace, $content);
    }

    private function removeHelperLoadingInBaseController(): void
    {
        $file = 'Controllers/BaseController.php';

        $check = '        $this->helpers = array_merge($this->helpers, [\'setting\']);';

        $replaces = [
            '$this->helpers = array_merge($this->helpers, [\'exAuth\', \'setting\']);' => $check,
        ];
        $this->replace($file, $replaces);

        $replaces = [
            "\n" . $check . "\n" => '',
        ];
        $this->replace($file, $replaces);
    }

    private function setupRoutes(): void
    {
        $file = 'Config/Routes.php';

        $check   = "service('auth')->routes(\$routes);";
        $pattern = '/(.*)(\n' . preg_quote('$routes->', '/') . '[^\n]+?;\n)/su';
        $replace = '$1$2' . "\n" . $check . "\n";

        $this->add($file, $check, $pattern, $replace);
    }

    private function setSecurityCSRF(): void
    {
        $file     = 'Config/Security.php';
        $replaces = [
            '$csrfProtection = \'cookie\';' => '$csrfProtection = \'session\';',
        ];

        $path      = $this->distPath . $file;
        $cleanPath = clean_path($path);

        if (! is_file($path)) {
            $this->error("  Not found file '{$cleanPath}'.");

            return;
        }

        $content = file_get_contents($path);
        $output  = $this->replacer->replace($content, $replaces);

        if ($output === $content) {
            $this->write(CLI::color('  Security Setup: ', 'green') . 'Everything is fine.');

            return;
        }

        if (write_file($path, $output)) {
            $this->write(CLI::color('  Updated: ', 'green') . "We have updated file '{$cleanPath}' for security reasons.");
        } else {
            $this->error("  Error updating file '{$cleanPath}'.");
        }
    }

    private function setupEmail(): void
    {
        $file = 'Config/Email.php';

        $path      = $this->distPath . $file;
        $cleanPath = clean_path($path);

        if (! is_file($path)) {
            $this->error("  Not found file '{$cleanPath}'.");

            return;
        }

        $config    = config(EmailConfig::class);
        $fromEmail = (string) $config->fromEmail;
        $fromName  = (string) $config->fromName;

        if ($fromEmail !== '' && $fromName !== '') {
            $this->write(CLI::color('  Email Setup: ', 'green') . 'Everything is fine.');

            return;
        }

        $content = file_get_contents($path);
        $output  = $content;

        if ($fromEmail === '') {
            $set = $this->prompt('  The required Config\Email::$fromEmail is not set. Do you set now?', ['y', 'n']);

            if ($set === 'y') {
                $fromEmail = $this->prompt('  What is your email?', null, 'required|valid_email');

                $pattern = '/^    public .*\$fromEmail\s+= \'\';/mu';
                $replace = '    public string $fromEmail  = \'' . $fromEmail . '\';';
                $output  = preg_replace($pattern, $replace, $content);
            }
        }

        if ($fromName === '') {
            $set = $this->prompt('  The required Config\Email::$fromName is not set. Do you set now?', ['y', 'n']);

            if ($set === 'y') {
                $fromName = $this->prompt('  What is your name?', null, 'required');

                $pattern = '/^    public .*\$fromName\s+= \'\';/mu';
                $replace = '    public string $fromName   = \'' . $fromName . '\';';
                $output  = preg_replace($pattern, $replace, $output);
            }
        }

        if (write_file($path, $output)) {
            $this->write(CLI::color('  Updated: ', 'green') . $cleanPath);
        } else {
            $this->error("  Error updating file '{$cleanPath}'.");
        }
    }

    private function runMigrations(): void
    {
        if (
            $this->prompt('  Run `spark migrate --all` now?', ['y', 'n']) === 'n'
        ) {
            return;
        }

        $command = new Migrate(Services::logger(), Services::commands());

        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();
        CITestStreamFilter::addErrorFilter();

        $command->run(['all' => null]);

        CITestStreamFilter::removeOutputFilter();
        CITestStreamFilter::removeErrorFilter();

        $output = CITestStreamFilter::$buffer;
        $this->write($output);

        CITestStreamFilter::$buffer = '';
    }
}
