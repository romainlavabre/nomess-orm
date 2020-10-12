<?php


namespace Nomess\Component\Orm;


use Nomess\Component\Orm\Handler\StoreHandlerInterface;

interface EntityManagerInterface
{
    
    public const LOCK_SHARE     = 'LOCK IN SHARE MODE';
    public const LOCK_EXCLUSIVE = 'FOR UPDATE';
    
    
    /**
     * @param string $classname         The class name of target
     * @param string|int|null $idOrSql  Identifiant or where clause in sql
     * @param array|null $parameter     If parameter 2 is a where clause, you can add parameters with this:
     *                                  <code>
     *                                  [
     *                                  'param_name' => 'value'
     *                                  ]
     *                                  </code>
     * @param string|null $lock_type    You can apply a lock, look the constant "LOCK_SHARE" and "LOCK_EXCLUSIVE"
     * @return mixed
     */
    public function find( string $classname, $idOrSql = NULL, array $parameter = [], string $lock_type = NULL );
    
    
    /**
     * Store the object while waiting for the save method to be called
     *
     * @param object $object
     * @return EntityManagerInterface
     */
    public function delete( object $object ): EntityManagerInterface;
    
    
    /**
     * Store the object while waiting for the save method to be called
     *
     * @param object $object
     * @return EntityManagerInterface
     */
    public function persist( object $object ): EntityManagerInterface;
    
    
    /**
     * Execute a arbitrary request
     * The data will not be followed by ORM, if you want persist your object, use method "store"
     *
     * @param string $query     SQL query
     * @param array $parameters Parameters to escape
     * @return array
     */
    public function raw( string $query, array $parameters = [] ): array;
    
    
    /**
     * If you want manipulate the store of instances, call it
     *
     * @param object $object
     * @return StoreHandlerInterface
     */
    public function getStore( object $object ): StoreHandlerInterface;
    
    
    /**
     * Launch a new transaction and persist or delete all the entities,
     * If an error occurred during the execution, a rollback is apply
     *
     * @return bool
     */
    public function save(): bool;
}
