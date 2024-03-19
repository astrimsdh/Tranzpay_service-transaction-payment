<?php

namespace App\Http\Controllers;

use App\Models\TransactionModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Traits\CodeGenerate;
use Illuminate\Support\Facades\Validator;

class DigiflazController extends Controller
{
    use CodeGenerate;
    protected $header = null;
    protected $url = null;
    protected $user = null;
    protected $key = null;
    protected $model_transaction = null;

    public function __construct()
    {
        $this->header = array(
            'Content-Type:application/json'
        );

        $this->url = env('DIGIFLAZ_URL');
        $this->user = env('DIGIFLAZ_USERNAME');
        $this->key = env('DIGIFLAZ_MODE') == 'development' ? env('DIGIFLAZ_DEV_KEY') : env('DIGIFLAZ_PROD_KEY');

        $this->model_transaction = new TransactionModel();
    }

    public function get_product_prepaid()
    {


        $response = Http::withHeaders($this->header)->post(
            $this->url . '/price-list',
            [
                "cmd" => "prepaid",
                "username" => $this->user,
                "sign" => md5($this->user . $this->key . "pricelist")
            ]
        );

        $data = insert_product_prepaid(json_decode($response->getBody(), true));

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function get_product_pasca()
    {

        $response = Http::withHeaders($this->header)->post(
            $this->url . '/price-list',
            [
                "cmd" => "pasca",
                "username" => $this->user,
                "sign" => md5($this->user . $this->key . "pricelist")
            ]
        );

        $data = insert_product_pasca(json_decode($response->getBody(), true));

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function digiflazTopup(Request $request)
    {
        $rules = [
            'user_id' => 'required|integer',
            'sku' => 'required|string',
            'customer_no' => 'required|string'
        ];

        $data = $request->all();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        $sku = $request->sku;

        $product = getProductPrepaidBySKU($sku);
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'SKU tidak di temukan atau Non-Aktif!'
            ]);
        }

        $userId = $request->input('user_id');
        $user = getUser($userId);
        if ($user['status'] === 'error') {
            return response()->json([
                'status' => $user['status'],
                'message' => $user['message']
            ], $user['http_code']);
        }

