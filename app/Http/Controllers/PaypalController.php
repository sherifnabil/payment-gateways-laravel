<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Payment;
use PayPal\Rest\ApiContext;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use Illuminate\Http\Request;
use PayPal\Api\PaymentExecution;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;

class PaypalController extends Controller
{
    private function apiContext(): ApiContext
    {
        return new ApiContext(
            new OAuthTokenCredential(
                env('PAYPAL_CLIENT_ID'),
                env('PAYPAL_CLIENT_SECRET')
            )
        );

        // Going Live
        // ->setConfig(
        //     [
        //         'mode' => 'live',
        //     ]
        // );
        // Get Live Credentials from Dashboard
    }

    public function index(Request $request)
    {
        $apiContext = $this->apiContext();

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $amount = new Amount();
        $amount->setTotal($request->amount);
        $amount->setCurrency('USD');

        $transaction = new Transaction();
        $transaction->setAmount($amount);

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(route('paypal.return'))
            ->setCancelUrl(route('paypal.cancel'));

        $payment = new Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setTransactions(array($transaction))
            ->setRedirectUrls($redirectUrls);

        try {
            $payment->create($apiContext);
            echo $payment;
            return redirect($payment->getApprovalLink());
        } catch (PayPalConnectionException $ex) {
            dd($ex->getData());
        }
    }

    public function paypalReturn(Request $request): Payment
    {
        $apiContext = $this->apiContext();

        try {
            $payment = Payment::get($request->paymentId, $apiContext);
        } catch (\Exception $ex) {
            dd($ex);
            exit(1);
        }

        // Execute payment with payer ID
        $execution = new PaymentExecution();
        $execution->setPayerId($request->PayerID);

        try {
            $result = $payment->execute($execution, $apiContext);
        } catch (PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            die($ex);
        }
        // Do your stuff with the payer and payment store it in DB or whatever
        // dd($result->state == 'approved'); // if state is approved so the payment is successed otherwise it's not
        return $result;
    }

    public function paypalCancel()
    {
        dd('canceled');
    }
}
