<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console;

use AdamczykPiotr\UltimateFormatter\Console\Commands\AnalyseCommand;
use AdamczykPiotr\UltimateFormatter\Console\Commands\CheckCommand;
use AdamczykPiotr\UltimateFormatter\Console\Commands\FormatCommand;
use AdamczykPiotr\UltimateFormatter\Console\Commands\InstallCommand;
use Symfony\Component\Console\Application;

class FormatterApplication extends Application
{
    /**
     * Short alias for the standalone binary, mapped to the command class.
     */
    public const COMMANDS = [
        'install' => InstallCommand::class,
        'format' => FormatCommand::class,
        'check' => CheckCommand::class,
        'analyse' => AnalyseCommand::class,
    ];

    public function __construct()
    {
        parent::__construct('Ultimate Formatter');

        foreach (self::COMMANDS as $alias => $commandClass) {
            $command = new $commandClass();
            $command->setAliases([$alias]);
            $this->addCommand($command);
        }
    }
}
