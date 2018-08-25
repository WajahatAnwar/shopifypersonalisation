@extends('layouts.app')

@section('content')
	<header style="background-color: #5e8f3f;color: #ffffff;">
		<img src="{{ asset('carrer.png') }}" alt="logo of stack apps" style="width:45px;">
        <h1>Stack Apps</h1>
        <h2>A Simple App which get you sales</h2>
    </header>
	</br>
	<!-- <section>
		<aside>
			<h2>Aside Heading</h2>
			<p>content</p>
		</aside>
		<article>
			<div class="card">
				<div id = "text">
					<h1 id = "h1">Grumpy wizards make toxic brew for the evil Queen and Jack.</h1>
					<h2 id = "h2">Grumpy wizards make toxic brew for the evil Queen and Jack.</h2>
					<h3 id = "h3">Grumpy wizards make toxic brew for the evil Queen and Jack.</h3>
					<h4 id = "h4">Grumpy wizards make toxic brew for the evil Queen and Jack.</h4>
					<div id = "standard">Grumpy wizards make toxic brew for the evil Queen and Jack.</div><br>
				</div>
			</div>
		</article>
	</section> -->
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
						<dt>Options Updated</dt>
						<dd>All option are updated successfully.</dd>
					</dl>
				</div>
				@endif
				<form action="/save_data" method="POST">
					@csrf
					<div>
						<div class="" style="background-color: #5e8f3f;">
							<h2 style="color: #ffffff;padding: 21px;">Set Add To Cart</h2>
						</div>
						<div class="row">
							<label>Text of button</label>
							<input type="text" id="btn_text" name="btn_text" required/>
						</div>
						<div class="row">
							<label>Color of button</label>
							<input type="text" id="btn_color" name="btn_color" required/>
						</div>
						<div class="row">
							<label>Hover Color of button</label>
							<input type="text" id="btn_color_hover" name="btn_color_hover" required/>
						</div>
						<div class="row">
							<label>Width of button(in percentage)</label>
							<input type="number" id="btn_size" name="btn_size" max="100" required/>
						</div>
						<div class="row">
							<label>Font Size</label>
							<input type="text" id="pitch_size" name="pitch_size" required/>
						</div>
					</div>
					<div>
						<div class="" style="background-color: #5e8f3f;">
							<h2 style="color: #ffffff;padding: 21px;">Set Security Badges Attributes</h2>
						</div>
					</div>
					<div class="row">
						<label>Select Badges(Select Multiple Badges: hold shift and click on the options)</label>
						<select name="cards[]" id="card" multiple>
							<option value="mastercard">Master Card</option>
							<option value="visa">Visa Card</option>
							<option value="amex">American Express</option>
							<option value="paypal">Paypal</option>
							<option value="discover">Discover</option>
						</select>
					</div>
					<div class="row">
						<input type="submit" onclick="store()" class="button secondary">
					</div>	
				</form>
			</div>
		</article>
	</section>

	<script>
    function store(){

        var btn_text= document.getElementById("btn_text").value;
        var btn_color= document.getElementById("btn_color").value;
        var btn_size= document.getElementById("btn_size").value;
        var pitch_size= document.getElementById("pitch_size").value;
        var btn_color_hover= document.getElementById("btn_color_hover").value;
        
        var testObject = { 'btn_text': btn_text, 'btn_color': btn_color, 'btn_size': btn_size, 'pitch_size': pitch_size, 'btn_color_hover': btn_color_hover };
        localStorage.setItem('formattributes', JSON.stringify(testObject));
        
    }

    var retrievedObject = JSON.parse(localStorage.getItem('formattributes'));
    
    var btn_size = retrievedObject.btn_size;
    var btn_text =retrievedObject.btn_text;
    var btn_color =retrievedObject.btn_color;
    var pitch_size =retrievedObject.pitch_size;
    var btn_color_hover =retrievedObject.btn_color_hover;

    document.getElementById('btn_size').value = btn_size;
    document.getElementById('btn_color').value = btn_color;
	document.getElementById('btn_text').value = btn_text;
	document.getElementById("pitch_size").value = pitch_size;
	document.getElementById("btn_color_hover").value = btn_color_hover;
    
</script>

@endsection