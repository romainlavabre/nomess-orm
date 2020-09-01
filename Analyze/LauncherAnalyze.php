<?php
ini_set( 'display_errors', 'on' );

define('ROOT', str_replace('vendor/newwebsouth/orm/Analyze', '', __DIR__));
require ROOT . 'vendor/autoload.php';
use Newwebsouth\Orm\Annotation\Parser;
use Newwebsouth\Orm\Cache\Builder\CacheBuilder;
use Newwebsouth\Orm\Cache\Builder\EntityBuilder;
use Newwebsouth\Orm\Cache\Builder\RelationBuilder;
use Newwebsouth\Orm\Cache\Builder\TableBuilder;
use Newwebsouth\Orm\Cache\CacheHandler;
use Newwebsouth\Orm\Driver\Pdo\PdoDriver;

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
        
        echo "Launch the analyze of tables...\r";
        ( new Newwebsouth\Orm\Analyze\Table( $driverHandler ) )->revalideTables();
        echo "OK\r";
        echo "Launch the analyze of columns...\r";
        ( new Newwebsouth\Orm\Analyze\Column( $driverHandler, $cacheHandler ) )->revalideColumns();
        echo "OK\r";
        echo "Launch the analyze of relations...\r";
        ( new Newwebsouth\Orm\Analyze\Relation( $driverHandler, $cacheHandler ) )->revalideRelation();
        echo "OK\r";
        
        echo "Your database is updated";
    }
}
