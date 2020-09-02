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
    private array                 $joinColumn = array();
    
    
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
            foreach( scandir( $directory ) as $file ) {
                if( $file !== '.' && $file !== '..' && $file !== '.gitkeep'
                    && ( $reflectionClass = $this->getReflectionClass( $directory . $file, $file ) ) !== NULL
                    && $reflectionClass->isInstantiable()) {
                    
                    $cache              = $this->cacheHandler->getCache( $reflectionClass->getName() );
                    $this->joinTables[] = $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
                    
                    foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
                        if( $array[CacheHandlerInterface::ENTITY_RELATION] !== NULL ) {
                            if( $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE] !== NULL ) {
                                $this->joinTables[] = $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE];
                            }
                            
                            $this->createRelation( $array, $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] );
                        }
                        
                        $this->joinColumn[] = $array[CacheHandlerInterface::ENTITY_COLUMN];
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
        
        if( $relationType === 'ManyToMany' ) {
            try {
                echo "Try to create table " . $config[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE] . " if not exists";
                $this->driverHandler->getConnection()
                                    ->query( 'CREATE TABLE IF NOT EXISTS `' .
                                             $config[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE] .
                                             '`
                                     (
                                        `' . $tableName . '_id` INT UNSIGNED NOT NULL,
                                        `' . $tableRelation . '_id` INT UNSIGNED NOT NULL,
                                        UNIQUE KEY `UQ_' . $tableName . '_' . $tableRelation . '` (`' . $tableName . '_id`,`' . $tableRelation . '_id`),
                                        KEY `fk_' . $tableName . '_' . $tableName . '` (`' . $tableName . '_id`),
                                        KEY `fk_' . $tableRelation . '_' . $tableName . '` (`' . $tableRelation . '_id`),
                                        CONSTRAINT `c_' . $tableName . '_' . $tableName . '` FOREIGN KEY (`' . $tableName . '_id`) REFERENCES `' . $tableName . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                                        CONSTRAINT `c_' . $tableRelation . '_' . $tableName . '` FOREIGN KEY (`' . $tableRelation . '_id`) REFERENCES `' . $tableRelation . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                     )ENGINE=InnoDB DEFAULT CHARSET=utf8;' )->execute();
            } catch( \Throwable $th ) {
            }
            
            echo "\n";
        } else {
            $this->joinColumn[] = $tableRelation . '_id';
            
            try {
                echo "Try to create column " . $tableName . "_id...";
                $this->driverHandler->getConnection()->query( '
                ALTER TABLE `' . $tableName . '`
                ADD `' . $tableRelation . '_id` INT UNSIGNED NULL,
                ADD KEY `fk_' . $tableRelation . '_' . $tableName . '` (`' . $tableRelation . '_id`),
                ADD CONSTRAINT `c_' . $tableRelation .'_' . $tableName . '` FOREIGN KEY (`' . $tableRelation . '_id`) REFERENCES `' . $tableRelation . '` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
            ' )->execute();
            } catch( \Throwable $th ) {
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
                }catch(\Throwable $th){
                    echo "fails";
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
                }catch(\Throwable $e){
                    echo "fails";
                }
                
                echo "\n";
            }
        }
    }
}
