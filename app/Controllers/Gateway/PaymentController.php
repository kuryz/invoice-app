<?php
namespace App\Controllers\Gateway;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Constants\Status;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\GatewayCurrency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Plan;
use App\Models\CoinPrice;


class PaymentController extends Controller
{   
    public function deposit()
    {
        $user = $this->getUser();
        if (!$user) {
            $this->redirect('/login');
            return '';
        }
        // $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
        //     $gate->where('status', Status::ENABLE);
        // })->with('method')->orderby('method_code')->get();
        $gateways = Gateway::where('status', Status::ENABLE)->get();
        $gatewayCodes = [];
        foreach ($gateways as $gateway) {
            $gatewayCodes[] = $gateway->code;
        }
        $gatewayCurrency = GatewayCurrency::whereIn('method_code', $gatewayCodes)
            ->orderBy('name', 'ASC')
            ->get();
        $gatewayMap = [];
        foreach ($gateways as $gateway) {
            $gatewayMap[$gateway->code] = $gateway;
        }
        foreach ($gatewayCurrency as $currency) {
            $currency->method = $gatewayMap[$currency->method_code] ?? null;
        }

        $pageTitle = 'Deposit Methods';
        $general = gs();
        // print_r($gatewayCurrency);
        // die;

        return $this->render('user/payment/deposit', compact(
            'pageTitle',
            'general',
            'gatewayCurrency',
            'user'
        ));
    }

    public function depositInsert(Request $request, Response $response): void
    {
        $user = $this->getUser();

        if (!$user) {
            $this->redirect('/login');
            return;
        }

        // $validator = new \App\Services\ValidatorService();

        if (!\App\Services\ValidatorService::make($request->all(), [
            'amount'      => 'required|numeric|min:0.01',
            'method_code' => 'required',
            'currency'    => 'required',
        ])) {
            $this->session->setFlash('error', implode('<br>', $validator->errors()));
            $this->redirectBack();
            return;
        }

        $gateway = Gateway::query()
            ->where('status', Status::ENABLE)
            ->where('code', $request->input('method_code'))
            ->first();

        if (!$gateway) {
            $this->session->setFlash('error', 'Invalid gateway');
            $this->redirectBack('/user/deposit');
            return;
        }

        $gate = GatewayCurrency::query()
            ->where('method_code', $request->input('method_code'))
            ->where('currency', $request->input('currency'))
            ->first();
            // print_r($gate);
            // die;

        if (empty($gate)) {
            $this->session->setFlash('error', 'Invalid gateway');
            $this->redirectBack('/user/deposit');
            return;
        }

        $amount = (float)$request->input('amount');

        if ($amount < $gate->min_amount || $amount > $gate->max_amount) {
            $this->session->setFlash(
                'error',
                "Deposit amount must be between {$gate->min_amount} and {$gate->max_amount}"
            );
            $this->redirectBack('/user/deposit');
            return;
        }

        $deposit = $this->insertDeposit($gate, $amount, $user);

        // $_SESSION['Track'] = $deposit->trx;
        $this->session->set('Track', $deposit->trx);

        $this->redirect('/user/deposit-confirm');
    }

    public static function withdrawProcess($withdraw)
    {
        $processor = __NAMESPACE__ . '\\Coinpayments\\ProcessController';

        return $processor::withdrawProcess($withdraw);
    }

    public function insertDeposit(
        GatewayCurrency $gateway,
        float $amount,
        $investPlan = null,
        ?User $user = null
    ): Deposit {

        $user ??= $this->getUser(); // or pass from controller

        $charge = $gateway->fixed_charge +
            (($amount * $gateway->percent_charge) / 100);

        $payable = $amount + $charge;

        $finalAmount = $payable * $gateway->rate;

        $deposit = new Deposit();

        if ($investPlan) {
            $deposit->plan_id = $investPlan->id;
        }

        $deposit->user_id         = $user->id;
        $deposit->method_code     = $gateway->method_code;
        $deposit->method_currency = strtoupper($gateway->currency);
        $deposit->amount          = $amount;
        $deposit->charge          = $charge;
        $deposit->rate            = $gateway->rate;
        $deposit->final_amo       = $finalAmount;
        $deposit->btc_amo         = 0;
        $deposit->btc_wallet      = '';
        $deposit->trx             = getTrx();
        $deposit->try             = 0;
        $deposit->status          = Status::PAYMENT_INITIATE;

        $deposit->save();

        return $deposit;
    }

    public function appDepositConfirm(Request $request, Response $response, string $hash): void
    {
        try {
            $id = decrypt($hash);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo 'Sorry, invalid URL.';
            return;
        }

        $deposit = Deposit::query()
            ->where('id', $id)
            ->where('status', Status::PAYMENT_INITIATE)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$deposit) {
            http_response_code(404);
            echo 'Deposit not found.';
            return;
        }

        $user = User::find($deposit->user_id);

        if (!$user) {
            http_response_code(404);
            echo 'User not found.';
            return;
        }

        // Log user into your custom framework
        // $_SESSION['user_id'] = $user->id;
        $this->session->set('user_id', $user->id);
        // Store transaction reference
        // $_SESSION['Track'] = $deposit->trx;
        $this->session->set('Track', $deposit->trx);

        $this->redirect('/user/deposit-confirm');
    }

    public function depositConfirm(Request $request, Response $response): void
    {
        $track = $this->session->get('Track') ?? null;

        if (!$track) {
            $this->session->setFlash('error', 'Invalid deposit session');
            $this->redirect('/user/deposit');
            return;
        }

        $deposit = Deposit::query()
            ->where('trx', $track)
            ->where('status', Status::PAYMENT_INITIATE)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$deposit) {
            $this->session->setFlash('error', 'Deposit not found');
            $this->redirect('/user/deposit');
            return;
        }

        // Load gateway manually
        $gateway = GatewayCurrency::query()
            ->where('method_code', $deposit->method_code)
            ->first();

        $deposit->gateway = $gateway;

        /*
        |--------------------------------------------------------------------------
        | Manual Gateway
        |--------------------------------------------------------------------------
        */
        if ($deposit->method_code >= 1000) {
            $this->redirect('/user/deposit/manual-confirm');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Load Gateway Process Controller
        |--------------------------------------------------------------------------
        */
        $dirName = $gateway->gateway_alias;

        $class = "\\App\\Controllers\\Gateway\\{$dirName}\\ProcessController";

        if (!class_exists($class)) {
            $this->session->setFlash('error', 'Payment gateway processor not found.');
            $this->redirect('/user/deposit');
            return;
        }
        $user = $this->getUser();

        $result = $class::process($deposit, $user);

        if (is_string($result)) {
            $result = json_decode($result);
        }

        /*
        |--------------------------------------------------------------------------
        | Gateway returned error
        |--------------------------------------------------------------------------
        */
        if (isset($result->error)) {
            $this->session->setFlash('error', $result->message ?? 'Unable to process payment.');
            $this->redirect('/user/deposit');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | External Redirect
        |--------------------------------------------------------------------------
        */
        if (!empty($result->redirect)) {
            header('Location: ' . $result->redirect_url);
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Stripe Session
        |--------------------------------------------------------------------------
        */
        if (!empty($result->session)) {
            $deposit->btc_wallet = $result->session->id;
            $deposit->save();
        }

        $pageTitle = 'Payment Confirm';
        $general   = gs();

        echo $this->render($result->view, compact(
            'pageTitle',
            'deposit',
            'result',
            'general',
            'user'
        ));
    }


}
