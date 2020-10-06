<?php


namespace Nomess\Component\Orm\Handler\Find;


use Nomess\Component\Config\ConfigStoreInterface;
use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\QueryWriter\QueryFreeInterface;
use Nomess\Component\Orm\QueryWriter\QueryJoinRelationInterface;
use Nomess\Component\Orm\Store;

/**
 * @author Romain Lavabre
 */
class FindRelation extends AbstractFind
{
    
    private const CONFIG_NAME = 'orm';
    private array                 $config;
    
    
    public function __construct(
        CacheHandlerInterface $cacheHandler,
        QueryFreeInterface $queryFree,
        ConfigStoreInterface $configStore,
        QueryJoinRelationInterface $queryJoinRelation )
    {
        parent::__construct( $cacheHandler, $queryFree, $queryJoinRelation );
        $this->config = $configStore->get( self::CONFIG_NAME )['lazyload'];
    }
    
    
    public function load( object $object, string $propertyName ): void
    {
        if( !$this->config['enable'] ) {
            return;
        }
        
        $statement = $this->queryJoinRelation->getQuery( $propertyName, $object );
        $statement->execute();
        
        $relationClassname = $this->cacheHandler->getCache( get_class( $object ) )[CacheHandlerInterface::ENTITY_METADATA][$propertyName][CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME];
        
        $result = [];
        
        foreach( $statement->fetchAll( \PDO::FETCH_ASSOC ) as $data ) {
            if( Store::repositoryHas( $relationClassname, $data['id'] ) ) {
                $result[] = Store::getOfRepository( $relationClassname, $data['id'] );
                continue;
            }
            
            $this->setObject( $relation = new $relationClassname(), $data );
            
            $this->setRelations( $relation );
            $result[] = $relation;
            Store::addToRepository( $relation );
        }
        
        $this->setProperty( $object, $propertyName, $result );
    }
    
    
    private function setProperty( object $object, string $propertyName, array $data ): void
    {
        $reflectionProperty = Store::getReflection( get_class( $object ), $propertyName );
        
        if( $reflectionProperty->getType()->getName() === 'array' ) {
            $value = [];
            if( $reflectionProperty->isInitialized( $object ) ) {
                $value = $reflectionProperty->getValue( $object );
                
                if( !is_array( $value ) ) {
                    $value = [];
                }
            }
            
            $reflectionProperty->setValue( $object, array_merge( $value, $data ) );
            
            return;
        }
        
        if( !empty( $data ) ) {
            $reflectionProperty->setValue( $object, $data[0] );
            
            return;
        }
        
        $reflectionProperty->setValue( $object, NULL );
    }
    
    
    protected function isLazyLoaded( string $classname ): bool
    {
        return $this->config['enable']
               && ( !is_array( $this->config['exclude'] )
                    || array_search( $classname, $this->config['exclude'] ) === FALSE );
    }
}
