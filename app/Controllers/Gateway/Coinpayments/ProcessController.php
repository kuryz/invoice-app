<?php

namespace App\Controllers\Gateway\Coinpayments;

use App\Constants\Status;
use App\Models\Deposit;
use App\Controllers\Controller;
use App\Controllers\Gateway\Coinpayments\CoinPaymentHosted;
use App\Controllers\Gateway\PaymentController;
use App\Core\Request;
use App\Models\User;
use App\Repositories\MlmRepository;

class ProcessController extends Controller
{
    /*
     * CoinPaymentHosted Gateway
     */

    public static function process($deposit, $user)
    {
        // print_r($deposit->gateway->gateway_parameter);
        // die;
        $coinPayAcc = json_decode($deposit->gateway->gateway_parameter);

        if ($deposit->btc_amo == 0 || $deposit->btc_wallet == "") {
            try {
                $cps = new CoinPaymentHosted();
            } catch (\Exception $e) {
                $send['error'] = true;
                $send['message'] = $e->getMessage();
                return json_encode($send);
            }

            $cps->Setup($coinPayAcc->private_key, $coinPayAcc->public_key);
            $callbackUrl = url('/ipn/'.$deposit->gateway->alias);

            $req = array(
                'amount' => $deposit->final_amo,
                'currency1' => 'USD',
                'currency2' => $deposit->method_currency,
                'custom' => $deposit->trx,
                'buyer_email' => $user->email,
                'ipn_url' => $callbackUrl,
            );

            $result = $cps->CreateTransaction($req);
            if ($result['error'] == 'ok') {
                $bcoin = sprintf('%.08f', $result['result']['amount']);
                $sendadd = $result['result']['address'];
                $deposit->btc_amo = $bcoin;
                $deposit->btc_wallet = $sendadd;
                // print_r($deposit);die;
                
                 Deposit::where('id', $deposit->id)->update([
                    'btc_amo' => $bcoin,
                    'btc_wallet' => $sendadd
                ]);
                // $deposit->update();
            } else {
                $send['error'] = true;
                $send['message'] = $result['error'];
            }
        }

        $send['amount'] = $deposit->btc_amo;
        $send['sendto'] = $deposit->btc_wallet;
        $send['img'] = cryptoQR($deposit->btc_wallet);
        $send['currency'] = "$deposit->method_currency";
        $send['view'] = 'user/payment/crypto';
        return json_encode($send);
    }
    
    public static function withdrawProcess($withdraw)
    {
        $coinPayAcc = $withdraw->withdraw_information;

        try {
            $cps = new CoinPaymentHosted();
        } catch (\Exception $e) {
            $send['error'] = true;
            $send['message'] = $e->getMessage();
            return json_encode($send);
        }

        $cps->Setup('07811De4979c4B95ed9501EEd2Eacf3be25B1f758Ada2e2ba81731E4Fb7bf496', '219d6a041c1c771a8b7968ce828c8d4c40463e717234cd5574ac6d536f90cb3b');
        // $callbackUrl = route('ipn.'.$deposit->gateway->alias);
        $amount = $withdraw->amount - ($withdraw->amount * 0.025);
        $result = $cps->CreateWithdrawal($amount, 'USDT.BEP20', $coinPayAcc[0]->value, TRUE, '');
        // $result = '{"result":{"status":0}}';
        // print_r($result);
        // exit();/**/
        return $result;
    }

    public function ipn(Request $request)
    {

        $track = $request->custom;
        $status = $request->status;
        $amount2 = floatval($request->amount2);
        $deposit = Deposit::where('trx', $track)->orderBy('id', 'DESC')->first();

        if ($status >= 100 || $status == 2) {
            $coinPayAcc = json_decode($deposit->gatewayCurrency()->gateway_parameter);

            if (($deposit->method_currency == $request->currency2 && $deposit->btc_amo <= $amount2)  && $coinPayAcc->merchant_id == $request->merchant && $deposit->status == Status::PAYMENT_INITIATE) {
                PaymentController::userDataUpdate($deposit);
                $user = User::where('id', $deposit->user_id)->first();
                // check and move
                // Mlm::moveUserRank(@$user->ref_by, @$user->plan_ida);
            }
        }
        echo "working";
    }
}
