<?php


namespace Newwebsouth\Orm\Annotation;


use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;

class Parser implements AnnotationParserInterface
{
    
    /**
     * @param string $annotation
     * @param ReflectionProperty|ReflectionClass|ReflectionFunction|ReflectionMethod $reflection
     * @return bool
     */
    public function has( string $annotation, $reflection ): bool
    {
        return strpos( $this->getDocComment( $reflection ), "@$annotation" ) !== FALSE;
    }
    
    
    /**
     * @param string $annotation
     * @param ReflectionProperty|ReflectionClass|ReflectionFunction|ReflectionMethod $reflection
     * @return string|null
     */
    public function grossValue( string $annotation, $reflection ): ?string
    {
        $comments = $this->getDocComment( $reflection );
        
        if( !$this->has( "$annotation", $reflection ) ) {
            return NULL;
        }
        
        $brokenComment = explode( '*', $comments );
        
        foreach( $brokenComment as $line ) {
            if( strpos( $line, "@$annotation" ) !== FALSE ) {
                return trim( str_replace( [ "@$annotation", '[]', '|' ], '', $line ) );
            }
        }
        
        return NULL;
    }
    
    
    /**
     * @param ReflectionProperty|ReflectionClass|ReflectionFunction|ReflectionMethod $reflection
     * @return string|null
     */
    private function getDocComment( $reflection ): ?string
    {
        return $reflection->getDocComment();
    }
}
