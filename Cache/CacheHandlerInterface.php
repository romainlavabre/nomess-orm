<?php

namespace Nomess\Component\Orm\Cache;

interface CacheHandlerInterface
{
    
    public const ENTITY_METADATA            = 'entity_metadata';
    public const ENTITY_TYPE                = 'entity_type';
    public const ENTITY_COLUMN              = 'entity_column';
    public const ENTITY_IS_NULLABLE         = 'entity_is_nullable';
    public const ENTITY_RELATION            = 'entity_relation';
    public const ENTITY_RELATION_CLASSNAME  = 'entity_relation_classname';
    public const ENTITY_RELATION_TYPE       = 'entity_relation_type';
    public const ENTITY_RELATION_JOIN_TABLE = 'entity_relation_join_table';
    public const TABLE_METADATA             = 'table_metadata';
    public const TABLE_NAME                 = 'table_table_name';
    
    
    /**
     * Return an array of metadata to entity
     *
     * @param string $classname
     * @return array
     */
    public function getCache( string $classname ): array;
}
