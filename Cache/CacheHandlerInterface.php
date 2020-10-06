<?php

namespace Nomess\Component\Orm\Cache;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
interface CacheHandlerInterface
{
    
    public const ENTITY_METADATA            = 'entity_metadata';
    public const ENTITY_TYPE                = 'entity_type';
    public const ENTITY_COLUMN_NAME         = 'entity_column_name';
    public const ENTITY_COLUMN_TYPE         = 'entity_column_type';
    public const ENTITY_COLUMN_LENGTH       = 'entity_column_length';
    public const ENTITY_COLUMN_OPTIONS      = 'entity_column_options';
    public const ENTITY_COLUMN_INDEX        = 'entity_column_index';
    public const ENTITY_IS_NULLABLE         = 'entity_is_nullable';
    public const ENTITY_RELATION            = 'entity_relation';
    public const ENTITY_RELATION_CLASSNAME  = 'entity_relation_classname';
    public const ENTITY_RELATION_TYPE       = 'entity_relation_type';
    public const ENTITY_RELATION_JOIN_TABLE = 'entity_relation_join_table';
    public const ENTITY_RELATION_INVERSED   = 'entity_relation_inversed';
    public const ENTITY_RELATION_OWNER      = 'entity_relation_owner';
    public const ENTITY_RELATION_ON_UPDATE  = 'entity_relation_on_update';
    public const ENTITY_RELATION_ON_DELETE  = 'entity_relation_on_delete';
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
