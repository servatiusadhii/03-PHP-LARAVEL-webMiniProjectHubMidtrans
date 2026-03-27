<?php

namespace App\Http\Controllers;

use App\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $carts = Cart::with(['product.galleries', 'user'])
                     ->where('users_id', Auth::user()->id)
                     ->get();
    
        // Hitung total price
        $totalPrice = $carts->sum(function($cart){
            return $cart->product->price;
        });
    
        // Konfigurasi Midtrans
        \Midtrans\Config::$serverKey = config('services.midtrans.serverKey');
        \Midtrans\Config::$isProduction = config('services.midtrans.isProduction');
        \Midtrans\Config::$isSanitized = config('services.midtrans.isSanitized');
        \Midtrans\Config::$is3ds = config('services.midtrans.is3ds');
    
        // Generate order_id sementara untuk Snap
        $orderId = 'ORDER-' . mt_rand(1000, 9999);
    
        // Data transaksi untuk Midtrans Snap
        $midtransParams = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $totalPrice,
            ],
            'customer_details' => [
                'first_name' => Auth::user()->name,
                'email'      => Auth::user()->email,
            ],
            'enabled_payments' => ['gopay', 'bank_transfer'],
        ];
    
        // Generate Snap token
        $snapToken = \Midtrans\Snap::getSnapToken($midtransParams);
    
        // Kirim data ke view
        return view('pages.cart', [
            'carts' => $carts,
            'totalPrice' => $totalPrice,
            'snapToken' => $snapToken
        ]);
    }

    public function delete(Request $request, $id)
    {
        $cart = Cart::findOrFail($id);

        $cart->delete();

        return redirect()->route('cart');
    }
    
    public function success()
    {
        return view('pages.success');
    }
}
