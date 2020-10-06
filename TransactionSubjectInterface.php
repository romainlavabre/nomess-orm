<?php


namespace Nomess\Component\Orm;


interface TransactionSubjectInterface
{
    
    /**
     * Add a observer to notify when transaction have success or crash
     *
     * @param object $subscriber
     */
    public function addSubscriber( object $subscriber ): void;
    
    
    /**
     * Send notification
     *
     * @param bool $status
     */
    public function notifySubscriber( bool $status ): void;
}
