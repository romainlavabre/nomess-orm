<?php


namespace Nomess\Component\Orm;


interface TransactionObserverInterface
{
    
    /**
     * Receive the status of transaction
     *
     * @param bool $status
     */
    public function statusTransactionNotified( bool $status ): void;
    
    
    /**
     * Send request for be notified when transaction have success or crash
     *
     * @param TransactionSubjectInterface $transactionSubject
     */
    public function subscribeToTransactionStatus( TransactionSubjectInterface $transactionSubject ): void;
}
