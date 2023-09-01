<?php

namespace EnjoysCMS\Module\Catalog\Composer\Scripts;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class AssetsInstallCommand extends Command
{

    /**
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $process = new Process([
            'yarn',
            'install',
        ], cwd: realpath(__DIR__ . '/..'));

        $output->writeln(sprintf("<info>%s</info>", $process->getWorkingDirectory()));

        $process->setTimeout(60);

        $process->run(
            function ($type, $buffer) use ($output) {
                $output->write((Process::ERR === $type) ? 'ERR:' . $buffer : $buffer);
            }
        );
        return Command::SUCCESS;
    }
}
