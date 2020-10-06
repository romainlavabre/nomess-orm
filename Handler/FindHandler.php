<?php


namespace Nomess\Component\Orm\Handler;


use Nomess\Component\Config\ConfigStoreInterface;
use Nomess\Component\Orm\Handler\Find\FindBlock;
use Nomess\Component\Orm\Handler\Find\FindLazy;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
class FindHandler implements FindHandlerInterface
{
    
    private const CONF_NAME = 'orm';
    private FindBlock            $findBlock;
    private FindLazy             $findLazy;
    private ConfigStoreInterface $configStore;
    
    
    public function __construct(
        FindBlock $findBlock,
        FindLazy $findLazy,
        ConfigStoreInterface $configStore )
    {
        $this->findBlock   = $findBlock;
        $this->findLazy    = $findLazy;
        $this->configStore = $configStore;
    }
    
    
    /**
     * @inheritDoc
     */
    public function handle( string $classname, $idOrSql, array $parameters = [], ?string $lock_type )
    {
        if( $this->configStore->get( self::CONF_NAME )['lazyload']['enable'] ) {
            return $this->findLazy->find( $classname, $idOrSql, $parameters, $lock_type );
        }
        
        
        return $this->findBlock->find( $classname, $idOrSql, $parameters, $lock_type );
    }
}
