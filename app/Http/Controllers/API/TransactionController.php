<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id     = $request->input('id');
        $limit  = $request->input('limit');
        $status = $request->input('status');

        if ($id) {
            $transaction = Transaction::with(['items'])->find($id);

            if (!$transaction) {
                return ResponseFormatter::error(
                    null,
                    'Data tidak ada',
                    404
                );
            }

            return ResponseFormatter::success(
                $transaction,
                'Data transaksi berhasil diambil'
            );
        }

        $transactions = Transaction::with(['items'])
            ->where('user_id', auth()->user()->id)
            ->when($status, function ($q, $status) {
                return $q->where('status', $status);
            });

        return ResponseFormatter::success(
            $transactions->paginate($limit),
            'Data transaction berhasil diambil'
        );
    }
}
