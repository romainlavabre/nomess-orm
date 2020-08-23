<?php

namespace Nwsorm\Cache;

interface CacheHandlerInterface
{
    
    public const ENTITY_METADATA           = 'entity_metadata';
    public const ENTITY_TYPE               = 'entity_type';
    public const ENTITY_NULLABLE           = 'entity_nullable';
    public const ENTITY_COLUMN             = 'entity_column';
    public const ENTITY_RELATION           = 'entity_relation';
    public const ENTITY_RELATION_CLASSNAME = 'entity_relation_classname';
    public const ENTITY_RELATION_TYPE      = 'entity_relation_type';
    public const TABLE_METADATA            = 'table_metadata';
    public const TABLE_NAME                = 'table_table_name';
    public const TABLE_PREFIX              = 'table_prefix';
    
    
    /**
     * Return an array of metadata to entity
     *
     * @param string $classname
     * @return array
     */
    public function getCache( string $classname ): array;
    
    
    /**
     * Return true if query is cached
     *
     * @param string $classname
     * @return bool
     */
    public function hasSelectQuery( string $classname ): bool;
    
    
    /**
     * Return query without where clause
     *
     * @param string $classname
     * @return string
     */
    public function getSelectQuery( string $classname ): string;
    
    
    /**
     * Return metadata's query
     *
     * @param string $classname
     * @return array
     */
    public function getSelectMetadataQuery( string $classname ): array;
    
    
    /**
     * Return true if query is cached
     *
     * @param string $classname
     * @return bool
     */
    public function hasCreateQuery( string $classname ): bool;
    
    
    /**
     * Return query
     *
     * @param string $classname
     * @return string
     */
    public function getCreateQuery( string $classname ): string;
    
    
    /**
     * Return metadata's query
     *
     * @param string $classname
     * @return array
     */
    public function getCreateMetadataQuery( string $classname ): array;
    
    
    /**
     * Return true if query is cached
     *
     * @param string $classname
     * @return bool
     */
    public function hasUpdateQuery( string $classname ): bool;
    
    
    /**
     * Return query
     *
     * @param string $classname
     * @return string
     */
    public function getUpdateQuery( string $classname ): string;
    
    
    /**
     * Return metadata's query
     *
     * @param string $classname
     * @return array
     */
    public function getUpdateMetadataQuery( string $classname ): array;
    
    
    /**
     * Return true if query is cached
     *
     * @param string $classname
     * @return bool
     */
    public function hasDeleteQuery( string $classname ): bool;
    
    
    /**
     * Return query
     *
     * @param string $classname
     * @return string
     */
    public function getDeleteQuery( string $classname ): string;
    
    
    /**
     * Return metadata's query
     *
     * @param string $classname
     * @return array
     */
    public function getDeleteMetadataQuery( string $classname ): array;
}
