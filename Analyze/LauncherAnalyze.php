<?php

namespace Nomess\Component\Orm\Analyze;

use Nomess\Component\Config\ConfigStoreInterface;
use Nomess\Component\Orm\Analyze\PostgreSql\Column;
use Nomess\Component\Orm\Analyze\PostgreSql\Relation;
use Nomess\Component\Orm\Analyze\PostgreSql\Table;
use Nomess\Container\Container;

class LauncherAnalyze
{
    
    private const CONF_NAME = 'orm';
    private ConfigStoreInterface $configStore;
    
    
    public function __construct( ConfigStoreInterface $configStore )
    {
        $this->configStore = $configStore;
    }
    
    
    public function launch(): void
    {
        $server = $this->configStore->get( self::CONF_NAME )['connection']['server'];
        echo "Launch the analyze of tables...\n";
        
        if( $server === 'pgsql' ) {
            Container::getInstance()->get( Table::class )->revalideTables();
        } elseif( $server === 'mysql' ) {
            Container::getInstance()->get( \Nomess\Component\Orm\Analyze\Mysql\Table::class )->revalideTables();
        }
        
        echo "Launch the analyze of columns...\n";
        
        if( $server === 'pgsql' ) {
            Container::getInstance()->get( Column::class )->revalideColumns();
        } elseif( $server === 'mysql' ) {
            Container::getInstance()->get( \Nomess\Component\Orm\Analyze\Mysql\Column::class )->revalideColumns();
        }
        
        echo "Launch the analyze of relations...\n";
        
        if( $server === 'pgsql' ) {
            Container::getInstance()->get( Relation::class )->revalideRelation();
        } elseif( $server === 'mysql' ) {
            Container::getInstance()->get( \Nomess\Component\Orm\Analyze\Mysql\Relation::class )->revalideRelation();
        }
        
        echo "Your database is updated\n";
    }
}
