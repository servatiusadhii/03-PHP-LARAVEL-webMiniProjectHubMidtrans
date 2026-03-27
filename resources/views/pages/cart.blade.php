@extends('layouts.app')

@section('title')
    Store Cart Page
@endsection

@section('content')
<div class="page-content page-cart">

    <!-- Breadcrumbs -->
    <section class="store-breadcrumbs" data-aos="fade-down" data-aos-delay="100">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/index.html">Home</a></li>
                            <li class="breadcrumb-item active">Cart</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Cart Table -->
    <section class="store-cart">
        <div class="container">
            <div class="row" data-aos="fade-up" data-aos-delay="100">
                <div class="col-12 table-responsive">
                    <table class="table table-borderless table-cart">
                        <thead>
                            <tr>
                                <td>Image</td>
                                <td>Name & Seller</td>
                                <td>Price</td>
                                <td>Menu</td>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalPrice = 0 @endphp
                            @foreach ($carts as $cart)
                            <tr>
                                <td style="width:20%;">
                                    @if($cart->product->galleries)
                                    <img src="{{ Storage::url($cart->product->galleries->first()->photos) }}" 
                                        alt="" class="cart-image">
                                    @endif
                                </td>
                                <td style="width:35%;">
                                    <div class="product-title">{{ $cart->product->name }}</div>
                                    <div class="product-subtitle">by {{ $cart->product->user->store_name }}</div>
                                </td>
                                <td style="width:35%;">
                                    <div class="product-title">${{ number_format($cart->product->price) }}</div>
                                    <div class="product-subtitle">USD</div>
                                </td>
                                <td style="width:20%;">
                                    <form action="{{ route('cart-delete', $cart->products_id) }}" method="POST">
                                        @method('DELETE')
                                        @csrf
                                        <button class="btn btn-remove-cart" type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            @php $totalPrice += $cart->product->price @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Shipping Details -->
            <div class="row" data-aos="fade-up" data-aos-delay="150">
                <div class="col-12"><hr></div>
                <div class="col-12"><h2 class="mb-4">Shipping Details</h2></div>
            </div>

            <!-- Checkout Form -->
            <form id="checkout-form" method="POST">
                @csrf
                <input type="hidden" name="total_price" value="{{ $totalPrice }}">
                <input type="hidden" id="snap_token" name="snap_token" value="{{ $snapToken ?? '' }}">

                <div class="row mb-2" data-aos="fade-up" data-aos-delay="200" id="locations">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Address 1</label>
                            <input type="text" class="form-control" name="address_one" value="Setra Duta Cemara">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Address 2</label>
                            <input type="text" class="form-control" name="address_two" value="Blok B2 No.34">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Province</label>
                            <select name="provinces_id" class="form-control" v-model="provinces_id" v-if="provinces">
                                <option v-for="province in provinces" :value="province.id">@{{ province.name }}</option>
                            </select>
                            <select v-else class="form-control"></select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>City</label>
                            <select name="regencies_id" class="form-control" v-model="regencies_id" v-if="regencies">
                                <option v-for="regency in regencies" :value="regency.id">@{{ regency.name }}</option>
                            </select>
                            <select v-else class="form-control"></select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Postal Code</label>
                            <input type="text" class="form-control" name="zip_code" value="40512">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" class="form-control" name="country" value="Indonesia">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Mobile</label>
                            <input type="text" class="form-control" name="phone_number" value="+628 2020 11111">
                        </div>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="row" data-aos="fade-up" data-aos-delay="150">
                    <div class="col-12"><hr></div>
                    <div class="col-4 col-md-2">
                        <div class="product-title">$0</div>
                        <div class="product-subtitle">Country Tax</div>
                    </div>
                    <div class="col-4 col-md-3">
                        <div class="product-title">$0</div>
                        <div class="product-subtitle">Product Insurance</div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="product-title">$0</div>
                        <div class="product-subtitle">Ship to Jakarta</div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="product-title text-success">${{ number_format($totalPrice) }}</div>
                        <div class="product-subtitle">Total</div>
                    </div>
                    <div class="col-8 col-md-3">
                        <button type="button" id="pay-button" class="btn btn-success mt-4 px-4 btn-block">Checkout Now</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection

@push('addon-script')
<script src="/vendor/vue/vue.js"></script>
<script src="https://unpkg.com/vue-toasted"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>

<!-- Midtrans Snap JS -->
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('services.midtrans.clientKey') }}"></script>

<script>
    // Snap Payment
    var payButton = document.getElementById('pay-button');
    payButton.addEventListener('click', function () {
        var snapToken = document.getElementById('snap_token').value;
        if(!snapToken){
            alert('Snap token is missing!');
            return;
        }

        snap.pay(snapToken, {
            onSuccess: function(result){
                window.location.href = "{{ route('success') }}";
            },
            onPending: function(result){
                window.location.href = "{{ route('success') }}";
            },
            onError: function(result){
                alert("Payment failed!");
            },
            onClose: function(){
                alert('You closed the payment popup without finishing the payment');
            }
        });
    });
</script>

<script>
    // Vue for province & city
    var locations = new Vue({
        el: "#locations",
        mounted() { this.getProvincesData(); },
        data: { provinces: null, regencies: null, provinces_id: null, regencies_id: null },
        methods: {
            getProvincesData() {
                var self = this;
                axios.get('{{ route('api-provinces') }}').then(function(response){
                    self.provinces = response.data;
                });
            },
            getRegenciesData() {
                var self = this;
                axios.get('{{ url('api/regencies') }}/' + self.provinces_id).then(function(response){
                    self.regencies = response.data;
                });
            },
        },
        watch: {
            provinces_id() {
                this.regencies_id = null;
                this.getRegenciesData();
            }
        }
    });
</script>
@endpush