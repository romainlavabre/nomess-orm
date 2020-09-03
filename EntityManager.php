<?php


namespace Newwebsouth\Orm;


use Newwebsouth\Orm\Handler\DeleteHandlerInterface;
use Newwebsouth\Orm\Handler\FindHandlerInterface;
use Newwebsouth\Orm\Handler\PersistHandlerInterface;
use Newwebsouth\Orm\Handler\SaveHandlerInterface;

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
    
    
    public function find( string $classname, $idOrSql = NULL, array $parameter = NULL )
    {
        return $this->findHandler->handle( $classname, $idOrSql, $parameter );
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
