<?php

use Illuminate\Support\Facades\Http;

function updateSaldo($data)
{
    $id = $data['user_id'];
    $amount = $data['amount'];
    // $url = env('SERVICE_USER_URL') . 'users/update-saldo?user_id=' . $id . '&amount=' . $amount;
    $url = env('SERVICE_USER_URL') . 'users/update-saldo';

    try {
        $response = Http::put($url, ['user_id' => $id, 'amount' => $amount]);
        $data = $response->json();
        $data['http_code'] = $response->getStatusCode();
    } catch (\Throwable $th) {
        return [
            'status' => 'error',
            'http_code' => 500,
            'message' => 'service user tidak tersedia'
        ];
    }
}


function getUser($userId)
{
    $url = env('SERVICE_USER_URL') . 'users/' . $userId;

    try {
        $response = Http::timeout(10)->get($url);
        $data = $response->json();
        $data['http_code'] = $response->getStatusCode();
        return $data;
    } catch (\Throwable $th) {
        return [
            'status' => 'error',
            'http_code' => 500,
            'message' => 'service user tidak tersedia'
        ];
    }
}

function getProductPrepaidBySKU($sku)
{
    $url = env('SERVICE_DATA_URL') . 'api/product_prepaid/sku';

    try {
        $response = Http::post($url, ['product_sku' => $sku]);
        $data = $response->json();
        $data['http_code'] = $response->getStatusCode();
        return $data;
    } catch (\Throwable $th) {
        return [
            'status' => 'error',
            'http_code' => 500,
            'message' => 'service user tidak tersedia'
        ];
    }
}

function getProductPascaBySKU($sku)
{
    $url = env('SERVICE_DATA_URL') . 'api/product_pasca/sku';

    try {
        $response = Http::post($url, ['product_sku' => $sku]);
        $data = $response->json();
        $data['http_code'] = $response->getStatusCode();
        return $data;
    } catch (\Throwable $th) {
        return [
            'status' => 'error',
            'http_code' => 500,
            'message' => 'service user tidak tersedia'
        ];
    }
}

function insert_product_prepaid($params)
{
    $url = env('SERVICE_DATA_URL') . 'api/product_prepaid';
    try {
        $response = Http::post($url, $params);
        $data = $response->json();
        $data['http_code'] = $response->getStatusCode();
        return $data;
    } catch (\Throwable $th) {
        return [
            'status' => 'error',
            'http_code' => 500,
            'message' => 'service data tidak tersedia'
        ];
    }
}

function insert_product_pasca($params)
{
    $url = env('SERVICE_DATA_URL') . 'api/product_pasca';
    try {
        $response = Http::post($url, $params);
        $data = $response->json();
        $data['http_code'] = $response->getStatusCode();
        return $data;
    } catch (\Throwable $th) {
        return [
            'status' => 'error',
            'http_code' => 500,
            'message' => 'service data tidak tersedia'
        ];
    }
}
