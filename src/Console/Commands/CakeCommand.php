<?php

namespace Ostoandel\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArgvInput;

class CakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cake';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the cake command';

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Console\Command::run()
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        return parent::run(new ArgvInput([]), $output);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        require_once CAKE_CORE_INCLUDE_PATH . '/Cake/Console/ShellDispatcher.php';

        $argv = $_SERVER['argv'];
        array_shift($argv); // artisan
        array_shift($argv); // cake
        (new \ShellDispatcher($argv, false))->dispatch();
    }

}
