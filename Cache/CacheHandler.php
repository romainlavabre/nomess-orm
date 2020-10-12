<?php

namespace Nomess\Component\Orm\Cache;


use Nomess\Component\Orm\Cache\Builder\Mysql\CacheBuilder;

class CacheHandler implements CacheHandlerInterface
{
    
    private const CACHE_NAME = 'orm';
    private CacheBuilder                                  $cacheBuilder;
    private \Nomess\Component\Cache\CacheHandlerInterface $cacheHandler;
    private array                                         $cache = array();
    
    
    public function __construct(
        CacheBuilder $cacheBuilder,
        \Nomess\Component\Cache\CacheHandlerInterface $cacheHandler )
    {
        $this->cacheBuilder = $cacheBuilder;
        $this->cacheHandler = $cacheHandler;
    }
    
    
    /**
     * @inheritDoc
     */
    public function getCache( string $classname ): array
    {
        if( !array_key_exists( $classname, $this->cache ) ) {
            if( ( $cache = $this->cacheHandler->get( self::CACHE_NAME, $this->getFilename( $classname ) ) ) === NULL ) {
                $cache = $this->cacheBuilder->buildCache( $classname );
                $this->cacheHandler->add( self::CACHE_NAME, [
                    'value'    => $cache,
                    'filename' => $this->getFilename( $classname )
                ] );
            }
            
            return $this->cache[$classname] = $cache;
        }
        
        return $this->cache[$classname];
    }
    
    
    private function getFilename( string $classname ): string
    {
        return str_replace( '\\', '_', $classname );
    }
}
