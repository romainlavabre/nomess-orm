<?php


namespace Nomess\Component\Orm\Cache\Builder;


use Nomess\Component\Orm\Annotation\AnnotationParserInterface;
use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\Exception\ORMException;
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
    
    public function isNullable(): bool
    {
        $instance = $this->reflectionProperty->getDeclaringClass()->newInstanceWithoutConstructor();
        
        try {
            $this->reflectionProperty->setAccessible(TRUE);
            $this->reflectionProperty->setValue($instance, NULL);
        }catch(\Throwable $th){
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    public function setReflectionProperty( ReflectionProperty $reflectionProperty ): void
    {
        $this->reflectionProperty = $reflectionProperty;
        $this->relationBuilder->setReflectionProperty( $reflectionProperty );
    }
}
