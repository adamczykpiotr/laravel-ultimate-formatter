<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class FormatterCommand extends Command
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('path', null, InputOption::VALUE_REQUIRED, 'Project root, defaults to the working directory');
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    protected function projectPath(InputInterface $input): string
    {
        $path = $input->getOption('path');
        if (is_string($path)) {
            return $path;
        }

        $workingDirectory = getcwd();
        if ($workingDirectory === false) {
            throw new RuntimeException('Cannot determine the working directory, pass --path.');
        }

        return $workingDirectory;
    }
}
