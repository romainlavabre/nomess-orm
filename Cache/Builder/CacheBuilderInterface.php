<?php


namespace Nomess\Component\Orm\Cache\Builder;


/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
interface CacheBuilderInterface
{
    
    public function buildCache( string $classname ): array;
}
