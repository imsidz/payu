<?php

namespace Imsidz\Payu\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Imsidz\Payu\Gateway\Gateway;
use Imsidz\Payu\Gateway\PayuMoney;
use Imsidz\Payu\Models\PayuTransaction;

class VerifyPayuMoney implements Actionable
{
    use Verifier;

    protected PayuMoney $gateway;
    protected string $transactionId;
    protected array $partMap = [
        Gateway::TEST_MODE => 'sandbox/',
        Gateway::LIVE_MODE => '',
    ];

    public function handle(PayuTransaction $transaction)
    {
        if (!$transaction->shouldVerify()) {
            return false;
        }

        $this->initialize($transaction);

        $response = Http::withHeaders(['Authorization' => $this->gateway->auth()])->post($this->url());
        $data = collect(data_get($response->json(), 'result', []))->first();

        $this->verify($transaction, $data);

        return true;
    }

    protected function initialize(PayuTransaction $transaction)
    {
        $this->gateway = $transaction->gateway;
        $this->transactionId = data_get($transaction, 'transaction_id');
    }

    protected function url(): string
    {
        $part = data_get($this->partMap, $this->gateway->mode);
        throw_unless($part, ValidationException::withMessages([
            'mode' => __('Invalid mode supplied for PayuBiz'),
        ]));

        return sprintf('https://www.payumoney.com/%spayment/op/getPaymentResponse?%s', $part, $this->getQuery());
    }

    protected function getQuery(): string
    {
        return http_build_query([
            'merchantKey' => $this->gateway->key,
            'merchantTransactionIds' => $this->transactionId,
        ]);
    }
}
