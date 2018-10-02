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
			<p>From here you can select a Product and type of the key you want to disable on the certain product.</p>
		</aside>
		<article>
			<div class="card">
				@if($success == 1)
				<div class="alert success">
					<dl>
						<dt>Key Disabled</dt>
						<dd>Key is successfully Disabled.</dd>
					</dl>
				</div>
				@endif
				<form action="/save_disable_key" method="POST">
					@csrf
					<div>
						<div class="" style="background-color: #5e8f3f;">
							<h2 style="color: #ffffff;padding: 21px;">Set Disable Key For Product</h2>
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
							<label>Product & Disable Key Type</label>
							<select name="type_option" id="type_option">
								<option value="front_embroidery">Front Embroidery</option>
								<option value="back_embroidery">Back Embroidery</option>
								<option value="front_back_embroidery">Front & Back Embroidery</option>
								<option value="second_front_back_embroidery">Second Front & Back Line</option>
							</select>
						</div>
					</div>
					<div class="row">
						<input type="submit" onclick="store()" class="button secondary">
					</div>	
				</form>
			</div>
		</article>
	</section>

		<section>
		<aside>
			<h2>Options</h2>
			<p>From here you can select a Product type.</p>
		</aside>
		<article>
			<div class="card">
				@if($success == 1)
				<div class="alert success">
					<dl>
						<dt>Product Type is successfull set </dt>
					</dl>
				</div>
				@endif
				<form action="/save_disable_key" method="POST">
					@csrf
					<div>
						<div class="" style="background-color: #5e8f3f;">
							<h2 style="color: #ffffff;padding: 21px;">Set Product Type</h2>
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
							<label>Product & Disable Key Type</label>
							<select name="type_option" id="type_option">
								<option value="wrap">Wrap</option>
								<option value="towel">Towel</option>
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

			<section>
		<aside>
			<h2>Add Variants</h2>
			<p>From here you can add variants to products</p>
		</aside>
		<article>
			<div class="card">
				@if($success == 7)
				<div class="alert success">
					<dl>
						<dt>Product Variants is added </dt>
					</dl>
				</div>
				@endif
				<form action="/save_variants" method="POST">
					@csrf
					<div>
						<div class="" style="background-color: #5e8f3f;">
							<h2 style="color: #ffffff;padding: 21px;">Set Variants</h2>
						</div>
						<input type="hidden" name="shopify_store_id" value="{{Session('shopifyId')}}">
						<div class="row">
							<label>Product</label>
							<select data-placeholder="Choose a Product..." class="chosen-select" tabindex="2" name="trigger_product" id="" required>
								@if(!empty($shop_products))
									@foreach ($shop_products as $product)
									{{ $variable = true }} 
										@foreach ($product->variants as $variant)
											@if ($variant->option1 == "Front Embroidery")
												{{ $variable = false }} 
											@endif
										@endforeach
										@if($variable)
											<option value="{{ $product->id }}-{{ $product->title }}">{{ $product->title }}</option>
										@endif
									@endforeach
								@endif
							</select>

						</div>
						<div class="row">
							<label for="product_price">product_price</label>
							<input type="text" name="product_price" id="product_price" placeholder="Enter Price of this Product">
						</div>
					</div>
					<div class="row">
						<input type="submit" onclick="store()" class="button secondary">
					</div>	
				</form>
			</div>
		</article>
	</section>

		<section id="">
		<aside>
  			<h2>Disable the key on product</h2>
		</aside>
		<article>
			<div class="card">
				@if($success == "4")
				<div class="alert success">
					<dl>
						<dt>Deleted Successfully</dt>
						<dd>Right Click Prevention is Deleted From This Product</dd>
					</dl>
				</div>
				@endif
				<h5>Disable the key on product</h5>
				<table>
					<thead>
						<tr>
						<th>Product Id</th>
						<th>Product Name</th>
						<th>Disabled Key</th>
						</tr>
					</thead>
					<tbody>
						@if(!empty($product_disable_key))
							@foreach ($product_disable_key as $disable_key)
								<tr>
									<td>{{ $disable_key->product_id }}</td>
									<td>{{ $disable_key->product_name }}</td>
									<td>{{ $disable_key->disable_key }}</td>
									<td><a href="https://app.robesale.com/delete_offer?meta_id={{ $disable_key->meta_field_id }}&product_id={{ $disable_key->product_id }}" class="button secondary icon-trash"></a></td>
								</tr>
							@endforeach
						@endif
					</tbody>
				</table>
			</div>
		</article>
	</section>
	
@endsection