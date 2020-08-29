<?php

namespace Nwsorm;

use ReflectionClass;
use ReflectionProperty;

class Store
{
    
    private static array   $toCreate    = array();
    private static array   $toUpdate    = array();
    private static array   $toDelete    = array();
    private static array   $repository  = array();
    private static array   $reflections = array();
    
    
    public static function getReflection( string $classname, string $propertyName ): ReflectionProperty
    {
        if( !array_key_exists( $classname, self::$reflections ) ) {
            self::$reflections[$classname] = array();
            
            foreach( ( new ReflectionClass( $classname ) )->getProperties() as $reflectionProperty ) {
                self::$reflections[$classname][$reflectionProperty->getName()] = $reflectionProperty;
            }
        }
        
        /** @var ReflectionProperty $reflectionProperty */
        $reflectionProperty = self::$reflections[$classname][$propertyName];
        
        if( !$reflectionProperty->isPublic() ) {
            $reflectionProperty->setAccessible( TRUE );
        }
        
        return self::$reflections[$classname][$propertyName];
    }
    
    
    public static function addToCreate( object $object ): void
    {
        $classname = get_class( $object );
        
        if( !array_key_exists( $classname, self::$toCreate ) ) {
            self::$toCreate[$classname]   = array();
            self::$toCreate[$classname][] = $object;
        } elseif( !in_array( $object, self::$toCreate[$classname], TRUE ) ) {
            self::$toCreate[$classname][] = $object;
        }
    }
    
    
    public static function addToUpdate( object $object ): void
    {
        $classname = get_class( $object );
        
        if( !array_key_exists( $classname, self::$toUpdate ) ) {
            self::$toUpdate[$classname]   = array();
            self::$toUpdate[$classname][] = $object;
        } elseif( !in_array( $object, self::$toUpdate[$classname], TRUE ) ) {
            self::$toUpdate[$classname][] = $object;
        }
    }
    
    
    public static function addToDelete( object $object ): void
    {
        $classname = get_class( $object );
        
        if( !array_key_exists( $classname, self::$toDelete ) ) {
            self::$toDelete[$classname]   = array();
            self::$toDelete[$classname][] = $object;
        } elseif( !in_array( $object, self::$toDelete[$classname], TRUE ) ) {
            self::$toDelete[$classname][] = $object;
        }
    }
    
    
    public static function getToCreate(): array
    {
        return self::$toCreate;
    }
    
    
    public static function getToUpdate(): array
    {
        return self::$toUpdate;
    }
    
    
    public static function getToDelete(): array
    {
        return self::$toDelete;
    }
    
    
    public static function toCreateHas( object $object ): bool
    {
        $classname = get_class( $object );
        
        return !array_key_exists( $classname, self::$toCreate )
               || !in_array( $object, self::$toCreate[$classname], TRUE );
    }
    
    
    public static function toUpdateHas( object $object ): bool
    {
        $classname = get_class( $object );
        
        return !array_key_exists( $classname, self::$toUpdate )
               || !in_array( $object, self::$toUpdate[$classname], TRUE );
    }
    
    
    public static function toDeleteHas( object $object ): bool
    {
        $classname = get_class( $object );
        
        return !array_key_exists( $classname, self::$toDelete )
               || !in_array( $object, self::$toDelete[$classname], TRUE );
    }
    
    
    public static function resetCreateRepository(): void
    {
        self::$toCreate = array();
    }
    
    
    public static function resetUpdateRepository(): void
    {
        self::$toUpdate = array();
    }
    
    
    public static function resetDeleteRepository(): void
    {
        self::$toDelete = array();
    }
    
    
    public static function repositoryHas( string $classname, int $id ): bool
    {
        return !array_key_exists( $classname, self::$repository )
               || !array_key_exists( $id, self::$repository[$classname] );
    }
    
    
    public static function getOfRepository( string $classname, int $id ): object
    {
        return self::$repository[$classname][$id];
    }
}
