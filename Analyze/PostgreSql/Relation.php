<?php


namespace Nomess\Component\Orm\Analyze\PostgreSql;


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
                                    ->query( 'CREATE TABLE IF NOT EXISTS "' . $tableJoin . '"
                                     (
                                        "' . $tableName . '_id" INT NOT NULL,
                                        "' . $tableRelation . '_id" INT NOT NULL,
                                        CONSTRAINT UQ_' . $tableName . '_' . $tableJoin . ' UNIQUE ("' . $tableName . '_id","' . $tableRelation . '_id"),
                                        CONSTRAINT c_' . $tableName . '_' . $tableJoin . ' FOREIGN KEY ("' . $tableName . '_id") REFERENCES "' . $tableName . '" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
                                        CONSTRAINT c_' . $tableRelation . '_' . $tableJoin . ' FOREIGN KEY ("' . $tableRelation . '_id") REFERENCES "' . $tableRelation . '" ("id") ON DELETE CASCADE ON UPDATE CASCADE
                                     );' );
    
                $this->driverHandler->getConnection()
                                    ->query( 'CREATE INDEX fk_' . $tableName . '_' . $tableJoin . ' ON "' . $tableJoin . '" ("' . $tableName . '_id");' );
                
                $this->driverHandler->getConnection()
                                    ->query( 'CREATE INDEX fk_' . $tableRelation . '_' . $tableJoin . ' ON "' . $tableJoin . '" ("' . $tableRelation . '_id")' );
            } catch( \Throwable $th ) {
                if(strpos( $th->getMessage(), 'Duplicate') === FALSE) {
                    echo $th->getMessage() . "\n";
                }
            }
        } else {
            
            if( $relationType === 'OneToOne' && !$config[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_OWNER] ) {
                return;
            }
            
            $this->joinColumn[] = $tableRelation . '_id';
            
            if( $relationType === 'OneToMany' || $relationType === 'OneToOne' ) {
                try {
                    echo "Try to create column " . $tableName . "_id for relation " . $tableName . " => " . $tableRelation . "...";
    
    
                    $this->driverHandler->getConnection()
                                        ->query( '
                ALTER TABLE "' . $tableName . '"
                ADD COLUMN "' . $tableRelation . '_id" INT NULL,
                ADD CONSTRAINT c_' . $tableRelation . '_' . $tableName . ' FOREIGN KEY ("' . $tableRelation . '_id") REFERENCES "' . $tableRelation . '" ("id") ON DELETE ' . $onDelete . ' ON UPDATE ' . $onUpdate . '
            ' );
                    
                    $this->driverHandler->getConnection()
                                        ->query( 'CREATE INDEX fk_' . $tableRelation . '_' . $tableName . ' ON "' . $tableName . '" ("' . $tableRelation . '_id");' );
                } catch( \Throwable $th ) {
                    if(strpos( $th->getMessage(), 'Duplicate') === FALSE) {
                        echo $th->getMessage() . "\n";
                    }
                }
            } elseif( $relationType === 'ManyToOne' ) {
                try {
                    echo "Try to create column " . $tableRelation . "_id for relation " . $tableRelation . " => " . $tableName . "...";
    
    
                    $this->driverHandler->getConnection()
                                        ->query( '
                ALTER TABLE "' . $tableRelation . '"
                ADD COLUMN "' . $tableName . '_id" INT NULL,
                ADD CONSTRAINT c_' . $tableName . '_' . $tableRelation . ' FOREIGN KEY ("' . $tableName . '_id") REFERENCES "' . $tableName . '" ("id") ON DELETE ' . $onDelete . ' ON UPDATE ' . $onUpdate . '
            ' );
                    
                    $this->driverHandler->getConnection()
                                        ->query( 'CREATE INDEX fk_' . $tableName . '_' . $tableRelation . ' ON "' . $tableRelation . '" ("' . $tableName . '_id")' );
                                       
                } catch( \Throwable $th ) {
                    if(strpos( $th->getMessage(), 'Duplicate') === FALSE) {
                        echo $th->getMessage() . "\n";
                    }
                }
            }
            
            
            echo "\n";
        }
    }
    
    
    private function purgeJoinTable( string $database ): void
    {
        $statement = $this->driverHandler->getConnection()->query(
            'SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = \'' . $database . '\';'
        );
        
        foreach( $statement->fetchAll() as $data ) {
            if( !in_array( $data[0], $this->joinTables ) ) {
                $query = 'DROP TABLE "' . $data[0] . '";';
                
                try {
                    echo "Try to remove table " . $data[0] . "...";
                    $this->driverHandler->getConnection()->query( $query );
                } catch( \Throwable $th ) {
                }
                
                echo "\n";
            }
        }
    }
    
    
    private function purgeForeignKey( string $tableName, array $config ): void
    {
        $statement = $this->driverHandler->getConnection()->query( 'SELECT * FROM information_schema.columns WHERE table_name = \'' . $tableName . '\';' );
        
        foreach( $statement->fetchAll() as $data ) {
            if( !in_array( $data['column_name'], $this->joinColumn ) ) {
                $query = 'ALTER TABLE "' . $tableName . '"
                DROP CONSTRAINT c_' . str_replace( '_id', '', $data['column_name'] ) . '_' . $tableName . ',
                DROP COLUMN "' . $data['column_name'] . '";';
                
                try {
                    echo "Try to remove column " . $data['column_name'] . "...";
                    $this->driverHandler->getConnection()->query( 'DROP INDEX fk_' . str_replace( '_id', '', $data['column_name'] ) . '_' . $tableName . ';' );
                    $this->driverHandler->getConnection()->query( $query );
                } catch( \Throwable $e ) {
                }
                
                echo "\n";
            }
        }
    }
}
