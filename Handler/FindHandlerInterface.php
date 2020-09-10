<?php


namespace Nomess\Component\Orm\Handler;


use Nomess\Component\Orm\Exception\ORMException;

interface FindHandlerInterface
{
    
    /**
     * @param string $classname
     * @param $idOrSql
     * @param array|null $parameter
     * @return mixed
     * @throws ORMException
     */
    public function handle( string $classname, $idOrSql, array $parameter = [], ?string $lock_type);
}
