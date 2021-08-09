<?php

namespace Imsidz\Payu\Actions;

use Illuminate\Http\Request;
use Imsidz\Payu\Checksum;
use Imsidz\Payu\Events\TransactionInvalidated;
use Imsidz\Payu\Events\TransactionSuccessful;
use Imsidz\Payu\Models\PayuTransaction;

class SuccessResponse implements Actionable
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(PayuTransaction $transaction)
    {
        $valid = Checksum::with($transaction->gateway->salt())
            ->match($this->request->all(), $this->request->input('hash'));

        $transaction->update([
            'response' => $this->request->all(),
            'status' => $valid ? PayuTransaction::STATUS_SUCCESSFUL : PayuTransaction::STATUS_INVALID,
        ]);

        $fresh = $transaction->fresh();
        $dispatch = $valid ? new TransactionSuccessful($fresh) : new TransactionInvalidated($fresh);

        event($dispatch);
    }
}
