<?php
/** @noinspection PhpUndefinedConstantInspection */


namespace Nomess\Component\Orm\Cache\Builder;


use Nomess\Component\Orm\Cache\CacheHandlerInterface;

class CacheBuilder
{
    
    private EntityBuilder $entityBuilder;
    private TableBuilder  $tableBuilder;
    private array         $cache = array();
    
    
    public function __construct( EntityBuilder $entityBuilder, TableBuilder $tableBuilder )
    {
        $this->entityBuilder = $entityBuilder;
        $this->tableBuilder  = $tableBuilder;
    }
    
    
    public function buildCache( string $classname ): array
    {
        $reflectionProperties = ( $reflectionClass = new \ReflectionClass( $classname ) )->getProperties();
        
        $this->cache[CacheHandlerInterface::TABLE_METADATA] = [
            CacheHandlerInterface::TABLE_NAME => $this->tableBuilder->setReflectionClass( $reflectionClass )->getTable()
        ];
        
        foreach( $reflectionProperties as $reflectionProperty ) {
            $this->entityBuilder->setReflectionProperty( $reflectionProperty );
            
            if( $this->entityBuilder->isValidProperty() ) {
                
                $this->cache[CacheHandlerInterface::ENTITY_METADATA][$reflectionProperty->getName()] = [
                    CacheHandlerInterface::ENTITY_COLUMN   => $this->entityBuilder->getColumn(),
                    CacheHandlerInterface::ENTITY_TYPE     => $this->entityBuilder->getType(),
                    CacheHandlerInterface::ENTITY_RELATION => $this->entityBuilder->getRelation(),
                    CacheHandlerInterface::ENTITY_IS_NULLABLE => $this->entityBuilder->isNullable()
                ];
            }
        }
        
        $cache = $this->cache;
        $this->cache = array();
        return $cache;
    }
}
