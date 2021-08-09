<?php

namespace Imsidz\Payu\Actions;

use Imsidz\Payu\Models\PayuTransaction;

interface Actionable
{
    public function handle(PayuTransaction $transaction);
}
