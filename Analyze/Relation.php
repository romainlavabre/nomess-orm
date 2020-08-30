<?php


namespace Newwebsouth\Orm\Analyze;


use Newwebsouth\Orm\Cache\CacheHandlerInterface;
use Newwebsouth\Orm\Driver\DriverHandlerInterface;

/**
 * TODO Delete foreign key and constraint
 */
class Relation extends AbstractAnalyze
{
    
    private CacheHandlerInterface $cacheHandler;
    private array                 $joinTables = array();
    
    
    public function __construct(
        DriverHandlerInterface $driverHandler,
        CacheHandlerInterface $cacheHandler )
    {
        parent::__construct( $driverHandler );
        $this->cacheHandler = $cacheHandler;
    }
    
    
    public function revalideRelation(): void
    {
        foreach( $this->directories() as $directory ) {
            foreach( scandir( $directory ) as $files ) {
                foreach( $files as $file ) {
                    if( $file !== '.' && $file !== '..' && $file !== '.gitkeep'
                        && ( $reflectionClass = $this->getReflectionClass( $directory . $file ) ) !== NULL ) {
                        
                        $cache              = $this->cacheHandler->getCache( $reflectionClass->getName() );
                        $this->joinTables[] = $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
                        
                        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
                            if( $array[CacheHandlerInterface::ENTITY_RELATION] !== NULL ) {
                                if( $array[CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE] !== NULL ) {
                                    $this->joinTables[] = $array[CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE];
                                }
                            }
                        }
                    }
                }
            }
        }
        
        $this->purgeJoinTable();
    }
    
    
    public function createRelation( array $config, string $tableName ): void
    {
        $relationType  = $config[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_TYPE];
        $tableRelation = $this->cacheHandler->getCache(
            $config[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME]
        )[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
        
        if( $relationType === 'ManyToMany' ) {
            try {
                $this->driverHandler->getConnection()
                                    ->query( 'CREATE TABLE IF NOT EXISTS `' .
                                             $config[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE] .
                                             '`
                                     (
                                        `' . $tableName . '_id` INT UNSIGNED NOT NULL,
                                        `' . $tableRelation . '_id` INT UNSIGNED NOT NULL,
                                        UNIQUE KEY `UQ_' . $tableName . '_' . $tableRelation . '` (`' . $tableName . '_id`,`' . $tableRelation . '_id`),
                                        KEY `index_foreignkey_' . $tableName . '_' . $tableRelation . '` (`' . $tableName . '_id`),
                                        KEY `index_foreignkey_' . $tableRelation . '_' . $tableName . '` (`' . $tableRelation . '_id`),
                                        CONSTRAINT `c_fk_' . $tableName . '_' . $tableRelation . '_id` FOREIGN KEY (`' . $tableName . '_id`) REFERENCES `' . $tableName . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                                        CONSTRAINT `c_fk_' . $tableRelation . '_' . $tableName . '_id` FOREIGN KEY (`' . $tableRelation . '_id`) REFERENCES `' . $tableRelation . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                     )ENGINE=InnoDB DEFAULT CHARSET=utf8;' )->execute();
            } catch( \Throwable $th ) {
            }
        } elseif( $relationType === 'OneToMany' || $relationType === 'OneToOne' ) {
            try {
                $this->driverHandler->getConnection()->query( '
                ALTER TABLE ' . $tableName . '
                ADD FOREIGN KEY `index_foreignkey_' . $tableName . '_' . $tableRelation . '` (`' . $tableRelation . '_id`),
                ADD CONSTRAINT `c_fk_' . $tableName . '_' . $tableRelation . '_id` FOREIGN KEY (`' . $tableRelation . '_id`) REFERENCES `' . $tableRelation . '` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
            ' )->execute();
            } catch( \Throwable $th ) {
            }
        }
    }
    
    
    private function purgeJoinTable(): void
    {
        $statement = $this->driverHandler->getConnection()->query();
        $statement->execute();
        
        foreach( $statement->fetchAll() as $data ) {
            if( !in_array( $data[0], $this->joinTables ) ) {
                $query = 'DROP TABLE `' . $data[0] . '`;';
                $this->driverHandler->getConnection()->query( $query )->execute();
            }
        }
    }
}
