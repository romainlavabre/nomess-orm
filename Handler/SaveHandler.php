<?php


namespace Nwsorm\Handler;


use Nwsorm\Driver\DriverHandlerInterface;
use Nwsorm\Handler\Dispatcher\DispatcherHandler;
use Nwsorm\QueryWriter\QueryCreateInterface;
use Nwsorm\QueryWriter\QueryDeleteInterface;
use Nwsorm\QueryWriter\QueryJoinInterface;
use Nwsorm\QueryWriter\QueryUpdateInterface;
use Nwsorm\Store;

class SaveHandler implements SaveHandlerInterface
{
    
    private DispatcherHandler      $dispatcher;
    private DriverHandlerInterface $driverHandler;
    private QueryCreateInterface   $queryCreate;
    private QueryUpdateInterface   $queryUpdate;
    private QueryDeleteInterface   $queryDelete;
    private QueryJoinInterface     $queryJoin;
    private array                  $toJoin = array();
    
    
    public function __construct(
        DispatcherHandler $dispatcher,
        DriverHandlerInterface $driverHandler,
        QueryCreateInterface $queryCreate,
        QueryUpdateInterface $queryUpdate,
        QueryDeleteInterface $queryDelete,
        QueryJoinInterface $queryJoin )
    {
        $this->dispatcher    = $dispatcher;
        $this->driverHandler = $driverHandler;
        $this->queryCreate   = $queryCreate;
        $this->queryUpdate   = $queryUpdate;
        $this->queryDelete   = $queryDelete;
        $this->queryJoin     = $queryJoin;
    }
    
    
    public function handle(): bool
    {
        $this->dispatcher->dispatch();
        
        $connection = $this->driverHandler->getConnection();
        
        try {
            
            $connection->beginTransaction();
            
            foreach( Store::getToDelete() as $classname => &$array ) {
                foreach( $array as &$object ) {
                    $this->queryCreate->getQuery( $object )->execute();
                }
            }
            
            
            foreach( Store::getToCreate() as $classname => &$array ) {
                foreach( $array as &$object ) {
                    $this->queryCreate->getQuery( $object )->execute();
                    
                    Store::getReflection( get_class( $object ), 'id' )->setValue( $connection->lastInsertId() );
                }
            }
            
            
            foreach( Store::getToUpdate() as $classname => &$array ) {
                foreach( $array as &$object ) {
                    $this->queryCreate->getQuery( $object )->execute();
                }
            }
            
            foreach( $this->toJoin as $object ) {
                $this->queryJoin->getQuery( $object )->execute();
            }
            
            
            $connection->commit();
            
            Store::resetDeleteRepository();
            Store::resetCreateRepository();
            Store::resetUpdateRepository();
            $this->toJoin = array();
        } catch( \Throwable $th ) {
            $connection->rollBack();
            
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    private function setToJoin( object $object ): void
    {
        if( !in_array( $object, $this->toJoin ) ) {
            $this->toJoin[] = $object;
        }
    }
}
