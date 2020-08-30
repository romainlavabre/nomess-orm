<?php
/** @noinspection PhpUndefinedConstantInspection */


namespace Newwebsouth\Orm\Cache;


use Newwebsouth\Orm\Cache\Builder\CacheBuilder;

class CacheHandler implements CacheHandlerInterface
{
    
    private const PATH_CACHE = ROOT . 'var/cache/orm/table';
    private CacheBuilder $cacheBuilder;
    
    
    public function __construct( CacheBuilder $cacheBuilder )
    {
        $this->cacheBuilder = $cacheBuilder;
    }
    
    
    /**
     * @inheritDoc
     */
    public function getCache( string $classname ): array
    {
        return $this->getArray( $classname );
    }
    
    
    private function getArray( string $classname ): array
    {
        if( !file_exists( $this->getFilename( $classname ) ) ) {
            $this->write( $classname, $this->cacheBuilder->buildCache( $classname ) );
        }
        
        return unserialize( require $this->getFilename( $classname ) );
    }
    
    
    private function getFilename( string $classname ): string
    {
        return self::PATH_CACHE . str_replace( '\\', '_', $classname ) . '.php';
    }
    
    
    private function write( string $classname, array $cache ): void
    {
        file_put_contents( $this->getFilename( $classname ) . '.php', "<?php return '" . serialize( $cache ) . "';" );
    }
}
