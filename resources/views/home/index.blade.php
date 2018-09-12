@extends('layouts.app')

@section('content')
	<header style="background-color: #5e8f3f;color: #ffffff;">
		<img src="{{ asset('carrer.png') }}" alt="logo of stack apps" style="width:45px;">
	<h1>Robe Sale App</h1>
    </header>
	</br>
	<section>
		<aside>
			<h2>Options</h2>
			<p>From these options you can set the name of the Add-to-Cart button, Color of the Add-to-Cart button, Change the badges tag line and much more</p>
		</aside>
		<article>
			<div class="card">
				@if($success == 1)
				<div class="alert success">
					<dl>
						<dt>Switch Off Options</dt>
						<dd>Select and Product then select option you want to turn off.</dd>
					</dl>
				</div>
				@endif
				<form action="/save_disable_key" method="POST">
					@csrf
					<div>
						<div class="" style="background-color: #5e8f3f;">
							<h2 style="color: #ffffff;padding: 21px;">Set License Key For Product</h2>
						</div>
						<input type="hidden" name="shopify_store_id" value="{{Session('shopifyId')}}">
						<div class="row">
							<label>Product</label>
							<select data-placeholder="Choose a Product..." class="chosen-select" tabindex="2" name="trigger_product" id="" required>
								@if(!empty($shop_products))
									@foreach ($shop_products as $product)
										<option value="{{ $product->id }}-{{ $product->title }}">{{ $product->title }}</option>
									@endforeach
								@endif
							</select>
						</div>
						<div class="row">
							<label>License key</label>
							<select name="type_option" id="type_option">
								<option value="front_embroidery">Front Embroidery</option>
								<option value="back_embroidery">Back Embroidery</option>
								<option value="front_back_embroidery">Front & Back Embroidery</option>
								<option value="second_front_back_embroidery">Second Front & Back Line</option>
							</select>
							<!-- <input type="text" id="license_key" name="license_key" required/> -->
						</div>
					</div>
					<div class="row">
						<input type="submit" onclick="store()" class="button secondary">
					</div>	
				</form>
			</div>
		</article>
	</section>
	
@endsection