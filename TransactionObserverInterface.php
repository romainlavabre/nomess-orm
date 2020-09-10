<?php


namespace Nomess\Component\Orm;


interface TransactionObserverInterface
{
    public function statusTransactionNotified(bool $status): void;
    
    public function subscribeToTransactionStatus(TransactionSubjectInterface $transactionSubject): void;
}
