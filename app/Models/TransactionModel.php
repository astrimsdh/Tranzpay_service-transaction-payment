<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TransactionModel extends Model
{
    use HasFactory;
    protected $table = 'transaction';
    protected $primaryKey = 'id';
    protected $fillable = [
        'category_id',
        'transaction_code',
        'transaction_date',
        'transaction_time',
        'transaction_type',
        'transaction_number',
        'transaction_sku',
        'transaction_total',
        'transaction_message',
        'transaction_status',
        'transaction_user_id',
        'raw_response'

    ];

    public function scopeGetTransactionByRefID($query, $value)
    {
        return $query->where('transaction_code', $value);
    }



    public function insert_transaction_data($data, $type,  $user, $product)
    {
        if (strtolower($type) == 'prepaid') {
            if ($user['role'] == 'admin') {
                $total = isset($data['price']) ? $data['price'] : $product['product_seller_price'];
            } else {
                $total = $product['product_buyer_price'];
            }
        } else {
            if ($user['role'] == 'admin') {
                $total = isset($data['price']) ? $data['price'] : 0;
            } else {
                $total = isset($data['selling_price']) ? $data['selling_price'] : 0;
            }
        }


        return self::create([
            'category_id' => $product['category_id'],
            'transaction_code' => $data['ref_id'],
            'transaction_date' => Carbon::now()->format('Y-m-d'),
            'transaction_time' => Carbon::now(),
            'transaction_type' => $type,
            'transaction_number' => $data['customer_no'],
            'transaction_sku' => $data['buyer_sku_code'],
            'transaction_total' => $total,
            'transaction_message' => $data['message'],
            'transaction_status' => $data['status'],
            'transaction_user_id' => $user['id'],
            'raw_response' => json_encode($data)
        ]);
    }
}
