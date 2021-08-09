<?php

namespace Imsidz\Payu\Actions;

use Illuminate\Http\Request;
use Imsidz\Payu\Events\TransactionFailed;
use Imsidz\Payu\Models\PayuTransaction;

class FailedResponse implements Actionable
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(PayuTransaction $transaction)
    {
        $transaction->update([
            'response' => $this->request->all(),
            'status' => PayuTransaction::STATUS_FAILED,
        ]);

        event(new TransactionFailed($transaction->fresh()));
    }
}
