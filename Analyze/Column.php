<?php


namespace Newwebsouth\Orm\Analyze;


use Newwebsouth\Orm\Cache\CacheHandlerInterface;
use Newwebsouth\Orm\Driver\DriverHandlerInterface;

class Column extends AbstractAnalyze
{
    
    private CacheHandlerInterface $cacheHandler;
    private array                 $type    = [
        'string' => 'VARCHAR(255)',
        'array'  => 'TEXT',
        'int'    => 'INT(11) UNSIGNED',
        'float'  => 'FLOAT(11,4) UNSIGNED',
        'bool'   => 'TINYINT(1)'
    ];
    private array                 $columns = array();
    
    
    public function __construct(
        DriverHandlerInterface $driverHandler,
        CacheHandlerInterface $cacheHandler )
    {
        parent::__construct( $driverHandler );
        $this->cacheHandler = $cacheHandler;
    }
    
    
    public function revalideColumns(): void
    {
        foreach( $this->directories() as $directory ) {
            foreach( scandir( $directory ) as $file ) {
                if( $file !== '.' && $file !== '..' && $file !== '.gitkeep'
                    && ( $reflectionClass = $this->getReflectionClass( $directory . $file, $file ) ) !== NULL
                    && $reflectionClass->isInstantiable()) {
                    $this->columns = array();
                    
                    $cache = $this->cacheHandler->getCache( $reflectionClass->getName() );
                    echo "Class " . $reflectionClass->getName() . "::class\n";
                    
                    foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
                        $this->columns[] = $array[CacheHandlerInterface::ENTITY_COLUMN];
                        
                        if( $array[CacheHandlerInterface::ENTITY_RELATION] === NULL ) {
                            $this->createColumn( $array, $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] );
                        }
                    }
                    
                    $this->purgeColumns( $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] );
                }
            }
        }
    }
    
    
    private function createColumn( array $config, string $tableName ): void
    {
        echo "Create column " . $config[CacheHandlerInterface::ENTITY_COLUMN] . "\n";
        
        $query = '
        ALTER TABLE `' . $tableName . '`
        ADD `' .
                 $config[CacheHandlerInterface::ENTITY_COLUMN] . '` ' .
                 $this->type[$config[CacheHandlerInterface::ENTITY_TYPE]] . ' ' .
                 $this->partNullable( $config[CacheHandlerInterface::ENTITY_IS_NULLABLE], $config[CacheHandlerInterface::ENTITY_TYPE] ) . '
        ;
        ';
        
        try {
            $this->driverHandler->getConnection()->query( $query )->execute();
        }catch(\Throwable $th){
        
        }
    }
    
    
    private function purgeColumns( string $tableName ): void
    {
        $statement = $this->driverHandler->getConnection()->query( 'DESCRIBE `' . $tableName . '`;' );
        $statement->execute();
        
        foreach( $statement->fetchAll() as $data ) {
            if( !in_array( $data[0], $this->columns ) && !preg_match('/.+_id/', $data[0])) {
                
                echo "Remove Column " . $data[0];
                
                $query = '
                ALTER TABLE `' . $tableName . '`
                DROP `' . $data[0] . '`;';
                $this->driverHandler->getConnection()->query( $query )->execute();
            }
        }
    }
    
    
    private function partNullable( bool $isNullable, string $type ): string
    {
        if( $isNullable ) {
            return 'NULL';
        }
        
        if( $type === 'string' || $type === 'array' ) {
            return 'NOT NULL';
        }
        
        if( $type === 'int' || $type === 'bool' ) {
            return 'NOT NULL DEFAULT 0';
        }
        
        
        return 'NOT NULL DEFAULT 0.0';
    }
}
