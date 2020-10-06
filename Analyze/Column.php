<?php


namespace Nomess\Component\Orm\Analyze;


use Nomess\Component\Config\ConfigStoreInterface;
use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\Driver\DriverHandlerInterface;

class Column extends AbstractAnalyze
{
    
    private CacheHandlerInterface $cacheHandler;
    private array                 $columns = array();
    private const NOT_SUPPORTED_NULL = [
        'TINYINT',
        'SMALLINT',
        'MEDIUMINT',
        'INT',
        'BIGINT',
        'DECIMAL',
        'FLOAT',
        'DOUBLE',
        'DATE',
        'DATETIME',
        'TIMESTAMP',
        'TIME',
        'YEARS',
        'JSON'
    ];
    private const PREFIX_INDEX       = [
        'PRIMARY'  => 'pri',
        'UNIQUE'   => 'uni',
        'INDEX'    => 'mul',
        'FULLTEXT' => 'mul',
        'SPATIAL'  => 'mul'
    ];
    
    
    public function __construct(
        DriverHandlerInterface $driverHandler,
        CacheHandlerInterface $cacheHandler,
        ConfigStoreInterface $configStore )
    {
        parent::__construct( $driverHandler, $configStore );
        $this->cacheHandler = $cacheHandler;
    }
    
    
    public function revalideColumns(): void
    {
        foreach( $this->directories() as $directory ) {
            foreach( scandir( $directory ) as $file ) {
                
                if( $file !== '.' && $file !== '..' && $file !== '.gitkeep'
                    && ( $reflectionClass = $this->getReflectionClass( $directory . $file, $file ) ) !== NULL
                    && $reflectionClass->isInstantiable() ) {
                    $this->columns = array();
                    
                    $cache = $this->cacheHandler->getCache( $reflectionClass->getName() );
                    echo "Class " . $reflectionClass->getName() . "::class\n";
                    
                    foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
                        $this->columns[] = $array[CacheHandlerInterface::ENTITY_COLUMN_NAME];
                        
                        if( $array[CacheHandlerInterface::ENTITY_RELATION] === NULL ) {
                            $this->createColumn( $array, $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] );
                        }
                    }
                    
                    $this->purgeColumns( $array, $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] );
                }
            }
        }
    }
    
    
    private function createColumn( array $config, string $tableName ): void
    {
        echo "Create column " . $config[CacheHandlerInterface::ENTITY_COLUMN_NAME] . "\n";
        
        if( $this->mustBePurged( $config[CacheHandlerInterface::ENTITY_IS_NULLABLE], $config[CacheHandlerInterface::ENTITY_COLUMN_TYPE], $config, $tableName ) ) {
            
            try {
                $statement = $this->driverHandler->getConnection()->query( 'DESCRIBE `' . $tableName . '`;' );
                $statement->execute();
                
                $query = 'ALTER TABLE `' . $tableName . '` ' . "\n";
                
                $query .= 'DROP COLUMN `' . $config[CacheHandlerInterface::ENTITY_COLUMN_NAME] . '`;';
                
                $statement = $this->driverHandler->getConnection()->query( $query );
                $statement->fetchAll();
            } catch( \Throwable $throwable ) {
            }
        }
        
        
        $query = '
        ALTER TABLE `' . $tableName . '`
        ADD `' .
                 $config[CacheHandlerInterface::ENTITY_COLUMN_NAME] . '` ' .
                 $config[CacheHandlerInterface::ENTITY_COLUMN_TYPE] .
                 ( ( ( $length = $config[CacheHandlerInterface::ENTITY_COLUMN_LENGTH] ) !== NULL ) ? '(' . $length . ')' : NULL ) .
                 ' ' . $config[CacheHandlerInterface::ENTITY_COLUMN_OPTIONS] . ' ' .
                 ( $config[CacheHandlerInterface::ENTITY_IS_NULLABLE] ? 'NULL' : 'NOT NULL' ) . ';
        ';
        
        
        try {
            
            $statement = $this->driverHandler->getConnection()->query( $query );
            $statement->fetchAll();
        } catch( \Throwable $th ) {
            if( strpos( $th->getMessage(), 'Duplicate' ) === FALSE ) {
                echo $th->getMessage() . "\n";
            }
        }
        
        if( $config[CacheHandlerInterface::ENTITY_COLUMN_INDEX] !== NULL ) {
            try {
                $query = 'ALTER TABLE `' . $tableName . '` ADD ' . $config[CacheHandlerInterface::ENTITY_COLUMN_INDEX] . ' `index_' . self::PREFIX_INDEX[$config[CacheHandlerInterface::ENTITY_COLUMN_INDEX]] . '_' . $tableName . '_' . $config[CacheHandlerInterface::ENTITY_COLUMN_NAME] . '` (`' . $config[CacheHandlerInterface::ENTITY_COLUMN_NAME] . '`)';
                
                $statement = $this->driverHandler->getConnection()->query( $query );
                $statement->fetchAll();
            } catch( \Throwable $throwable ) {
            }
        }
    }
    
    
    private function purgeColumns( array $config, string $tableName ): void
    {
        $statement = $this->driverHandler->getConnection()->query( 'DESCRIBE `' . $tableName . '`;' );
        $statement->execute();
        
        foreach( $statement->fetchAll() as $data ) {
            if( !in_array( $data[0], $this->columns ) && !preg_match( '/.+_id/', $data[0] ) ) {
                
                echo "Remove Column " . $data[0];
                
                $query = '
                ALTER TABLE `' . $tableName . '`';
                
                if( $config[CacheHandlerInterface::ENTITY_COLUMN_INDEX] !== NULL ) {
                    $query .= 'DROP INDEX `index_' . self::PREFIX_INDEX[$config[CacheHandlerInterface::ENTITY_COLUMN_INDEX]] . '_' . $tableName . '_' . $config[CacheHandlerInterface::ENTITY_COLUMN_NAME] . '` (`' . $config[CacheHandlerInterface::ENTITY_COLUMN_NAME] . '`)';
                }
                
                $query .= 'DROP COLUMN `' . $data[0] . '`;';
                
                try {
                    $this->driverHandler->getConnection()->query( $query )->execute();
                } catch( \Throwable $throwable ) {
                    echo $throwable->getMessage() . "\n";
                }
            }
        }
    }
    
    
    private function mustBePurged( bool $isNullable, string $type, array $config, string $tableName ): bool
    {
        if( $config[CacheHandlerInterface::ENTITY_COLUMN_NAME] === 'id' ) {
            return FALSE;
        }
        
        
        $statment = $this->driverHandler->getConnection()->query( 'DESCRIBE `' . $tableName . '`;' );
        $statment->execute();
        
        $truncate = TRUE;
        
        foreach( $statment->fetchAll() as $item ) {
            if( $item['Field'] === $config[CacheHandlerInterface::ENTITY_COLUMN_NAME] ) {
                if( strpos( $item['Type'], mb_strtolower( $config[CacheHandlerInterface::ENTITY_COLUMN_TYPE] ) ) === FALSE ) {
                    break;
                }
                
                if( is_array( $config[CacheHandlerInterface::ENTITY_COLUMN_OPTIONS] ) ) {
                    foreach( $config[CacheHandlerInterface::ENTITY_COLUMN_OPTIONS] as $option ) {
                        if( strpos( $item['Type'], mb_strtolower( $option ) ) === FALSE ) {
                            break;
                        }
                    }
                }
                
                if( preg_match( '/\([0-9]\.,\)/', $item['Type'] ) ) {
                    if( !empty( $config[CacheHandlerInterface::ENTITY_COLUMN_LENGTH] )
                        && strpos( $config[CacheHandlerInterface::ENTITY_COLUMN_LENGTH], $item['Type'] ) === FALSE ) {
                        break;
                    }
                }
                
                if( ( $item['Null'] === 'YES' && !$config[CacheHandlerInterface::ENTITY_IS_NULLABLE] )
                    || ( $item['Null'] === 'NO' && $config[CacheHandlerInterface::ENTITY_IS_NULLABLE] ) ) {
                    break;
                }
                
                $columnIndex = $config[CacheHandlerInterface::ENTITY_COLUMN_INDEX];
                if( !empty( $item['Key'] ) && $columnIndex === NULL
                    || empty( $item['Key'] ) && $columnIndex !== NULL
                    || ( !empty( $item['Key'] ) ? mb_strtolower( $item['Key'] ) : NULL ) !==
                       ( empty( self::PREFIX_INDEX ) ? self::PREFIX_INDEX[$columnIndex] : NULL ) ) {
                    
                    break;
                }
                
                $truncate = FALSE;
                break;
            }
        }
        
        
        return $truncate;
    }
}
