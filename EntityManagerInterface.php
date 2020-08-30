<?php


namespace Newwebsouth\Orm;


interface EntityManagerInterface
{
    
    /**
     * @param string $classname         The class name of target
     * @param $idOrSql                  Identifiant or where clause in sql
     * @param array|null $parameter     If parameter 2 is a where clause, you can add parameters with this:
     *                                  <code>
     *                                  [
     *                                  'param_name' => 'value'
     *                                  ]
     *                                  </code>
     * @return mixed
     */
    public function find( string $classname, $idOrSql, array $parameter = NULL );
    
    
    /**
     * @param object $object
     * @return EntityManagerInterface
     */
    public function delete( object $object ): EntityManagerInterface;
    
    
    /**
     * @param object $object
     * @return EntityManagerInterface
     */
    public function persist( object $object ): EntityManagerInterface;
    
    
    /**
     * @return bool
     */
    public function save(): bool;
}
