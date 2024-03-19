<?php

namespace App\Http\Controllers;

use App\Models\PaymentLog;
use App\Models\Topup;
use App\Models\TransactionLog;
use App\Models\TransactionModel;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function midtransHandler(Request $request)
    {
        $data = $request->all();

        $signatureKey = $data['signature_key'];

        $orderId = $data['order_id'];
        $statusCode = $data['status_code'];
        $grossAmount = $data['gross_amount'];
        $serverKey = env('MIDTRANS_SERVER_KEY');

        $mySignatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $transactionStatus = $data['transaction_status'];
        $type = $data['payment_type'];
        $fraudStatus = $data['fraud_status'];

        if ($signatureKey !== $mySignatureKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'signature salah!'
            ], 400);
        }

        $realOrderId = explode('-', $orderId);
        $order = Topup::find($realOrderId[0]);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'order id not found'
            ], 404);
        }

        if ($order->status == 'success') {
            return response()->json([
                'status' => 'error',
                'message' => 'Operasi tidak diizinkan!'
            ], 405);
        }

        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challange') {
                $order->status = 'challange';
            } else if ($fraudStatus == 'accept') {
                $order->status = 'success';
            }
        } elseif ($transactionStatus == 'settlement') {
            $order->status = 'success';
        } else if ($transactionStatus == 'cancle' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $order->status = 'failure';
        } else if ($transactionStatus == 'pending') {
            $order->status = 'pending';
        }

        $logData = [
            'status' => $transactionStatus,
            'raw_response' => json_encode($data),
            'topup_id' => $realOrderId[0],
            'payment_type' => $type,
        ];

        PaymentLog::create($logData);
        $order->save();

        if ($order->status == 'success') {
            updateSaldo([
                'user_id' => $order->user_id,
                'amount' => $order->amount
            ]);
        }

        return response()->json([
            'status' => 'success'
        ]);
    }

    public function digiflazHandler(Request $request)
    {
        $secret = 't3uingatuh';


        $post_data = file_get_contents('php://input');
        $signature = hash_hmac('sha1', $post_data, $secret);
        if ($request->header('X-Hub-Signature') == 'sha1=' . $signature) {
            $data = json_decode($request->getContent(), true);
            $transaction = TransactionModel::getTransactionByRefID($data['data']['ref_id'])->first();
            $user = getUser($transaction['transaction_user_id']);
            if ($data['data']['status'] == 'Gagal') {
                updateSaldo([
                    'user_id' => $transaction['transaction_user_id'],
                    'amount' => $user['data']['saldo'] + $transaction['transaction_total']
                ]);
                $transaction->transaction_status = $data['data']['status'];
                $transaction->transaction_message = $data['data']['message'];
                $transaction->save();
            } else {
                $transaction->transaction_status = $data['data']['status'];
                $transaction->transaction_message = $data['data']['message'];
                $transaction->save();
            }
            TransactionLog::create([
                'status' => $data['data']['status'],
                'buyer_sku_code' => $data['data']['buyer_sku_code'],
                'ref_id' => $data['data']['ref_id'],
                'raw_response' => json_encode($data['data']),
            ]);
            return response()->json($data);
        }
    }
}
