<?php

namespace Imsidz\Payu\Actions;

use Imsidz\Payu\Events\TransactionFailed;
use Imsidz\Payu\Events\TransactionSuccessful;
use Imsidz\Payu\Models\PayuTransaction;

trait Verifier
{
    public function verify($transaction, $data)
    {
        $successful = data_get($data, 'status') === 'success';
        $transaction->update([
            'status' => $successful ? PayuTransaction::STATUS_SUCCESSFUL : PayuTransaction::STATUS_FAILED,
            'verified_at' => now(),
        ]);

        $event = $successful ? TransactionSuccessful::class : TransactionFailed::class;

        event(new $event($transaction->fresh()));
    }
}
