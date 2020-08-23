<?php


namespace Nwsorm\Cache\Builder;


use Nwsorm\Cache\CacheHandlerInterface;

class CacheBuilder
{
    
    private TypeBuilder $typeBuilder;
    private array       $cache = array();
    
    
    public function getCache( string $classname ): array
    {
        $reflectionProperties = ( new \ReflectionClass( $classname ) )->getProperties();
        
        foreach( $reflectionProperties as $reflectionProperty ) {
            $this->cache[$reflectionProperty->getName()][CacheHandlerInterface::ENTITY_METADATA] = [
            
            ];
        }
    }
    
    
    private function write(): void
    {
    
    }
}