        if ($user['data']['role'] == 'member') {
            if ($user['data']['saldo'] < $product['data']['product_buyer_price']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Saldo tidak cukup'
                ], 500);
            }
        }

        $ref_id = $this->getCode();

        $response = Http::withHeaders($this->header)->post(
            $this->url . '/transaction',
            [
                "username" => $this->user,
                "buyer_sku_code" => $request->sku,
                "customer_no" => $request->customer_no,
                "ref_id" => $ref_id,
                "sign" => md5($this->user . $this->key . $ref_id)
            ]
        );

        $data = json_decode($response->getBody(), true);
        $transaction = $this->model_transaction->insert_transaction_data($data['data'], 'Prepaid', $user['data'], $product['data']);

        if ($user['data']['role'] == 'member') {
            if ($data['data']['status'] == 'Sukses' || $data['data']['status'] == 'Pending') {
                $saldo = ($user['data']['saldo'] - $product['data']['product_buyer_price']);
                updateSaldo([
                    'user_id' => $user['data']['id'],
                    'amount' => $saldo
                ]);
            }
        }

        return response()->json(['status' => 'success', 'data' => $transaction]);
    }

    public function digiflazCekTagihan(Request $request)
    {
        $rules = [
            'user_id' => 'required|integer',
            'sku' => 'required|string',
            'customer_no' => 'required|string'
        ];

        $data = $request->all();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        $sku = $request->sku;
        $product = getProductPascaBySKU($sku);
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'SKU tidak di temukan atau Non-Aktif!'
            ]);
        }

        $userId = $request->input('user_id');
        $user = getUser($userId);
        if ($user['status'] === 'error') {
            return response()->json([
                'status' => $user['status'],
                'message' => $user['message']
            ], $user['http_code']);
        }

        $ref_id = $this->getCode();

        $response = Http::withHeaders($this->header)->post(
            $this->url . '/transaction',
            [
                "commands" => "inq-pasca",
                "username" => $this->user,
                "buyer_sku_code" => $sku,
                "customer_no" => $request->customer_no,
                "ref_id" => $ref_id,
                "sign" => md5($this->user . $this->key . $ref_id)
            ]
        );

        $data = json_decode($response->getBody(), true);
        $data['data']['status'] = 'pending';
        $transaksi = $this->model_transaction->insert_transaction_data($data['data'], 'Pasca', $user['data'], $product['data']);
        return response()->json(['status' => 'success', 'data' => $transaksi]);
    }

    public function digiflazBayarTagihan(Request $request)
    {
        $rules = [
            'user_id' => 'required|integer',
            'ref_id' => 'required|string',
        ];

        $data = $request->all();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        $ref_id = $request->ref_id;

        $transaction = TransactionModel::getTransactionByRefID($ref_id)->first();
        if (!$transaction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaksi dengan ref id tersebut tidak ditemukkan!'
            ]);
        }
        if ($transaction['transaction_status'] == 'Sukses') {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaksi berhasil dibayar sebelumnya!'
            ], 405);
        }

        $userId = $request->input('user_id');
        $user = getUser($userId);
        if ($user['status'] === 'error') {
            return response()->json([
                'status' => $user['status'],
                'message' => $user['message']
            ], $user['http_code']);
        }

        if ($user['data']['role'] == 'member') {
            if ($user['data']['saldo'] < $transaction['transaction_total']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Saldo tidak cukup'
                ], 500);
            }
        }

        $response = Http::withHeaders($this->header)->post(
            $this->url . '/transaction',
            [
                "commands" => "pay-pasca",
                "username" => $this->user,
                "buyer_sku_code" => $transaction['transaction_sku'],
                "customer_no" => $transaction['transaction_number'],
                "ref_id" => $ref_id,
                "sign" => md5($this->user . $this->key . $ref_id)
            ]
        );

        $data = json_decode($response->getBody(), true);
        $transaction['transaction_status'] = $data['data']['status'];
        $transaction->save();
        if ($user['data']['role'] == 'member') {
            if ($data['data']['status'] == 'Sukses') {
                $saldo = ($user['data']['saldo'] - $data['data']['selling_price']);
                updateSaldo([
                    'user_id' => $user['data']['id'],
                    'amount' => $saldo
                ]);
            }
        }
        return response()->json(['status' => 'success', 'data' => $transaction]);
    }

    public function cekIDPLN(Request $request)
    {
        $rules = [
            'customer_no' => 'required|string'
        ];

        $data = $request->all();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        $response = Http::withHeaders($this->header)->post(
            $this->url . '/transaction',
            [
                "commands" => "pln-subscribe",
                "customer_no" => $request->customer_no,
            ]
        );
        $data = json_decode($response->getBody(), true);
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function cekSaldoUser(Request $request)
    {
        $rules = [
            'user_id' => 'required|integer',
        ];

        $data = $request->all();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        $userId = $request->input('user_id');
        $user = getUser($userId);
        if ($user['status'] === 'error') {
            return response()->json([
                'status' => $user['status'],
                'message' => $user['message']
            ], $user['http_code']);
        }


        $response = Http::withHeaders($this->header)->post(
            $this->url . '/cek-saldo',
            [
                "cmd" => "pay-pasca",
                "username" => $this->user,
                "sign" => md5($this->user . $this->key . 'depo')
            ]
        );

        $data = json_decode($response->getBody(), true);

        if ($user['data']['role'] == 'admin') {
            $saldo = $data['data']['deposit'];
        } else {
            $saldo = $user['data']['saldo'];
        }
        return response()->json($saldo);
    }

    public function depositDigiflaz(Request $request)
    {
        $rules = [
            'user_id' => 'required|integer',
            'amount' => 'required|integer',
            'bank' => 'required|string',
            'owner_name' => 'required|string'
        ];

        $data = $request->all();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        $userId = $request->input('user_id');
        $user = getUser($userId);
        if ($user['status'] === 'error') {
            return response()->json([
                'status' => $user['status'],
                'message' => $user['message']
            ], $user['http_code']);
        }

        $response = Http::withHeaders($this->header)->post(
            $this->url . '/deposit',
            [
                "username" => $this->user,
                "bank" => $request->bank,
                "amount" => $request->amount,
                "owner_name" => $request->owner_name,
                "sign" => md5($this->user . $this->key . 'deposit')
            ]
        );

        $data = json_decode($response->getBody(), true);
        return response()->json($data);
    }
}
