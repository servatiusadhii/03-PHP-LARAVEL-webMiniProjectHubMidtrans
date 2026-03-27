<?php

namespace App\Http\Controllers;

use App\Cart;
use Exception;
use Midtrans\Snap;
use App\Transaction;
use Midtrans\Config;
use App\TransactionDetail;
use Midtrans\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function process(Request $request)
    {
        $user = Auth::user();
        $user->update($request->except('total_price'));
    
        // Generate kode transaksi
        $code = 'STORE-' . mt_rand(0000, 9999);
    
        // Ambil isi cart
        $carts = Cart::with(['product', 'user'])
                    ->where('users_id', $user->id)
                    ->get();
    
        // Buat transaksi utama
        $transaction = Transaction::create([
            'users_id' => $user->id,
            'inscurance_price' => 0,
            'shipping_price' => 0,
            'total_price' => $request->total_price,
            'transaction_status' => 'PENDING',
            'code' => $code
        ]);
    
        // Buat detail transaksi
        foreach ($carts as $cart) {
            $trx = 'TRX-' . mt_rand(0000, 9999);
            TransactionDetail::create([
                'transactions_id' => $transaction->id,
                'products_id' => $cart->product->id,
                'price' => $cart->product->price,
                'shipping_status' => 'PENDING',
                'resi' => '',
                'code' => $trx
            ]);
        }
    
        // Hapus cart
        Cart::where('users_id', $user->id)->delete();
    
        // Konfigurasi Midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');
    
        // Data untuk Snap JS
        $midtransParams = [
            'transaction_details' => [
                'order_id' => $code,
                'gross_amount' => (int) $request->total_price,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email
            ],
            'enabled_payments' => ['gopay', 'bank_transfer'],
        ];
    
        try {
            $midtransTransaction = Snap::createTransaction($midtransParams);
            $snapToken = $midtransTransaction->token ?? null;
    
            // Kirim snapToken ke view cart
            return view('cart', compact('carts', 'snapToken'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    public function callback(Request $request)
    {
        // Set konfigurasi midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // Buat instance midtrans notification
        $notification = new Notification();

        // Assign ke variable untuk memudahkan coding
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraud_status;
        $order_id = $notification->order_id;

        // Cari transaksi berdasarkan ID
        $transaction = Transaction::findOrFail($order_id);

        // Handle notification status midtrans
        if ($status == 'capture') {
            if ($type == 'credit_card'){
                if($fraud == 'challenge'){
                    $transaction->status = 'PENDING';
                }
                else {
                    $transaction->status = 'SUCCESS';
                }
            }
        }
        else if ($status == 'settlement'){
            $transaction->status = 'SUCCESS';
        }
        else if($status == 'pending'){
            $transaction->status = 'PENDING';
        }
        else if ($status == 'deny') {
            $transaction->status = 'CANCELLED';
        }
        else if ($status == 'expire') {
            $transaction->status = 'CANCELLED';
        }
        else if ($status == 'cancel') {
            $transaction->status = 'CANCELLED';
        }

        // Simpan transaksi
        $transaction->save();

        // Kirimkan email
        if ($transaction)
        {
            if($status == 'capture' && $fraud == 'accept' )
            {
                //
            }
            else if ($status == 'settlement')
            {
                //
            }
            else if ($status == 'success')
            {
                return response()->json([
                    'meta' => [
                        'code' => 200,
                        'message' => 'Midtrans Payment Challenge'
                    ]
                ]);
            }
            else if($status == 'capture' && $fraud == 'challenge' )
            {
                return response()->json([
                    'meta' => [
                        'code' => 200,
                        'message' => 'Midtrans Payment Challenge'
                    ]
                ]);
            }
            else
            {
                return response()->json([
                    'meta' => [
                        'code' => 200,
                        'message' => 'Midtrans Payment not Settlement'
                    ]
                ]);
            }

            return response()->json([
                'meta' => [
                    'code' => 200,
                    'message' => 'Midtrans Notification Success'
                ]
            ]);
        }
    }
}
