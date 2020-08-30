<?php


namespace Newwebsouth\Orm\Handler;


use Newwebsouth\Orm\Exception\ORMException;

interface FindHandlerInterface
{
    
    /**
     * @param string $classname
     * @param $idOrSql
     * @param array|null $parameter
     * @return mixed
     * @throws ORMException
     */
    public function handle( string $classname, $idOrSql, array $parameter = NULL );
}
