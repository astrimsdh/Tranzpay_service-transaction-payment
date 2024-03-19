<?php

namespace App\Http\Controllers;

use App\Models\Topup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TopupController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->input('user_id');
        $topups = Topup::query();

        $topups->when($userId, function ($query) use ($userId) {
            return $query->where('user_id', '=', $userId);
        });

        return response()->json([
            'status' => 'success',
            'data' => $topups->get()
        ]);
    }

    public function create(Request $request)
    {
        $rules = [
            'amount' => 'required|integer',
            'user_id' => 'required|integer'
        ];

        $data = $request->all();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        $userId = $data['user_id'];
        $user = getUser($userId);

        if ($user['status'] === 'error') {
            return response()->json([
                'status' => $user['status'],
                'message' => $user['message']
            ], $user['http_code']);
        }

        $amount = $data['amount'];

        $topup = Topup::create([
            'user_id' => $userId,
            'amount' => $amount
        ]);

        $transactionDetails = [
            'order_id' => $topup->id . '-' . Str::random(5),
            'gross_amount' => $amount
        ];

        $itemDetails = [
            [
                'price' => $amount,
                'quantity' => 1,
                'name' => 'Top Up Saldo',
                'brand' => 'Tranzpay',
                'category' => 'Saldo'
            ]
        ];

        $customerDetails = [
            'first_name' => $user['data']['nama_pemilik'],
            'email' => $user['data']['email']
        ];


        $midtransParams = [
            'transaction_details' => $transactionDetails,
            'item_details' => $itemDetails,
            'customer_details' => $customerDetails
        ];

        $midtransSnapUrl = $this->getMidtransSnapUrl($midtransParams);

        $topup->snap_url = $midtransSnapUrl;
        $topup->metadata = [
            'price' => $amount,
            'user' => $user['data']['nama_pemilik']
        ];

        $topup->save();

        return response()->json([
            'status' => 'success',
            'data' => $topup
        ]);
    }

    private function getMidtransSnapUrl($params)
    {
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_PRODUCTION');
        \Midtrans\Config::$is3ds = (bool) env('MIDTRANS_3DS');

        $snapUrl = \Midtrans\Snap::createTransaction($params)->redirect_url;
        return $snapUrl;
    }
}
