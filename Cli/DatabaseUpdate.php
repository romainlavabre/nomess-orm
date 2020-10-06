<?php


namespace Nomess\Component\Orm\Cli;


use Nomess\Component\Cli\Interactive\InteractiveInterface;
use Nomess\Component\Orm\Analyze\LauncherAnalyze;
use Nomess\Component\Cli\Executable\ExecutableInterface;

class DatabaseUpdate implements ExecutableInterface
{
    
    private LauncherAnalyze $launcherAnalyze;
    
    public function __construct( 
        LauncherAnalyze $launcherAnalyze)
    {
        $this->launcherAnalyze =$launcherAnalyze;
    }
    
    
    public function exec( array $command ): void
    {
        $this->launcherAnalyze->launch();
    }
}
