<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KasirController extends Controller
{
    public function index()
    {
        return view('kasir.index', ['trxNumber' => Transaction::nextNumber()]);
    }

    // Pencarian nama / kode / barcode
    public function produk(Request $request): JsonResponse
    {

        return response()->json(
            Product::query()
                ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('barcode', 'like', "%{$q}%"))
                ->orderBy('name')->limit(10)
                ->get(['id', 'code', 'barcode', 'name', 'price', 'stock'])
        );
    }

    // Simpan transaksi. Harga & total dihitung ulang di server (tidak percaya data browser).
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer' => 'nullable|string|max:50',
            'member_no' => 'nullable|string|max:30',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'discount_percent' => 'numeric|min:0|max:100',
            'discount_amount' => 'numeric|min:0',
            'tax' => 'numeric|min:0',
            'other_fee' => 'numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:Tunai,QRIS,Debit,Kredit,E-Wallet,Transfer',
        ]);

        $trx = DB::transaction(function () use ($data) {
            $products = Product::whereIn('id', collect($data['items'])->pluck('id'))
                ->lockForUpdate()->get()->keyBy('id');

            $subtotal = 0;
            $rows = [];
            foreach ($data['items'] as $it) {
                $p = $products[$it['id']];
                if ($p->stock < $it['qty']) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$p->name} tidak cukup (sisa {$p->stock}).",
                    ]);
                }
                $line = $p->price * $it['qty'];
                $subtotal += $line;
                $rows[] = [
                    'product_id' => $p->id, 'product_code' => $p->code, 'product_name' => $p->name,
                    'price' => $p->price, 'qty' => $it['qty'], 'subtotal' => $line,
                ];
                $p->decrement('stock', $it['qty']);
            }

            $discount = (int) round($subtotal * ($data['discount_percent'] ?? 0) / 100 + ($data['discount_amount'] ?? 0));
            $tax = (int) round($data['tax'] ?? 0);
            $fee = (int) round($data['other_fee'] ?? 0);
            $grand = max(0, $subtotal - $discount + $tax + $fee);
            $paid = (int) round($data['paid_amount']);

            if ($paid < $grand) {
                throw ValidationException::withMessages(['paid_amount' => 'Uang pembayaran masih kurang.']);
            }

            $trx = Transaction::create([
                'transaction_number' => Transaction::nextNumber(),
                'user_id' => auth()->id(),
                'customer' => $data['customer'] ?? 'Umum',
                'member_no' => $data['member_no'] ?? null,
                'subtotal' => $subtotal, 'discount' => $discount, 'tax' => $tax, 'other_fee' => $fee,
                'grand_total' => $grand, 'paid_amount' => $paid, 'change_amount' => $paid - $grand,
                'payment_method' => $data['payment_method'],
            ]);
            $trx->details()->createMany($rows);

            return $trx;
        });

        return response()->json(['receipt_url' => route('kasir.struk', $trx)], 201);
    }

    public function struk(Transaction $transaction)
    {
        return view('kasir.struk', ['trx' => $transaction->load('details')]);
    }
}
