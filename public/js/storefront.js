var loadScript = function(url, callback) {
  /* JavaScript that will load the jQuery library on Google's CDN.
     We recommend this code: http://snipplr.com/view/18756/loadscript/.
     Once the jQuery library is loaded, the function passed as argument,
     callback, will be executed. */
};

var myAppJavaScript = function($) {
  /* Your app's JavaScript here.
     $ in this scope references the jQuery object we'll use.
     Don't use 'jQuery', or 'jQuery191', here. Use the dollar sign
     that was passed as argument.*/
  $("body").append(
    "<p>Your app is using jQuery version " + $.fn.jquery + "</p>"
  );
  $(".header:contains(Embroidery)").hide();
  $(".front-embroidery").hide();
  $(".back-embroidery").hide();
  $(".front-back-embroidery").hide();
  $(".double-front-embroidery").hide();
  $(".double-back-embroidery").hide();
  $("input[name=second_front_only]").hide();
  $("input[name=second_back_only]").hide();

  var meta_front_back = $("#front_back_key").val();
  var meta_front = $("#front_key").val();
  var meta_back = $("#back_key").val();

  if (meta_front_back !== "") {
    $("option[value=front_back_embroidery]").hide();
  }
  if (meta_front !== "") {
    $("option[value=front_embroidery]").hide();
  }
  if (meta_back !== "") {
    $("option[value=back_embroidery]").hide();
  }
  $('input[name="wrap_checkbox"]').on("change", function() {
    if ($('input[name="wrap_checkbox"]').is(":checked")) {
      $("#embroidery_text_front_second").hide();
      $("#embroidery_text_back_second").hide();

      $("#embroidery_text_front").css("top", "505px");
      $("#embroidery_text_back").css("top", "505px");
    } else {
      $("#embroidery_text_front_second").show();
      $("#embroidery_text_back_second").show();

      $("#embroidery_text_front").css("top", "225px");
      $("#embroidery_text_back").css("top", "225px");
    }
  });

  $("#personlize_selection").click(function() {
    $(".ecom-modal").show();
    var price = $(".price-item--regular").text();
    $(".changing_price").text(price);
  });

  $("#personlize_select").on("change", function() {
    var type = $("#personlize_select")
      .find(":selected")
      .text();
    console.log(type);
    if (type == "Front Embroidery") {
      $("input[value='Front Embroidery']").trigger("click");
    }
    if (type == "Back Embroidery") {
      $("input[value='Back Embroidery']").trigger("click");
    }
    if (type == "Front & Back Embroidery") {
      $("input[value='Front & Back Embroidery']").trigger("click");
    }
  });

  $("input[value='Front Embroidery']").click(function() {
    $(".ecom-modal").show();
    $(".front_embroidery").show();
    $(".back_embroidery").hide();
    $(".front_back_embroidery").hide();
    $("#custom_image_front").show();
    $("#custom_image_back").hide();

    // $("#embroidery_text_front").show();
    $("#embroidery_text_back").hide();
    // $("#embroidery_text_front_second").show();
    $("#embroidery_text_back_second").hide();

    $(".show_select").show();

    var price = $(".price-item--regular").text();
    $(".changing_price").text(price);
  });

  $("input[value='Back Embroidery']").click(function() {
    $(".ecom-modal").show();
    $(".back_embroidery").show();
    $(".front_embroidery").hide();
    $(".front_back_embroidery").hide();
    $("#custom_image_front").hide();
    $("#custom_image_back").show();

    $("#embroidery_text_front").hide();
    // $("#embroidery_text_back").show();
    $("#embroidery_text_front_second").hide();
    // $("#embroidery_text_back_second").show();

    $(".show_select").show();

    var price = $(".price-item--regular").text();
    $(".changing_price").text(price);
  });

  $("input[value='Front & Back Embroidery']").click(function() {
    $(".ecom-modal").show();
    $(".front_back_embroidery").show();
    $(".front_embroidery").hide();
    $(".back_embroidery").hide();
    $("#custom_image_front").show();
    $("#custom_image_back").hide();

    // $("#embroidery_text_front").show();
    $("#embroidery_text_front_second").hide();
    $("#embroidery_text_back_second").hide();
    // $("#embroidery_text_back").hide();

    $(".show_select").show();

    var price = $(".price-item--regular").text();
    $(".changing_price").text(price);
  });

  $("#ecom-modal__btn-close").click(function() {
    $(".ecom-modal").hide();
  });

  $("input[name=second_front_only]").on("change", function() {
    var value = $(this).val();
    if (value !== "") {
      $("#second_front_embroidery").val(value);
      $("#second_back_embroidery").val("");
      $("input[name=second_back_only]").val("");
      $("input[value='Double Front Embroidery']").trigger("click");
      $("#embroidery_text_front_second").show();
      $("#embroidery_text_front_second").text(value);

      var price = $(".price-item--regular").text();
      $(".changing_price").text(price);
    } else {
      $("input[value='Front Embroidery']").trigger("click");
      $("#second_front_embroidery").val("");
      $("#second_back_embroidery").val("");
      $("#embroidery_text_front_second").hide();

      var price = $(".price-item--regular").text();
      $(".changing_price").text(price);
    }
  });

  $("input[name=second_back_only]").on("change", function() {
    var value = $(this).val();
    if (value !== "") {
      $("#second_back_embroidery").val(value);
      $("#second_front_embroidery").val("");
      $("input[name=second_front_only]").val("");
      $("input[value='Double Back Embroidery']").trigger("click");
      $("#embroidery_text_back_second").show();
      $("#embroidery_text_back_second").text(value);

      var price = $(".price-item--regular").text();
      $(".changing_price").text(price);
    } else {
      $("input[value='Back Embroidery']").trigger("click");
      $("#second_front_embroidery").val("");
      $("#second_back_embroidery").val("");
      $("#embroidery_text_back_second").hide();

      var price = $(".price-item--regular").text();
      $(".changing_price").text(price);
    }
  });

  $("input[name=front_only]").change(function() {
    var value = $("input[name=front_only]").val();
    $("#front_line_embroidery").val(value);
    $("#back_line_embroidery").val("");
    $("input[name=back_only]").val("");

    $("#front_line_embroidery_both").val("");
    $("input[name=front_both]").val("");
    $("input[name=back_both]").val("");
    $("#back_line_embroidery_both").val("");

    $("#embroidery_text_front").text(value);

    var price = $(".price-item--regular").text();
    $(".changing_price").text(price);

    if (value !== "") {
      $("#embroidery_text_front").show();
      $("input[name=second_front_only]").show();
    } else {
      $("#embroidery_text_front").hide();
      $("input[name=second_front_only]").hide();
    }
  });

  $("input[name=back_only]").change(function() {
    var value = $("input[name=back_only]").val();
    $("#back_line_embroidery").val(value);
    $("#front_line_embroidery").val("");
    $("input[name=front_only]").val("");

    $("#front_line_embroidery_both").val("");
    $("input[name=front_both]").val("");
    $("input[name=back_both]").val("");
    $("#back_line_embroidery_both").val("");

    $("#embroidery_text_back").text(value);

    var price = $(".price-item--regular").text();
    $(".changing_price").text(price);

    if (value !== "") {
      $("#embroidery_text_back").show();
      $("input[name=second_back_only]").show();
    } else {
      $("#embroidery_text_back").hide();
      $("input[name=second_back_only]").hide();
    }
  });

  $("input[name=front_both]").change(function() {
    var value = $("input[name=front_both]").val();
    $("#front_line_embroidery_both").val(value);

    $("input[name=front_only]").val("");
    $("input[name=back_only]").val("");

    $("#front_line_embroidery").val("");
    $("#back_line_embroidery").val("");

    var price = $(".price-item--regular").text();
    $(".changing_price").text(price);
  });

  $("input[name=back_both]").change(function() {
    var value = $("input[name=back_both]").val();
    $("#back_line_embroidery_both").val(value);

    $("input[name=front_only]").val("");
    $("input[name=back_only]").val("");

    $("#front_line_embroidery").val("");
    $("#back_line_embroidery").val("");

    var price = $(".price-item--regular").text();
    $(".changing_price").text(price);
  });

  $(".add_to_cart_custom_button").click(function() {
    $(".btnAddToCart").trigger("click");
  });
  var product_name = $("#product_name").val();
  jQuery.getJSON("/products/" + product_name + ".js", function(product) {
    $("#custom_image_front").attr("src", product.images["0"]);
    $("#custom_image_back").attr("src", product.images["1"]);
    console.log(product.images["0"]);
    console.log(product.images["1"]);
  });
  function add_product_to_cart() {
    var variant_id = $("#product_ids").val();
    var shop_url = Shopify.shop;
    $.ajax({
      method: "POST",
      dataType: "json",
      data: { quantity: 1, id: variant_id },
      url: "/cart/add.js",
      success: function(data) {
        // return false;
        if (data["status"] != 404) {
          window.location.href = "/cart";
        }
      }
    });
  }
};

if (typeof jQuery === "undefined" || parseFloat(jQuery.fn.jquery) < 1.7) {
  loadScript(
    "//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js",
    function() {
      jQuery191 = jQuery.noConflict(true);
      myAppJavaScript(jQuery191);
    }
  );
} else {
  myAppJavaScript(jQuery);
}
