<?php


namespace Nomess\Component\Orm\Cache\Builder\PostgreSql;


class TableBuilder
{
    
    private \ReflectionClass $reflectionClass;
    
    
    public function getTable(): string
    {
        return mb_strtolower( str_replace( '_', '', $this->reflectionClass->getShortName() ) );
    }
    
    
    public function setReflectionClass( \ReflectionClass $reflectionClass ): TableBuilder
    {
        $this->reflectionClass = $reflectionClass;
        
        return $this;
    }
}
