<?php

namespace Imsidz\Payu\Controllers;

use Illuminate\Http\Request;
use Imsidz\Payu\Actions\Actionable;
use Imsidz\Payu\Actions\FailedResponse;
use Imsidz\Payu\Actions\SuccessResponse;
use Imsidz\Payu\Models\PayuTransaction;

class StatusController
{
    public function __invoke(Request $request)
    {
        $this->checkValidation($request);

        /** @var PayuTransaction $payment */
        $transaction = PayuTransaction::query()->locate($request->input('transaction'));

        $this->handler($request)->handle($transaction);

        return redirect()->to($transaction->destination);
    }

    protected function handler(Request $request): Actionable
    {
        $actions = [
            PayuTransaction::STATUS_SUCCESSFUL => new SuccessResponse($request),
            PayuTransaction::STATUS_FAILED => new FailedResponse($request),
        ];

        $callable = data_get($actions, $request->input('urlType'));
        abort_unless($callable, 403);

        return $callable;
    }

    protected function checkValidation(Request $request)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $request->validate([
            'transaction' => 'required|string',
            'hash' => 'required|string',
        ]);
    }
}
