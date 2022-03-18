<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
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

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'address'        => ['required'],
            'items'          => ['required', 'array'],
            'items.*.id'     => ['required', 'exists:products,id'],
            'items.*.qty'    => ['required', 'numeric', 'min:1'],
            'total_price'    => ['required'],
            'shipping_price' => ['required'],
            'status'         => ['required', 'in:PENDING,SUCCESS,CANCELLED,FAILED,SHIPPING,SHIPPED'],
        ]);

        $transaction = Transaction::create([
            'user_id'        => auth()->user()->id,
            'address'        => $validated['address'],
            'total_price'    => $validated['total_price'],
            'shipping_price' => $validated['shipping_price'],
            'status'         => $validated['status'],
        ]);

        foreach ($validated['items'] as $product) {
            TransactionItem::create([
                'user_id'        => auth()->user()->id,
                'product_id'     => $product['id'],
                'transaction_id' => $transaction->id,
                'quantity'       => $product['qty'],
            ]);
        }

        return ResponseFormatter::success($transaction->load('items.product'), 'Transaksi berhasil');
    }
}
