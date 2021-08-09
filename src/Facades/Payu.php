<?php

namespace Imsidz\Payu\Facades;

use Illuminate\Support\Facades\Facade;
use Imsidz\Payu\Concerns\Transaction;
use Imsidz\Payu\Models\PayuTransaction;

/**
 * @see \Imsidz\Payu\Payu
 * @method static \Imsidz\Payu\Payu initiate(Transaction $payment)
 * @method static PayuTransaction capture()
 */
class Payu extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'payu';
    }
}
