<?php


namespace Nomess\Component\Orm;


interface TransactionSubjectInterface
{
    public function addSubscriber(object $subscriber): void;
    
    public function notifySubscriber(bool $status): void;
    
}
