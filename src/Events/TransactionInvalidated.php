<?php

namespace Imsidz\Payu\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Imsidz\Payu\Models\PayuTransaction;

class TransactionInvalidated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public PayuTransaction $transaction;

    public function __construct(PayuTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
