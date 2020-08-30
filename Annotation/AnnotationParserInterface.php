<?php


namespace Nwsorm\Annotation;


use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;

interface AnnotationParserInterface
{
    
    /**
     * @param string $annotation
     * @param ReflectionProperty|ReflectionClass|ReflectionFunction|ReflectionMethod $reflection
     * @return bool
     */
    public function has( string $annotation, $reflection ): bool;
    
    
    /**
     * @param string $annotation
     * @param ReflectionProperty|ReflectionClass|ReflectionFunction|ReflectionMethod $reflection
     * @return string|null
     */
    public function grossValue( string $annotation, $reflection ): ?string;
}
