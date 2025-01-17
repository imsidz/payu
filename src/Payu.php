<?php

namespace Imsidz\Payu;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;
use Imsidz\Payu\Components\Form;
use Imsidz\Payu\Concerns\Transaction;
use Imsidz\Payu\Contracts\HasFormParams;
use Imsidz\Payu\Events\TransactionInitiated;
use Imsidz\Payu\Gateway\Factory;
use Imsidz\Payu\Gateway\Gateway;
use Imsidz\Payu\Models\PayuTransaction;

class Payu implements HasFormParams
{
    protected ?string $destination = null;
    protected ?Gateway $gateway = null;
    protected ?Transaction $payment = null;

    /**
     * @param string $gateway
     * @return Payu
     * @throws Throwable
     */
    public function via(string $gateway): self
    {
        $this->gateway = Factory::make($gateway);

        return $this;
    }

    public function initiate(Transaction $payment): self
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * @param string $url
     * @return View
     * @throws Throwable
     */
    public function redirect(string $url): View
    {
        Validator::make(compact('url'), ['url' => 'required|url'])->validate();
        $url = preg_replace("/^http:/i", "https:", $url);
        $this->destination = $url;
        if (!$this->gateway) {
            $this->via($this->defaultGateway());
        }

        Form::inject($payload = $this->prepare());

        event(new TransactionInitiated($payload['transaction']));

        return view('payu::form');
    }

    /**
     * @throws ValidationException
     */
    protected function prepare()
    {
        $this->validate();
        $fields = $this->fields();
        $hash = $this->getHash();

        $transaction = PayuTransaction::query()
            ->firstOrNew([
                'transaction_id' => $this->payment->transactionId,
            ]);
        $transaction->fill(
            array_merge($this->morphFields(), [
                'gateway' => $this->gateway,
                'body' => $this->payment,
                'destination' => $this->destination,
                'hash' => $hash,
            ])
        )->save();

        Session::put('payuTransactionId', $this->payment->transactionId);

        return [
            'endpoint' => $this->gateway->endpoint(),
            'fields' => array_merge($fields, compact('hash')),
            'transaction' => $transaction,
        ];
    }

    public function capture(): PayuTransaction
    {
        return PayuTransaction::locate(Session::get('payuTransactionId'));
    }

    protected function morphFields()
    {
        if (!$this->payment->model) {
            return [];
        }

        return [
            'paid_for_id' => $this->payment->model->getKey(),
            'paid_for_type' => $this->payment->model->getMorphClass(),
        ];
    }

    protected function defaultGateway()
    {
        return config('payu.default');
    }

    public function toArray(): array
    {
        $failed = preg_replace("/^http:/i", "https:", $this->getSignedRoute('failed'));
        $successful = preg_replace("/^http:/i", "https:", $this->getSignedRoute('successful'));

        return [
            'furl' => $failed,
            'surl' => $successful,
        ];
    }

    public function fields(): array
    {
        return collect($this->toArray())
            ->merge($this->gateway->fields())
            ->merge($this->payment->fields())
            ->all();
    }

    /**
     * @throws ValidationException
     */
    public function validate(): array
    {
        $this->gateway->validate();
        $this->payment->payee->validate();
        $this->payment->params->validate();
        $this->payment->validate();

        return Validator::make($this->toArray(), [
            'surl' => 'required|url',
            'furl' => 'required|url',
        ])->validate();
    }

    public function getSignedRoute(string $urlType): string
    {
        return URL::temporarySignedRoute(
            'payu::redirect',
            now()->addMinutes(30),
            array_merge(compact('urlType'), ['transaction' => $this->payment->transactionId])
        );
    }

    protected function getHash()
    {
        return Checksum::with($this->gateway->salt())
            ->create($this->fields());
    }
}
