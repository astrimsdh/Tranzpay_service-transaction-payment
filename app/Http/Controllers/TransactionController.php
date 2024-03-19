<?php

namespace App\Http\Controllers;

use App\Models\TransactionModel;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = TransactionModel::query();

        $categoryId = $request->query('category_id');
        $transactions->when($categoryId, function ($query) use ($categoryId) {
            return $query->where('category_id', '=', $categoryId);
        });

        $userId = $request->query('user_id');
        $transactions->when($userId, function ($query) use ($userId) {
            return $query->where('transaction_user_id', '=', $userId);
        });

        return response()->json([
            'status' => 'success',
            'data' => $transactions->get()
        ]);
    }

    public function show($id)
    {
        $transactions = TransactionModel::find($id);
        if (!$transactions) {
            return response()->json([
                'status' => 'error',
                'message' => 'transaction not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $transactions
        ]);
    }
}
