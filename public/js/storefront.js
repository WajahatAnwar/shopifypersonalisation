var loadScript = function(url, callback){

  /* JavaScript that will load the jQuery library on Google's CDN.
     We recommend this code: http://snipplr.com/view/18756/loadscript/.
     Once the jQuery library is loaded, the function passed as argument,
     callback, will be executed. */

};

var myAppJavaScript = function($){
  /* Your app's JavaScript here.
     $ in this scope references the jQuery object we'll use.
     Don't use 'jQuery', or 'jQuery191', here. Use the dollar sign
     that was passed as argument.*/
  $('body').append('<p>Your app is using jQuery version '+$.fn.jquery+'</p>');
  
  $("button[name='add']").show();
  $(".product_popup").hide();
  
  var color = '';

  $( window ).scroll(function() {
    $(".product_popup").show();
    if ($(this).scrollTop() == 0) {
      $(".product_popup").hide();
    }
  });

  $("#navbar").insertAfter(".site-header");
  var check = $(".event_identifier").val();
  if(check !== "")
  {
    if(Shopify.theme.name == "Pop"){
     
    }else if(Shopify.theme.name == "Venture"){
      
    }else if(Shopify.theme.name == "Minimal"){
 
    }else if(Shopify.theme.name == "Brooklyn"){
     
    }else if(Shopify.theme.name == "Narrative"){
      
    }else if(Shopify.theme.name == "Supply"){
     
    }else if(Shopify.theme.name == "Jumpstart"){
     
    }else if(Shopify.theme.name == "Boundless"){
      
    }else if(Shopify.theme.name == "Debut" || Shopify.theme.name == "debut"){

    }else if(Shopify.theme.name == "Simple" || Shopify.theme.name == "simple"){
    }
  }else{
    $("button[name='add']").attr("id", "custom_cart");
  }

  $("button[name='add']").addClass('add-to-cart-custom-btn');
  var change_atc_btn_text = $('#btn_text').val();
  $("button[name='add']").text(change_atc_btn_text);
  $("#addToCartText").text(change_atc_btn_text);
  $("#pitch_badges").insertAfter("button[name='add']");
  // $("button[name='add']").after('<h6>This is the product is secure by these companies</h6><img src="//cdn.shopify.com/s/files/1/0967/5702/t/12/assets/trust_badge1.png?14040153490723835372">');

  var product_val2 = $("#product_id_real").val();
  var product_val = product_val2.replace(/\s+/g, '-');
  $.getJSON('/products/'+product_val+'.js', function(product) {
    console.log(product);

    $("#product_description_sticky").text(product.title);
    
    $(".custom-price-span").html($('#productPrice-product-template span').html());
    $(".custom-price-span").html($('.price-item--sale').html());
    $(".custom-price-span").html($('.product-single__price').html());
    $(".custom-price-span").html($('.product__price--reg').html());
    $(".custom-price-span").html($('#ProductPrice-product-template').html());
    $(".custom-price-span").html($('.product__current-price').html());
    var compare_prices = product.compare_at_price/100;
    $("#sup_id").html(compare_prices+"<sup>00€</sup>")
    console.log(product.featured_image);
    if(product.featured_image !== null)
    {
      $("#product_images_sticky").attr("src", product.featured_image);
    }else{
      $("#product_images_sticky").attr("src", "https://cdn.shopify.com/s/assets/no-image-2048-5e88c1b20e087fb7bbe9a3771824e743c244f437e4f8ba93bbf7b11b53f7824c_1024x.gif");      
    }
    
  });

  $(".needsclick").click(function(){
    add_product_to_cart();
  });
  function add_product_to_cart()
  {
    var variant_id = $("#product_ids").val();
    var shop_url = Shopify.shop;
    $.ajax({            
        method: 'POST',
        dataType: 'json',
        data: { quantity: 1 , id: variant_id },
        url: '/cart/add.js',
        success: function(data) {
          // return false;
            if(data['status']!=404)
            {
              window.location.href= "/cart";
            }
        }
    });
  }
  
};

if ((typeof jQuery === 'undefined') || (parseFloat(jQuery.fn.jquery) < 1.7)) {
  loadScript('//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js', function(){
    jQuery191 = jQuery.noConflict(true);
    myAppJavaScript(jQuery191);
  });
} else {
  myAppJavaScript(jQuery);
}