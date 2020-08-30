<?php


namespace Nwsorm\Cache\Builder;


class TableBuilder
{
    
    private \ReflectionClass $reflectionClass;
    
    
    public function getTable(): string
    {
        return mb_strtolower( str_replace( '_', '', $this->reflectionClass->getName() ) );
    }
    
    
    public function setReflectionClass( \ReflectionClass $reflectionClass ): TableBuilder
    {
        $this->reflectionClass = $reflectionClass;
        
        return $this;
    }
}
