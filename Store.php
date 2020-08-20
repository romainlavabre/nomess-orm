<?php

namespace Nwsorm;

use ReflectionClass;
use ReflectionProperty;

class Store
{
    
    public static array $toCreate    = array();
    public static array $toUpdate    = array();
    public static array $toDelete    = array();
    public static array $repository  = array();
    public static array $reflections = array();
    
    
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
    
    
    public static function addToUpdate( object $object ): void
    {
        $classname = get_class( $object );
        
        if( !array_key_exists( get_class( $object ), self::$toUpdate ) ) {
            self::$toUpdate[$classname]   = array();
            self::$toUpdate[$classname][] = $object;
        } elseif( !in_array( $object, self::$toUpdate[$classname] ) ) {
            self::$toUpdate[$classname][] = $object;
        }
    }
    
    
    public static function addToCreate( object $object ): void
    {
        $classname = get_class( $object );
        
        if( !array_key_exists( get_class( $object ), self::$toCreate ) ) {
            self::$toCreate[$classname]   = array();
            self::$toCreate[$classname][] = $object;
        } elseif( !in_array( $object, self::$toCreate[$classname] ) ) {
            self::$toCreate[$classname][] = $object;
        }
    }
}
