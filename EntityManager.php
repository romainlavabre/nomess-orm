<?php


namespace Nomess\Component\Orm;


use Nomess\Component\Orm\Handler\DeleteHandlerInterface;
use Nomess\Component\Orm\Handler\FindHandlerInterface;
use Nomess\Component\Orm\Handler\PersistHandlerInterface;
use Nomess\Component\Orm\Handler\SaveHandlerInterface;

class EntityManager implements EntityManagerInterface
{
    
    private FindHandlerInterface    $findHandler;
    private PersistHandlerInterface $persistHandler;
    private DeleteHandlerInterface  $deleteHandler;
    private SaveHandlerInterface    $saveHandler;
    
    
    public function __construct(
        FindHandlerInterface $findHandler,
        PersistHandlerInterface $persistHandler,
        DeleteHandlerInterface $deleteHandler,
        SaveHandlerInterface $saveHandler )
    {
        $this->findHandler    = $findHandler;
        $this->persistHandler = $persistHandler;
        $this->deleteHandler  = $deleteHandler;
        $this->saveHandler    = $saveHandler;
    }
    
    
    public function find( string $classname, $idOrSql = NULL, array $parameter = [], string $lock_type = NULL)
    {
        return $this->findHandler->handle( $classname, $idOrSql, $parameter, $lock_type );
    }
    
    
    public function persist( object $object ): EntityManagerInterface
    {
        $this->persistHandler->handle( $object );
        
        return $this;
    }
    
    
    public function delete( object $object ): EntityManagerInterface
    {
        $this->deleteHandler->handle( $object );
        
        return $this;
    }
    
    
    public function save(): bool
    {
        return $this->saveHandler->handle();
    }
}
