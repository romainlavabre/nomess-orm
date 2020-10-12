<?php


namespace Nomess\Component\Orm\Analyze\Mysql;


use Nomess\Component\Config\ConfigStoreInterface;
use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\Driver\DriverHandlerInterface;

/**
 * TODO Delete foreign key and constraint
 */
class Relation extends AbstractAnalyze
{
    
    private CacheHandlerInterface $cacheHandler;
    private array                 $joinTables = array();
    private array                 $joinColumn = array();
    
    
    public function __construct(
        DriverHandlerInterface $driverHandler,
        CacheHandlerInterface $cacheHandler,
        ConfigStoreInterface $configStore )
    {
        parent::__construct( $driverHandler, $configStore );
        $this->cacheHandler = $cacheHandler;
    }
    
    
    public function revalideRelation(): void
    {
        foreach( $this->directories() as $directory ) {
            foreach( scandir( $directory ) as $file ) {
                if( $file !== '.' && $file !== '..' && $file !== '.gitkeep'
                    && ( $reflectionClass = $this->getReflectionClass( $directory . $file, $file ) ) !== NULL
                    && $reflectionClass->isInstantiable() ) {
                    
                    $cache              = $this->cacheHandler->getCache( $reflectionClass->getName() );
                    $this->joinTables[] = $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
                    
                    foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
                        if( $array[CacheHandlerInterface::ENTITY_RELATION] !== NULL ) {
                            
                            if( $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE] !== NULL ) {
                                $this->joinTables[] = $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE];
                            }
                            
                            $this->createRelation( $array, $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] );
                        }
                        
                        $this->joinColumn[] = $array[CacheHandlerInterface::ENTITY_COLUMN_NAME];
                    }
                    
                    $this->purgeForeignKey( $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME], $cache );
                }
            }
        }
        
        $this->purgeJoinTable( $this->driverHandler->getDatabase() );
    }
    
    
    private function createRelation( array $config, string $tableName ): void
    {
        $relationType  = $config[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_TYPE];
        $tableRelation = $this->cacheHandler->getCache(
            $config[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME]
        )[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
        $onUpdate      = $config[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_ON_UPDATE];
        $onDelete      = $config[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_ON_DELETE];
        
        if( $relationType === 'ManyToMany' ) {
            try {
                $tableJoin = $config[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE];
                
                echo "Try to create table " . $config[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE] . " if not exists\n";
                $this->driverHandler->getConnection()
                                    ->query( 'CREATE TABLE IF NOT EXISTS `' . $tableJoin . '`
                                     (
                                        `' . $tableName . '_id` INT UNSIGNED NOT NULL,
                                        `' . $tableRelation . '_id` INT UNSIGNED NOT NULL,
                                        UNIQUE KEY `UQ_' . $tableName . '_' . $tableJoin . '` (`' . $tableName . '_id`,`' . $tableRelation . '_id`),
                                        KEY `fk_' . $tableName . '_' . $tableJoin . '` (`' . $tableName . '_id`),
                                        KEY `fk_' . $tableRelation . '_' . $tableJoin . '` (`' . $tableRelation . '_id`),
                                        CONSTRAINT `c_' . $tableName . '_' . $tableJoin . '` FOREIGN KEY (`' . $tableName . '_id`) REFERENCES `' . $tableName . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                                        CONSTRAINT `c_' . $tableRelation . '_' . $tableJoin . '` FOREIGN KEY (`' . $tableRelation . '_id`) REFERENCES `' . $tableRelation . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                     )ENGINE=InnoDB DEFAULT CHARSET=utf8;' )->execute();
            } catch( \Throwable $th ) {
                echo $th->getMessage() . "\n";
            }
        } else {
            
            if( $relationType === 'OneToOne' && !$config[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_OWNER] ) {
                return;
            }
            
            $this->joinColumn[] = $tableRelation . '_id';
            
            if( $relationType === 'OneToMany' || $relationType === 'OneToOne' ) {
                try {
                    echo "Try to create column " . $tableName . "_id for relation " . $tableName . " => " . $tableRelation . "...";
                    $this->driverHandler->getConnection()->query( '
                ALTER TABLE `' . $tableName . '`
                ADD `' . $tableRelation . '_id` INT UNSIGNED NULL,
                ADD KEY `fk_' . $tableRelation . '_' . $tableName . '` (`' . $tableRelation . '_id`),
                ADD CONSTRAINT `c_' . $tableRelation . '_' . $tableName . '` FOREIGN KEY (`' . $tableRelation . '_id`) REFERENCES `' . $tableRelation . '` (`id`) ON DELETE ' . $onDelete . ' ON UPDATE ' . $onUpdate . '
            ' )->execute();
                } catch( \Throwable $th ) {
                }
            } elseif( $relationType === 'ManyToOne' ) {
                try {
                    echo "Try to create column " . $tableRelation . "_id for relation " . $tableRelation . " => " . $tableName . "...";
                    $this->driverHandler->getConnection()->query( '
                ALTER TABLE `' . $tableRelation . '`
                ADD `' . $tableName . '_id` INT UNSIGNED NULL,
                ADD KEY `fk_' . $tableName . '_' . $tableRelation . '` (`' . $tableName . '_id`),
                ADD CONSTRAINT `c_' . $tableName . '_' . $tableRelation . '` FOREIGN KEY (`' . $tableName . '_id`) REFERENCES `' . $tableName . '` (`id`) ON DELETE ' . $onDelete . ' ON UPDATE ' . $onUpdate . '
            ' )->execute();
                } catch( \Throwable $th ) {
                }
            }
            
            
            echo "\n";
        }
    }
    
    
    private function purgeJoinTable( string $database ): void
    {
        $statement = $this->driverHandler->getConnection()->query(
            'SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = "' . $database . '";'
        );
        $statement->execute();
        
        foreach( $statement->fetchAll() as $data ) {
            if( !in_array( $data[0], $this->joinTables ) ) {
                $query = 'DROP TABLE `' . $data[0] . '`;';
                
                try {
                    echo "Try to remove table " . $data[0] . "...";
                    $this->driverHandler->getConnection()->query( $query )->execute();
                } catch( \Throwable $th ) {
                }
                
                echo "\n";
            }
        }
    }
    
    
    private function purgeForeignKey( string $tableName, array $config ): void
    {
        $statement = $this->driverHandler->getConnection()->query(
            'DESCRIBE `' . $tableName . '`;'
        );
        $statement->execute();
        
        foreach( $statement->fetchAll() as $data ) {
            if( !in_array( $data[0], $this->joinColumn ) ) {
                $query = 'ALTER TABLE `' . $tableName . '`
                DROP CONSTRAINT `c_' . str_replace( '_id', '', $data[0] ) . '_' . $tableName . '`,
                DROP KEY `fk_' . str_replace( '_id', '', $data[0] ) . '_' . $tableName . '`,
                DROP COLUMN `' . $data[0] . '`;';
                
                try {
                    echo "Try to remove column " . $data[0] . "...";
                    $this->driverHandler->getConnection()->query( $query )->execute();
                } catch( \Throwable $e ) {
                }
                
                echo "\n";
            }
        }
    }
}
