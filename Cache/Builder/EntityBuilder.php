<?php


namespace Nwsorm\Cache\Builder;


use Nwsorm\Annotation\AnnotationParserInterface;
use Nwsorm\Cache\CacheHandlerInterface;
use Nwsorm\Exception\ORMException;
use ReflectionProperty;

class EntityBuilder
{
    
    private AnnotationParserInterface $annotationParser;
    private RelationBuilder           $relationBuilder;
    private ReflectionProperty        $reflectionProperty;
    
    
    public function __construct(
        AnnotationParserInterface $annotationParser,
        RelationBuilder $relationBuilder )
    {
        $this->annotationParser = $annotationParser;
        $this->relationBuilder  = $relationBuilder;
    }
    
    
    public function getColumn(): string
    {
        return $this->reflectionProperty->getName();
    }
    
    
    public function getType(): string
    {
        return $this->reflectionProperty->getType()->getName();
    }
    
    
    public function getRelation(): ?array
    {
        if( !$this->relationBuilder->isRelation() ) {
            return NULL;
        }
        
        return [
            CacheHandlerInterface::ENTITY_RELATION_TYPE       => $this->relationBuilder->getType(),
            CacheHandlerInterface::ENTITY_RELATION_CLASSNAME  => $this->relationBuilder->getRelationClassname(),
            CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE => $this->relationBuilder->getJoinTable()
        ];
    }
    
    
    public function isValidProperty(): bool
    {
        if( $this->annotationParser->has( 'StateLess', $this->reflectionProperty ) ) {
            return FALSE;
        }
        
        if( !$this->reflectionProperty->hasType() ) {
            throw new ORMException( 'Please, propose type for ' .
                                    $this->reflectionProperty->getDeclaringClass()->getName() .
                                    '::$' . $this->reflectionProperty->getName() . ' or add annotation "@StateLess"' );
        }
        
        return TRUE;
    }
    
    
    public function setReflectionProperty( ReflectionProperty $reflectionProperty ): void
    {
        $this->reflectionProperty = $reflectionProperty;
        $this->relationBuilder->setReflectionProperty( $reflectionProperty );
    }
}
