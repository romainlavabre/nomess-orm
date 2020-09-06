<?php
ini_set( 'display_errors', 'on' );

define('ROOT', str_replace('vendor/newwebsouth/orm/Analyze', '', __DIR__));
require ROOT . 'vendor/autoload.php';
use Nomess\Component\Orm\Annotation\Parser;
use Nomess\Component\Orm\Cache\Builder\CacheBuilder;
use Nomess\Component\Orm\Cache\Builder\EntityBuilder;
use Nomess\Component\Orm\Cache\Builder\RelationBuilder;
use Nomess\Component\Orm\Cache\Builder\TableBuilder;
use Nomess\Component\Orm\Cache\CacheHandler;
use Nomess\Component\Orm\Driver\Pdo\PdoDriver;

( new LauncherAnalyze() )->launch();

class LauncherAnalyze
{
    
    public function launch(): void
    {
        $driverHandler = new PdoDriver();
        $cacheHandler  = new CacheHandler(
            new CacheBuilder(
                new EntityBuilder(
                    $parser = new Parser(),
                    new RelationBuilder(
                        $parser
                    )
                ),
                new TableBuilder()
            )
        );
        
        echo "Launch the analyze of tables...\n";
        ( new Nomess\Component\Orm\Analyze\Table( $driverHandler ) )->revalideTables();
        echo "Launch the analyze of columns...\n";
        ( new Nomess\Component\Orm\Analyze\Column( $driverHandler, $cacheHandler ) )->revalideColumns();
        echo "Launch the analyze of relations...\n";
        ( new Nomess\Component\Orm\Analyze\Relation( $driverHandler, $cacheHandler ) )->revalideRelation();
        
        echo "Your database is updated";
    }
}
