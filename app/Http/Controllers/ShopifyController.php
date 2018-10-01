<?php
namespace App\Http\Controllers;
use DB;
use Log;
use App\Shop;
use App\Setting;
use App\Objects\ScriptTag;
use Illuminate\Http\Request;
use Oseintow\Shopify\Shopify;
use App\Objects\ShopifyWebhook;
use Oseintow\Shopify\Exceptions\ShopifyApiException;
use App\ShopInfo;
class ShopifyController extends Controller
{
    protected $shopify;
    function __construct(Shopify $shopify)
    {
    	$this->shopify = $shopify;
    }
    public function access(Request $request)
    {
    	
    	$shopUrl = $request->shop;
    	if($shopUrl)
    	{
    		$shop = Shop::where('myshopify_domain' , $shopUrl)->first();
    		if($shop)
    		{
    			session([
    					'shopifyId' => $shop->shopify_id,
    					'myshopifyDomain' => $shop->myshopify_domain,
    					'accessToken' => $shop->access_token
					]);
				$this->create_template();
				$shopProducts = $this->shopify->setShopUrl($shop->myshopify_domain)
				->setAccessToken($shop->access_token)
				->get('admin/products.json',[ 'limit' => 250 , 'page' => 1 ]);
				// print_r($shopProducts);
				// dd();
				$product_disable_key = DB::Table('product_disable_key')->where('shopify_store_id', session('shopifyId') )->get();
				return view('home.index' , ['shop' => $shop , 'settings' => $shop->settings, "shop_products" => $shopProducts, "product_disable_key" => $product_disable_key, 'success' => '2']);

    		}
    		else{
    			$shopify = $this->shopify->setShopUrl($shopUrl);
    			return redirect()->to($shopify->getAuthorizeUrl(config('shopify.scope') , config('shopify.redirect_uri')));
    		}
    	}
    	else{
    		abort(404);
    	}
    }
    public function callback(Request $request)
    {
		$queryString = $request->getQueryString();
		
    	if($this->shopify->verifyRequest($queryString))
    	{
    		$shopUrl = $request->shop;
    		try{
    			$accessToken = $this->shopify->setShopUrl($shopUrl)->getAccessToken($request->code);
    			$shopResponse = $this->shopify->setShopUrl($shopUrl)
    										  ->setAccessToken($accessToken)
    										  ->get('admin/shop.json');
  				if($shopResponse)
  				{
  					session([
  							'shopifyId' => $shopResponse['id'],
  							'myshopifyDomain' => $shopUrl,
  							'accessToken' => $accessToken
					]);
					
					$shop = $this->createShop($shopResponse);
					$this->createDefaultSettings($shop);
					$this->storeShopInfo($shopResponse, $shop->id);
					ShopifyWebhook::registerAppUninstallWebhook();
					if(config('shopify.billing_enabled'))
					{
						return redirect()->route('billing.charge');
					}
		
					ScriptTag::register();
					  
  					return redirect("https://{$shopUrl}/admin/apps");
  				}
    		} catch (ShopifyApiException $e) {
				Log::critical("Installation Callback exception." , ['message' => $e->getMessage(), 'shop' => $shopUrl]);
				abort(500);
    		}
    	}else{
			abort(500,"Hmm, Something doesn't look right.");
		}
    }
   	protected function createShop($data)
	{
		return Shop::create([
				'shopify_id' => $data['id'],
				'myshopify_domain' => $data['myshopify_domain'],
				'access_token' => session('accessToken')
		]);
	}
	protected function createDefaultSettings($shop)
    {
        return $settings = Setting::create([
            'enabled' => 1,
            'shop_id' => $shop->id,
            'myshopify_domain' => $shop->myshopify_domain
        ]);
	}
	
	protected function storeShopInfo($data, $shopId)
	{
		unset($data['id']);
		$data['shop_id'] = $shopId;
		return ShopInfo::create($data->toArray());
	}

	public function save_data()
	{
		$shopUrl= session('myshopifyDomain');
		$shop = Shop::where('myshopify_domain' , $shopUrl)->first();

				return view('home.index' , ['shop' => $shop , 'settings' => $shop->settings, 'success' => '1']);
	}

	public function save_variants()
	{
		$product_ids = $_POST['trigger_product'];
		$dash_pos = strpos($product_ids, "-");
		$product_id = substr($product_ids, 0, $dash_pos);
		$product_name = substr($product_ids, $dash_pos+1);

		$shopify_store_id = $_POST['shopify_store_id'];


			$postData1 = [
				"option1" => "Embroidery",
				"option2" => "Front Embroidery",
				"price" => "5.00"
			];
			$data1 = $this->shopify->setShopUrl(session('myshopifyDomain'))
					->setAccessToken(session('accessToken'))
					->post("/admin/products/".$product_id."/variants.json", [ 'variant' => $postData1 ]);

			$postData2 = [
				"option1" => "Embroidery",
				"option2" => "Back Embroidery",
				"price" => "10.00"
			];
			$data2 = $this->shopify->setShopUrl(session('myshopifyDomain'))
					->setAccessToken(session('accessToken'))
					->post("/admin/products/".$product_id."/variants.json", [ 'variant' => $postData2 ]);
				
			$postData3 = [
							"option1" => "Embroidery",
							"option2" => "Front & Back Embroidery",
							"price" => "15.00"
						];
			$data3 = $this->shopify->setShopUrl(session('myshopifyDomain'))
									->setAccessToken(session('accessToken'))
									->post("/admin/products/".$product_id."/variants.json", [ 'variant' => $postData3 ]);
			
			$postData4 = [
							"option1" => "Embroidery",
							"option2" => "Double Front Embroidery",
							"price" => "20.00"
						];
			$data4 = $this->shopify->setShopUrl(session('myshopifyDomain'))
									->setAccessToken(session('accessToken'))
									->post("/admin/products/".$product_id."/variants.json", [ 'variant' => $postData4 ]);
			
			$postData5 = [
							"option1" => "Embroidery",
							"option2" => "Double Back Embroidery",
							"price" => "25.00"
						];
			$data5 = $this->shopify->setShopUrl(session('myshopifyDomain'))
									->setAccessToken(session('accessToken'))
									->post("/admin/products/".$product_id."/variants.json", [ 'variant' => $postData5 ]);
			
			$postData6 = [
							"option1" => "Embroidery",
							"option2" => "Front Rush",
							"price" => "30.00"
						];
			$data6 = $this->shopify->setShopUrl(session('myshopifyDomain'))
									->setAccessToken(session('accessToken'))
									->post("/admin/products/".$product_id."/variants.json", [ 'variant' => $postData6 ]);
			
			$postData7 = [
							"option1" => "Embroidery",
							"option2" => "Back Rush",
							"price" => "35.00"
						];
			$data7 = $this->shopify->setShopUrl(session('myshopifyDomain'))
									->setAccessToken(session('accessToken'))
									->post("/admin/products/".$product_id."/variants.json", [ 'variant' => $postData7 ]);
			
			$postData8 = [
							"option1" => "Embroidery",
							"option2" => "Front & Back Rush",
							"price" => "40.00"
						];
			$data8 = $this->shopify->setShopUrl(session('myshopifyDomain'))
									->setAccessToken(session('accessToken'))
									->post("/admin/products/".$product_id."/variants.json", [ 'variant' => $postData8 ]);
			
			$postData9 = [
							"option1" => "Embroidery",
							"option2" => "Double Front Rush",
							"price" => "45.00"
						];
			$data9 = $this->shopify->setShopUrl(session('myshopifyDomain'))
									->setAccessToken(session('accessToken'))
									->post("/admin/products/".$product_id."/variants.json", [ 'variant' => $postData9 ]);
			
			$postData10 = [
							"option1" => "Embroidery",
							"option2" => "Double Back Rush",
							"price" => "50.00"
						];
			$data10 = $this->shopify->setShopUrl(session('myshopifyDomain'))
									->setAccessToken(session('accessToken'))
									->post("/admin/products/".$product_id."/variants.json", [ 'variant' => $postData10 ]);

		$shopUrl= session('myshopifyDomain');
		$shopify_id = session('shopifyId');
		$shop = Shop::where('myshopify_domain' , $shopUrl)->first();
		$shopProducts = $this->shopify->setShopUrl($shop->myshopify_domain)
					->setAccessToken($shop->access_token)
					->get('admin/products.json',[ 'limit' => 250 , 'page' => 1 ]);
		$product_disable_key = DB::Table('product_disable_key')->where('shopify_store_id', $shopify_id )->get();
		return view('home.index' , ['shop' => $shop , 'settings' => $shop->settings, "shop_products" => $shopProducts, "product_disable_key" => $product_disable_key,'success' => '7']);
	}

	public function save_disable_key()
	{
		$product_ids = $_POST['trigger_product'];
		$dash_pos = strpos($product_ids, "-");
		$product_id = substr($product_ids, 0, $dash_pos);
		$product_name = substr($product_ids, $dash_pos+1);

		$disable_key = $_POST['type_option'];
		$shopify_store_id = $_POST['shopify_store_id'];

		$meta_key_value = $_POST['type_option'];
		if($meta_key_value == "front_embroidery")
		{
			$postData = [
				"namespace" => "disable_robe_key",
				"key" => "front_key",
				"value" => $meta_key_value,
				"value_type" => "string"
			];
		}else if($meta_key_value == "back_embroidery")
		{
			$postData = [
				"namespace" => "disable_robe_key",
				"key" => "back_key",
				"value" => $meta_key_value,
				"value_type" => "string"
			];
		}else if($meta_key_value == "front_back_embroidery")
		{
			$postData = [
				"namespace" => "disable_robe_key",
				"key" => "front_back_keys",
				"value" => $meta_key_value,
				"value_type" => "string"
			];
		}else if($meta_key_value == "second_front_back_embroidery")
		{
			$postData = [
				"namespace" => "disable_robe_key",
				"key" => "second_front_back_key",
				"value" => $meta_key_value,
				"value_type" => "string"
			];
		}else if($meta_key_value == "wrap")
		{
			$postData = [
				"namespace" => "disable_robe_key",
				"key" => "wrap",
				"value" => $meta_key_value,
				"value_type" => "string"
			];
		}else if($meta_key_value == "towel")
		{
			$postData = [
				"namespace" => "disable_robe_key",
				"key" => "towel",
				"value" => $meta_key_value,
				"value_type" => "string"
			];
		}

		$data = $this->shopify->setShopUrl(session('myshopifyDomain'))
				 ->setAccessToken(session('accessToken'))
				 ->post("/admin/products/".$product_id."/metafields.json", [ 'metafield' => $postData ]);

		$disable_option_key = DB::Table('product_disable_key')->where('product_id', $product_id )->where('disable_key', $disable_key)->first();
		if(empty($disable_option_key))
		{
			$id = DB::table('product_disable_key')->insertGetId([
				'shopify_store_id' => $shopify_store_id,
				'product_id' => $product_id, 
				'product_name' => $product_name,
				'disable_key' => $disable_key, 
				'meta_field_id' => $data['id'], 
				'created_at'=> date('Y-m-d H:i:s'), 
				'updated_at'=> date('Y-m-d H:i:s')
			]);
		}else{
			DB::table('product_disable_key')->where('product_id', $product_id)->update([
				'meta_field_id' => $data['id'], 
				'updated_at' => date('Y-m-d H:i:s')]);
		}

		$shopUrl= session('myshopifyDomain');
		$shopify_id = session('shopifyId');
		$shop = Shop::where('myshopify_domain' , $shopUrl)->first();
		$shopProducts = $this->shopify->setShopUrl($shop->myshopify_domain)
					->setAccessToken($shop->access_token)
					->get('admin/products.json',[ 'limit' => 250 , 'page' => 1 ]);
		$product_disable_key = DB::Table('product_disable_key')->where('shopify_store_id', $shopify_id )->get();
		return view('home.index' , ['shop' => $shop , 'settings' => $shop->settings, "shop_products" => $shopProducts, "product_disable_key" => $product_disable_key,'success' => '1']);
	}

	public function delete_offer()
	{
		$meta_id = $_GET['meta_id'];
		$product_id = $_GET['product_id'];

		$shopUrl = session('myshopifyDomain');
		$accessToken = session('accessToken');
		$shopifyId = session('shopifyId');

		$data3 = $this->shopify->setShopUrl($shopUrl)
				   ->setAccessToken($accessToken)
					 ->delete("/admin/products/".$product_id."/metafields/".$meta_id.".json");
		if($data3)
		{
			$deleting = DB::table('product_disable_key')->where('shopify_store_id', $shopifyId )
			->where('meta_field_id', $meta_id)->delete();
		}
		
		$shop = Shop::where('myshopify_domain' , $shopUrl)->first();
		$shopProducts = $this->shopify->setShopUrl($shopUrl)
					->setAccessToken($accessToken)
					->get('admin/products.json',[ 'limit' => 250 , 'page' => 1 ]);
		$product_disable_key = DB::Table('product_disable_key')->where('shopify_store_id', $shopifyId )->get();
		return view('home.index' , ['shop' => $shop , 'settings' => $shop->settings, "shop_products" => $shopProducts, "product_disable_key" => $product_disable_key,'success' => '4']);

	}

	//This function include our custom template in the product.liquid.
	// Two function include_template_files and update_template_file
	// Are responsible for appending this code to product.liquid
	public function include_template_files()
	{
		$shopUrl= session('myshopifyDomain');
		$accessToken= session('accessToken');
		$data = $this->shopify->setShopUrl($shopUrl)->setAccessToken($accessToken)->get('admin/themes.json');
		$objection = json_decode($data);
		$name_check = "";
		$theme_id;
		foreach($data as $datas)
		{
			if($datas->role == "main")
			{
				$theme_id = $datas->id;
				$name_check = $datas->name;
			} 
		}
		$template_name='templates/product.liquid';
		$server_template = $this->shopify->setShopUrl($shopUrl)
								->setAccessToken($accessToken)
								->get("/admin/themes/".$theme_id."/assets.json", ["asset[key]" => "$template_name", "theme_id" => $theme_id]);
		$view = $server_template['value'];
		$view .= "{% include 'personalisation-popup' %}{% assign event_identifier = product.metafields.disable_robe_key %}
		<input type='hidden' value='{{ disable_robe_key['disable_robe_key'] }}' id='disable_robe_key' class='disable_robe_key' />

		<input type='hidden' value='{{ product.variants.first.id }}' id='product_id' class='product' />
		<input type='hidden' value='{{ product.id }}' id='product_id_real' class='product' />
		<input type='hidden' value='{{ product.title }}' id='product_name' class='product' />";
		
		$this->update_templete_files($view,$shopUrl,$accessToken,$theme_id);
	}


	public function update_templete_files($view,$shopUrl,$accessToken,$theme_id)
	{
		$postData = [
							"key" => "templates/product.liquid",
							"value" => $view
					];
		$data = $this->shopify->setShopUrl($shopUrl)->setAccessToken($accessToken)->put('/admin/themes/'.$theme_id.'/assets.json',[ 'asset' => $postData ]);
		// dd($data);
	}

	// This function create a template for popup
	// By using this function we include the popup on the frontend
    public function create_template()
    {
		$shopUrl= session('myshopifyDomain');
		// dd($shopUrl);
        $accessToken= session('accessToken');
        $data = $this->shopify->setShopUrl($shopUrl)->setAccessToken($accessToken)->get('admin/themes.json');
        $objection = json_decode($data);
        // dd($data);
        $name_check = "";
        $theme_id;
        foreach($data as $datas)
        {
            if($datas->role == "main")
            {
                $theme_id = $datas->id;
                $name_check = $datas->name;
            } 
		}
		$view = "<style>

		/*
	  ------------------------------------  WARNING  ------------------------------------
	  This file will be overwritten and should not be edited directly.
	  In order to edit custom CSS for ecom Product Upsell you should:
	  - Log into your Shopify Admin Panel
	  - Go to Apps --> Installed --> Product Upsell
	  - Go to Display Settings
	  ------------------------------------  WARNING  ------------------------------------
	  */
	  .ecom-modal {
		box-sizing: border-box;
		position: fixed;
		width: 100%;
		height: 100%;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 99999999;
		display: none; }
	  
		.ecom-modal__window {
		  box-sizing: border-box;
		  padding: 30px;
		  background-color: #fff;
		  position: fixed;
		  left: 0;
		  right: 0;
		  bottom: 0;
		  overflow-y: auto; }
	  
		.ecom-modal__btn-close {
		  box-sizing: border-box;
		  display: block;
		  font-weight: 900;
		  width: 40px;
		  height: 40px;
		  font-size: 20px;
		  line-height: 40px;
		  text-align: center;
		  color: rgba(0,0,0,0.2);
		  position: absolute;
		  top: 0;
		  right: 0;
		  cursor: pointer; }
	  
		  .ecom-modal__btn-close:hover {
			opacity: 0.5; }
	  
	  
		.ecom-modal__header {
		  box-sizing: border-box;
		  height: 40px;
		  line-height: 40px;
		  padding: 0 70px 0 30px;
		  margin: -30px -30px 0px;
		  overflow: hidden;
		  text-overflow: ellipsis;
		  white-space: nowrap; }
	  
		.ecom-modal__content {
		  box-sizing: border-box;
		  padding: 30px;
		  margin: -30px -30px 0; }
	  
		  .ecom-modal__header+.ecom-modal__content {
			margin-top: 0; }
	  
		  .ecom-modal__content:last-child {
			margin-bottom: -30px; }
	  
		.ecom-modal__footer {
		  box-sizing: border-box;
		  padding: 30px;
		  margin: 0px -30px -30px; }
	  
	  
	  /* Showing and Hiding the Modal */
	  body.ecom-modal--is-showing,
	  div.ecom-modal--is-showing {
		overflow-y: hidden !important; }
	  
	  body.ecom-modal--is-showing .ecom-modal,
	  div.ecom-modal--is-showing .ecom-modal {
		display: block; }
	  
	  /* Transitions and Animations */
	  .ecom-modal--animated {
		display: block;
		visibility: hidden;
		-webkit-transition: 0.3s ease;
		-moz-transition: 0.3s ease;
		transition: 0.3s ease; }
	  
	  .ecom-modal--animated .ecom-modal__window {
		top: 100vh;
		-webkit-transition: 0.3s ease;
		-moz-transition: 0.3s ease;
		transition: 0.3s ease; }
	  
	  body.ecom-modal--is-showing .ecom-modal--animated,
	  div.ecom-modal--is-showing .ecom-modal--animated {
		visibility: visible;
		opacity: 1; }
	  
	  @media only screen and (min-width: 499px) {
		.ecom-modal {
		  background: rgba(0,0,0,0.8);
		  overflow-y: auto; }
	  
		  .ecom-modal__window {
			position: relative;
			margin: 40px; }
	  
		.ecom-modal--animated {
		  opacity: 0; }
	  
		  .ecom-modal--animated .ecom-modal__window {
			top: 200px; }
	  }
	  
	  @media only screen and (min-width: 879px) {
		.ecom-modal__window {
		  max-width: 800px;
		  margin: 40px auto; }
	  }
	  
	  /* ecom.grid.css */
	  .ecom-grid {
		box-sizing: border-box;
		margin: 0px -15px; }
	  
	  .ecom-grid:after {
		box-sizing: border-box;
		display: table;
		content: '';
		clear: both; }
	  
	  .ecom-grid__column {
		box-sizing: border-box;
		padding: 15px;
		float: left;
	  }
	  
	  .ecom-grid__column--half{
		width: 50%;
	  }
	  
	  .ecom-grid__column--third{
		width: 33.3333%;
	  }
	  
	  @media only screen and (max-width: 700px) {
	  
		.ecom-grid__column--half,
		.ecom-grid__column--third,
		.ecom-grid__column--quarter {
		  width: 100%; }
	  
		.ecom-grid__column--third:first-child {
		  width: 100%;
		}
	  
	  
		.flickity-slider .ecom-grid__column--half,
		.flickity-slider .ecom-grid__column--third,
		.flickity-slider .ecom-grid__column--quarter {
		  width: 70%;
		}
	  
		.flickity-slider .ecom-grid__column--third:first-child {
		  width: 70%;
		}
	  
	  }
	  
	  
	  
	  /* ecom.grid.css */
	  
	  /* ecom.product.css */
	  .ecom-product {
		box-sizing: border-box; }
	  
	  .ecom-product__image-container {
		box-sizing: border-box; }
	  
	  .ecom-product__image {
		box-sizing: border-box;
		display: inline-block;
		max-width: 100%;
		max-height: 300px;}
	  
	   /* BEGIN Trigger Product display styling  */
	  .ecom-upsell__triger-product-container{
		display: flex;
		box-sizing: border-box;
		margin-bottom: 15px;
	  }
	  
	  .ecom-upsell__triger-image-container{
		width: 85px;
		height: 85px;
	  }
	  
	  .ecom-upsell__triger-product-container .ecom-product__info{
		width: calc(100% - 85px);
		display: inline-block;
		margin: 0 0 0 20px;
	  }
	  .ecom-upsell__triger-product-container .ecom-product__info div{
		max-height: 20px;
		margin-bottom: 3px;
	  }
	  
	  .ecom-upsell__triger-product-container .ecom-product__info .ecom-product__quantity{
		opacity: 0.7;
	  }
	  
	  .ecom-upsell__triger-product-container .ecom-product__info .ecom-product__title{
		max-height: 20px;
		overflow: hidden;
		margin-bottom: 0px;
	  }
	  
	  .ecom-upsell__triger-product-container .ecom-product__info .ecom-product__variant{
		opacity: 0.7;
	  }
	  
	  .ecom-upsell__triger-product-container .ecom-product__info .ecom-product__pricing{
		margin-top: 0px;
	  }
	  
	  .ecom-upsell__triger-product-container .ecom-product__info .ecom-product__price{
		font-size: 100%;
	  }
	   /*---END Trigger Product display styling -----*/
	  
	  .ecom-product__control {
		box-sizing: border-box; }
	  
	  .ecom-product__variant-selector {
		font: inherit;
		width: 100%; }
	  /* ecom.product.css */
	  
	  /* ecom.upsell.css */
	  .ecom-upsell {
		box-sizing: border-box; }
	  
	  .ecom-upsell__intro {
		box-sizing: border-box; }
	  
	  .ecom-upsell__products-list {
		box-sizing: border-box; }
	  
	  .ecom-upsell__products-list .ecom-grid__column {
		text-align: center; }
	  
	  .ecom-upsell__actions {
		text-align: right; }
	  
	  .ecom-upsell__button {
		box-sizing: border-box; }
	  /* ecom.upsell.css */
	  
	  .ecom-grid__column--half .ecom-product .ecom-product__image,
	  .ecom-grid__column--third .ecom-product .ecom-product__image {
		max-height: 100%;
		max-width: 100%;
		position: absolute;
		top: 50%;
		left: 50%;
		-webkit-transform: translate(-50%, -50%);
		-moz-transform: translate(-50%, -50%);
		-ms-transform: translate(-50%, -50%);
		-o-transform: translate(-50%, -50%);
		transform: translate(-50%, -50%);
	  }
	  
	  .ecom-grid__column--half .ecom-product .ecom-product__image-container,
	  .ecom-grid__column--third .ecom-product .ecom-product__image-container {
		max-height: 300px;
		max-width: 300px;
		width: 100%;
		height: 0;
		padding-bottom: 100%;
		position: relative;
		margin: 0 auto;
	  }
	  
	  /*Options Modal stuff*/
	  #ecom-modal-second__window #ecom-modal__content .ecom-product__info {
		display: inline-flex;
	  }
	  
	  #ecom-modal-second__window #ecom-modal__content .ecom-product__info #option_product_info{
		padding-left: 15px;
	  }
	  
	  #ecom-modal-second__window #ecom-modal__content .ecom-product__image {
		max-height: 100%;
		max-width: 100%;
		position: absolute;
		top: 50%;
		left: 50%;
		-webkit-transform: translate(-50%, -50%);
		-moz-transform: translate(-50%, -50%);
		-ms-transform: translate(-50%, -50%);
		-o-transform: translate(-50%, -50%);
		transform: translate(-50%, -50%);
	  }
	  
	  #ecom-modal-second__window #ecom-modal__content .ecom-product__image {
		max-height: 75px;
		max-width: 75px;
		position: relative;
	  }
	  
	  #ecom-modal-second__window #ecom-modal__content #ecom_options {
		text-align: center;
	  }
	  
	  #ecom-modal-second__window #options_scroll_display{
		position: absolute;
		padding: 5px;
		border-radius: 25px;
		background-color: #3498db;
		font-size: 12px;
		left: 50%;
		bottom: 20px;
		border: 1px solid rgba(0,0,0,0.2);
		z-index: 2;
		color: #FFF;
		transform: translateX(-50%);
	  }
	  
	  #second_window_back_btn {
		float: left;
		padding-top: 20px;
	  }
	  
	  #ecom-modal-second__window .ecom-modal__footer .ecom-product__quantity-field  {
		width: 75px;
	  }
	  
	  #loader {
		position: relative;
		left: 50%;
		top: 50%;
		z-index: 1;
		width: 120px;
		height: 120px;
		margin: 25px 0 0 -75px;
		border: 16px solid #f3f3f3;
		border-radius: 50%;
		border-top: 16px solid #3498db;
		-webkit-animation: spin 2s linear infinite;
		animation: spin 2s linear infinite;
	  }
	  
	  @-webkit-keyframes spin {
		0% { -webkit-transform: rotate(0deg); }
		100% { -webkit-transform: rotate(360deg); }
	  }
	  
	  @keyframes spin {
		0% { transform: rotate(0deg); }
		100% { transform: rotate(360deg); }
	  }
	  
	  @-webkit-keyframes animatebottom {
		from { bottom:-100px; opacity:0 }
		to { bottom:0px; opacity:1 }
	  }
	  
	  @keyframes animatebottom {
		from{ bottom:-100px; opacity:0 }
		to{ bottom:0; opacity:1 }
	  }
	  
	  
	  /*Nate Styles... or whatever*/
	  
	  #ecom-modal-second__window .ecom-control-group__item{
		display: inline-block;
		width: initial;
	  }
	  
	  #ecom-modal-second__window .ecom-product__control-label{
		float: left;
		margin: 15px 10px 0 0 ;
	  }
	  
	  #ecom-modal-second__window .ecom-modal__footer .ecom-product__quantity-field {
		width: 75px;
		float: right;
		padding: 18px 18px 17px;
	  }
	  
	  #ecom-modal-second__window .scroll_visual::after{
		content: '';
		display: block;
		background: linear-gradient(to bottom, rgba(255,255,255,0) 0%,rgba(0,0,0,.4) 100%);
		width: 100%;
		height: 60px;
		position: absolute;
		left: 0;
		bottom: 0;
		transition: all .4s ease-in-out;
	  }
	  
	  #ecom-modal-second__window .ecom-upsell__products-list{
		position: relative;
	  }
	  
	  @media screen and (max-width: 550px){
	  
		#ecom-modal-second__window .ecom-control-group__item{
		  width: 100%;
		  margin-bottom: 10px;
		}
	  
	  }
	  
	  
	  /* =============================================================================
		Responsive Slider Styles
	  ============================================================================= */
	  
	  .ecom-modal__slider{
		position: relative;
	  }
	  
	  .ecom-modal__slider:after{
		display: none;
		width: 200px;
		height: 100%;
		position: absolute;
		content: '';
		background: linear-gradient(to right, rgba(255,255,255,0) 0%,rgba(255,255,255,1) 100%);
		top: 0px;
		right: -15px;
		opacity: 1;
		pointer-events: none;
		z-index: 10;
		transition: opacity .4s ease-in-out;
	  }
	  
	  @media screen and (max-width: 699px){
		.ecom-modal__slider:after{
		  display: none;
		}
	  }
	  
	  .ecom-modal__slider .flickity-prev-next-button{
		z-index: 11;
		background: #adadad !important;
		width: 32px !important;
		height: 32px !important;
		opacity: .85 !important;
		transition: opacity .4s ease-in-out;
		top: 40% !important;
	  }
	  
	  .ecom-modal__slider .flickity-prev-next-button svg{
		width: 40% !important;
		left: 29% !important;
	  }
	  
	  .ecom-modal__slider .flickity-prev-next-button svg *{
		fill: #fff !important;
	  }
	  
	  .ecom-modal__slider .flickity-prev-next-button:disabled{
		opacity: 0 !important;
	  }
	  
	  .ecom-modal__slider .next{
		right: -5px !important;
	  }
	  
	  .ecom-modal__slider .previous{
		left: -5px !important;
	  }
	  
	  .ecom-modal__slider .flickity-page-dots{
		bottom: -15px !important;
	  }
	  
	  @media screen and (max-width: 499px){
		.ecom-modal__window{
		  width: calc(100% - 40px);
		  height: calc(100% - 40px);
		  top: 20px !important;
		  left: 20px;
		}
	  }
	  
	  
	  
	  /* Options second window */
	  
	  #ecom-modal-second__window .ecom_option {
		  display: flex;
		  margin-bottom: 16px;
		  text-align: left;
		  padding: 0 40px;
	  }
	  
	  @media screen and (max-width: 600px){
		#ecom-modal-second__window .ecom_option {
			padding: 0 15px;
		}
	  }
	  
	  #ecom-modal-second__window .ecom_option_title {
		  flex: 1 0 35%;
		  padding-right: 20px;
	  }
	  
	  #ecom-modal-second__window .ecom_option_element {
		  flex: 1 0 65%;
		  flex-wrap: wrap;
	  }
	  
	  #ecom-modal-second__window .ecom_option_dropdown label {
		  display: flex;
		  width: 100%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_element label {
		  display: block;
		  margin-bottom: 10px;
	  }
	  
	  #ecom-modal-second__window .ecom_option_element select {
		  width: 100%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_product_title {
		  display: none;
	  }
	  
	  #ecom-modal-second__window .ecom_option_textbox label {
		  width: 100%;
		  display: flex;
	  }
	  
	  
	  
	  #ecom-modal-second__window .ecom_option_textbox .ecom_option_element {
		  display: block;
		  width: 65%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_textbox .ecom_option_element input {
		  width: 100%;
	  }
	  
	  
	  
	  #ecom-modal-second__window .ecom_option_radio input {
		  margin-right: 8px;
	  }
	  
	  #ecom-modal-second__window .ecom_option_title {
		  display: block;
		  width: 35%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_value {
		  margin-right: 0;
		  display: block;
		  width: 100%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_value label {
		  display: flex;
	  }
	  
	  #ecom-modal-second__window .with-options {
		  font-size: 16px;
		  padding: 0px;
		  margin: 0;
		  width: 100%;
		  border: 0;
		  overflow-x: hidden;
	  }
	  
	  
	  #second_window_back_btn {
		  float: left;
		  padding-top: 14px;
		}
	  
	  #ecom-modal-second__window .scroll_visual::after {
		  background: linear-gradient(to bottom, rgba(255,255,255,0) 0%,rgba(0,0,0,.14) 100%);
	  }
	  
	  #ecom-modal-second__window .ecom-modal__content {
		  padding: 0;
	  }
	  
	  #ecom-modal-second__window .ecom-upsell__intro {
		  padding: 10px 30px 10px;
	  }
	  
	  #ecom-modal-second__window .ecom-upsell__intro .ecom-product__title {
		  font-size: 26px;
	  }
	  
	  #ecom-modal-second__window .ecom-upsell__intro .ecom-product__price {
		  font-size: 16px;
	  }
	  
	  #ecom-modal-second__window .ecom-upsell__actions {
		  padding: 20px 30px
	  ;
	  }
	  
	  #ecom-modal-second__window .ecom-upsell__actions .ecom-upsell__button--primary {
		  padding: 10px 20px
	  ;
	  }
	  
	  #ecom-modal-second__window .ecom-upsell__products-list {
		  padding: 30px 0px 0;
		  margin: 0;
		  border-top: 1px solid rgba(0,0,0,.16);
		  border-bottom: 1px solid rgba(0,0,0,.16);
	  }
	  
	  #ecom-modal-second__window #ecom-modal__content .ecom-product__info {
		  display: flex;
		  margin: 0;
	  }
	  #ecom-modal-second__window .ecom_option_checkbox label{
		display: flex;
		width: 100%;
	  }
	  #ecom-modal-second__window .ecom_option_checkbox .ecom_option_title{
	  
	  }
	  
	  #ecom-modal-second__window .ecom_option_checkbox .ecom_option_element {
		  width: 35px;
		  flex: 1 0 35px;
	  }
	  
	  
	  @media screen and (max-width: 600px){
		#ecom-modal-second__window .ecom_option_checkbox .ecom_option_title {
			flex: 1 0 70%;
		}
	  
		#ecom-modal-second__window .ecom-upsell__products-list{
		  padding: 10px;
		}
	  }
	  
	  #ecom-modal-second__window .ecom_option_value_price::before {
		  content: '+';
	  }
	  
	  #ecom-modal-second__window #options_scroll_display {
		  border: 0;
		  padding: 5px 12px;
		  box-shadow: 0px 4px 8px rgba(0,0,0,.1);
		  /*animation: jiggle 3s linear infinite;*/
		  bottom: 12px;
	  }
	  
	  #ecom-upsell__button--primary {
		  padding-top: 14px;
	  }
	  
	  #ecom-modal-second__window .ecom_option_swatch .ecom_option_element{
		  display: flex;
		  flex-wrap: wrap;
	  }
	  
	  #ecom-modal-second__window .ecom_option_swatch .ecom_option_value{
		  display: block;
		  width: 40px;
			margin-right: 10px;
			margin-bottom: 10px;
	  
	  }
	  
	  #ecom-modal-second__window .ecom_option_dropdownmulti label{
		  width: 100%;
		  display: flex;
	  }
	  
	  
	  /* Product options: Textarea */
	  
	  #ecom-modal-second__window .ecom_option_textarea label {
		  width: 100%;
		  display: flex;
	  }
	  
	  #ecom-modal-second__window .ecom_option_textarea .ecom_option_title {
		  flex: 1 0 35%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_textarea .ecom_option_element {
		  flex: 1 0 65%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_textarea textarea {
		  width: 100%;
		  max-width: 100%;
	  }
	  
	  
	  /* Product options: Textbox multi */
	  
	  #ecom-modal-second__window .ecom_option_textboxmulti{
		flex-wrap: wrap;
	  }
	  
	  #ecom-modal-second__window .ecom_option_textboxmulti .ecom_option_title{
		  flex: 1 0 35%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_textboxmulti .ecom_option_element{
		  flex: 1 0 65%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_textboxmulti label{
		flex-wrap: wrap;
	  }
	  
	  #ecom-modal-second__window .ecom_option_textboxmulti .ecom_help_text{
		flex: 1 0 100%;
		padding-left: 35%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_textboxmulti .ecom_option_value_title{
		display: block;
		flex: 1 0 100%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_textboxmulti .ecom_option_value_element{
		display: block;
		flex: 1 0 100%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_textboxmulti .ecom_option_value_element input{
		width: 100%;
	  }
	  
	  
	  /* Product options: Number */
	  
	  #ecom-modal-second__window .ecom_option_number label{
		display: flex;
		width: 100%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_number input{
		width: 100%;
	  }
	  
	  
	  /* Product options: Email */
	  
	  #ecom-modal-second__window .ecom_option_email label{
		display: flex;
		width: 100%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_email input{
		width: 100%;
	  }
	  
	  
	  
	  /* Product options: Color */
	  
	  #ecom-modal-second__window .ecom_option_color label{
		display: flex;
		width: 100%;
	  }
	  
	  
	  /* Product options: Date */
	  
	  #ecom-modal-second__window .ecom_option_date label{
		display: flex;
		width: 100%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_date input{
		width: 100%;
	  }
	  
	  
	  /* Product options: Telephone */
	  
	  #ecom-modal-second__window .ecom_option_telephone label{
		display: flex;
		width: 100%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_telephone input{
		width: 100%;
	  }
	  
	  
	  /* ecom options file upload */
	  
	  #ecom-modal-second__window .ecom_option_uploadfile label{
		display: flex;
		width: 100%;
	  }
	  
	  #ecom-modal-second__window .ecom_option_uploadfile input{
		width: 100%;
	  }
	  
	  @keyframes jiggle {
		0% {
		  transform: translate(-50%,0);
		}
		65% {
		  transform: translate(-50%,0);
		}
		70% {
		  transform: translate(-50%,5px);
		}
		75% {
		  transform: translate(-50%,-4px);
		}
		80% {
		  transform: translate(-50%,3px);
		}
		85% {
		  transform: translate(-50%,-2px);
		}
		90% {
		  transform: translate(-50%,1px);
		}
		95% {
		  transform: translate(-50%, 0px);
		}
	  }
	  
	  /* =============================================================================
		THIRD PARTY APPS STYLES
	  ============================================================================= */
	  
	  #ecom-upsell-modal-window .yotpo{
		display: inline-block;
	  }
	  
	  /* =============================================================================
		MODAL STYLES
	  ============================================================================= */
	  .ecom-modal {
		background-color: rgba(0,0,0,0.8); }
	  
		.ecom-modal__window {
		  box-shadow: 0px 5px 15px rgba(0,0,0,0.2);
		  border-radius: 5px; }
	  
		.ecom-modal__header {
		  font-size: 12px;
		  color: rgba(0,0,0,0.4);
		  text-transform: uppercase;
		  border-bottom: 1px solid rgba(0,0,0,0.1); }
	  
		.ecom-modal__footer {
		  border-top: 1px solid rgba(0,0,0,0.1);
		  background-color: rgba(0,0,0,0.02); }
	  
	  
	  /* =============================================================================
		UPSELL STYLES
	  ============================================================================= */
	  .ecom-upsell {}
	  
		.ecom-upsell__intro {
		  font-size: 14px;
		  line-height: 1.5; }
	  
		.ecom-upsell__intro-heading {
		  font-size: 24px; }
	  
	  .ecom-upsell__button--primary:link,
	  .ecom-upsell__button--primary:visited,
	  .ecom-upsell__button--primary:hover,
	  .ecom-upsell__button--primary:active,
	  .ecom-upsell__button--primary:focus {
		color: inherit;
		text-decoration: none; }
	  
	  .ecom-upsell__button--primary {
		display: inline-block;
		padding: 15px 25px;
		border: 1px solid rgba(0,0,0,0.3);
		background-color: rgba(0,0,0,0.03);
		border-radius: 2px; }
	  
		.ecom-upsell__button--primary:hover {
		  background-color: rgba(0,0,0,0);
		  border-color: rgba(0,0,0,0.2); }
	  
		.ecom-upsell__button--primary:active {
		  background-color: rgba(0,0,0,0.05);
		  border-color: rgba(0,0,0,0.4); }
	  
	  .ecom-upsell__button--secondary {
		margin-right: 20px;
		font-size: 90%; }
	  
	  
	  /* =============================================================================
		PRODUCT STYLES
	  ============================================================================= */
	  .ecom-product {
		max-width: auto;
		font-size: 13px;
		background-color: rgba(255,255,255, 0.5);
		border: 1px solid rgba(0,0,0,0.2);
		padding: 10px; }
	  
		.ecom-product.ecom-grid {
		  margin-left: 0;
		  margin-right: 0; }
	  
		.ecom-product__info,
		.ecom-product__variants,
		.ecom-product__actions {
		  margin: 10px 0; }
	  
		.ecom-product__pricing {
		  margin: 15px 0; }
	  
		.ecom-product__title {
		  font-size: 15px;
		  font-weight: ecom;
		  margin-bottom: 5px; }
	  
		.ecom-product__description {
		  opacity: 0.6;
		  margin-bottom: 10px; }
	  
	  .ecom-product__price {
		display: inline;
		margin: 0px 3px;
		font-weight: ecom;
		font-size: 150%; }
	  
	  .ecom-product__message {
		opacity: 0.4; }
	  
		.ecom-product__price--deleted {
		  font-weight: normal;
		  font-size: 90%;
		  opacity: 0.4;
		  text-decoration: line-through; }
	  
	  .ecom-control-group {
		box-sizing: border-box;
		display: block;
		width: 100%; }
	  
	  .ecom-control-group__item {
		display: block;
		vertical-align: bottom;
		text-align: left;
		width: 100%; }
	  
	  .ecom-product__control {
		padding: 15px;
		display: block;
		border-radius: 2px;
		line-height: 1;
		color: inherit;
		border: 1px solid rgba(0,0,0,0.3);
		background-color: rgba(0,0,0,0.03);
		-webkit-appearance: none;
		-moz-appearance: none;
		appearance: none; }
	  
	  .ecom-product__control-label {
		display: inline-block;
		margin-bottom: 3px; }
	  
	  .ecom-product__variant-selector {
		padding-right: 36px;
		background-image: url('data:image/svg+xml;utf-8,<svg xmlns='http://www.w3.org/2000/svg' width='26' height='16' viewBox='0 0 26 16'><path fill='CurrentColor' d='M8.02682426,8.99999532 L11.3523243,8.99999532 C11.7765243,8.99999532 12.0080243,9.49499532 11.7360243,9.82059532 L10.2242243,11.6301953 L8.41062426,13.8032953 C8.31564065,13.9171173 8.17504521,13.9829213 8.02679765,13.9829406 C7.87855009,13.9829599 7.73793751,13.9171926 7.64292426,13.8033953 L5.82942426,11.6315953 L4.31712426,9.82049532 C4.04532426,9.49489532 4.27682426,8.99999532 4.70102426,8.99999532 L8.02702426,8.99999532 L8.02682426,8.99999532 Z M8.02652426,6.98299532 L4.70102426,6.98299532 C4.27682426,6.98299532 4.04532426,6.48799532 4.31732426,6.16229532 L5.82902426,4.35269532 L7.64262426,2.17969532 C7.73759304,2.06586091 7.8781799,2.00003864 8.02642747,2.00000002 C8.17467503,1.9999614 8.31529617,2.06571041 8.41032426,2.17949532 L10.2238243,4.35129532 L11.7361243,6.16239532 C12.0079243,6.48799532 11.7764243,6.98289532 11.3523243,6.98289532 L8.02632426,6.98289532 L8.02652426,6.98299532 Z'/></svg>');
		background-repeat: no-repeat;
		background-position: right center;
		height: auto;
	  }
	  
		.ecom-product__actions {
		  margin-bottom: 0; }
	  
		.ecom-product__button,
		  .ecom-product__button:link,
		  .ecom-product__button:visited,
		  .ecom_product__button:hover,
		  .ecom_product__button:active,
		  .ecom_product__button:focus {
			text-decoration: none;
			color: inherit; }
	  
		  .ecom-product__button:hover {
			background-color: rgba(0,0,0,0);
			border-color: rgba(0,0,0,0.2); }
	  
		  .ecom-product__button:active {
			background-color: rgba(0,0,0,0.05);
			border-color: rgba(0,0,0,0.4); }
	  
	  .ecom-product__quantity-field{
		width: 100%;
	  }
	  /* custom css */
	  #demo{
		  font-size: 30px;
	  }
	  
  	ul.list_fonts{
      background: #fff;
      border: solid 1px #eaeaea;
      width: 340px;
      padding: 5px;
      margin: 0px;
      display: none;
      text-align: left;
    }
	ul.list_fonts li{list-style:none;font-size:14px;cursor:pointer;}
	#display_fonts{
      background: #fff;
      border: solid 1px #eaeaea;
      width: 340px;
      padding: 5px;
      margin: 0px;
      cursor: pointer;
      text-align: left;
      
    }
	  
	  </style>
	<div id='ecom-upsell-modal-window'>
	   <div id='ecom-modal' class='ecom-modal ecom-modal--' >
		  <div id='ecom-modal__window' class='ecom-modal__window'>
			 <div id='ecom-modal-first__window' style=''>
               <h4 style='text-align: center;'>We embroider exactly you entered including punctuation</h4>
				<a id='ecom-modal__btn-close' class='ecom-modal__btn ecom-modal__btn-close' aria-describedby='a11y-external-message'>x</a>
               
			   <p id='embroidery_text_front' style='display: none;position: absolute;top: 185px;left: 204px;z-index: 9999;'>Texting 1</p>
               <p id='embroidery_text_front_second' style='display: none;position: absolute;top: 200px;left: 204px;z-index: 9999;'>Texting 1</p>
               
               
               <p id='embroidery_text_back' style='display: none;position: absolute;top: 185px;left: 204px;z-index: 9999;'>Texting 1</p>
               <p id='embroidery_text_back_second' style='display: none;position: absolute;top: 200px;left: 204px;z-index: 9999;'>Texting 1</p>
				
               <div class='ecom-modal__footer ecom-upsell__actions' style='background-color: #fff;'>
                 
                <div class='col-md-6'>
                    <img id='custom_image_front' style='display:none;' src='https://cdn.shopify.com/s/files/1/0067/6941/0113/products/Hooded_Kids_PURPLEs_Robe_1024x1024@2x.jpg?v=1536049876'>
                  <img id='custom_image_back' style='display:none;' src='https://cdn.shopify.com/s/files/1/0067/6941/0113/products/Hooded_Kids_PURPLEs_Robe_1024x1024@2x.jpg?v=1536049876'>
                </div>
                <div class='col-md-6'>
                  <div class='form-group'>
                    <h4 class='changing_price' style='display:none;'>Rs: 15.00</h4>
                    <label class='form-group'>Select Embroidery Type</label>
                    <select id='personlize_select' class='form-control' style='height:50px;'>
                      <option disabled selected>Personalize</option>
                      <option value='front_embroidery'>Front Embroidery</option>
                      <option value='back_embroidery'>Back Embroidery</option>
                      <option value='front_back_embroidery'>Front & Back Embroidery</option>
                    </select>
                  </div>
                 </div>
                <div class='col-md-6 front_embroidery' style='display:none;'>
<!--                   <h2>Front Embroidery</h2> -->
    			  <br>
                  <div class='form-group'>
                    <input type='text' maxlength='11' name='front_only' class='form-control' id='from_embroidery' placeholder='Enter Text for Front Embroidery (+$7.95)'>
                  </div>
                  <div class='form-group'>
                    <input type='text' maxlength='11' name='second_front_only' class='form-control' id='from_second_embroidery' placeholder='Enter Text for Second Front Embroidery (+$4)'>
                  </div>
                </div>  

                <div class='col-md-6 back_embroidery' style='display:none;'>
<!--                   <h2>Back Embroidery</h2> -->
    			  <br>
                  <div class='form-group'>
                    <input type='text' maxlength='11' name='back_only' class='form-control' id='from_back_embroidery' placeholder='Enter Text for Back Embroidery (+$12.95)'>
                  </div>
                  <div class='form-group'>
                    <input type='text' maxlength='11' name='second_back_only' class='form-control' id='from_second_back_embroidery' placeholder='Enter Text for Second Back Embroidery (+$6.00)'>
                  </div>
                </div>
                  
                <div class='col-md-6 front_back_embroidery' style='display:none;'>
<!--                   <h2>Front & Back Embroidery</h2> -->
    			  <br>
                  <div class='form-group'>
                    <input type='text' maxlength='11' name='front_both' class='form-control' id='from_front2_embroidery' placeholder='Enter Text for Front Embroidery'>
                  </div>
                  <div class='form-group'>
                    <input type='text' maxlength='11' name='back_both' class='form-control' id='from_back2_embroidery' placeholder='Enter Text for Back Embroidery'>
                  </div>
                </div>
                  <div class='col-md-6' style='display:none'>
                    <div class='form-group'>
                      <input type='text' name='color' id='color_of_embroidery' class='form-control' placeholder='Choose Text Color'>
                    </div>
                  </div>
                 <div class='col-md-6 show_select' style='display:none'>
                   <div class='form-group'>
                     <label class='form-group'>Select Font Style:</label>
                   		{% include 'swatch-lip' with 'Ivory, Pink, Blue, Olive, black.png' %}
                   </div>
                 </div>
                  <div class='col-md-6 show_select' style='display:none'>
                    <div class='form-group'>
                    	  <ul id='display_fonts'></ul>
                          <ul class='list_fonts'>
                            <li style='font-family:Arial'>Arial</li>
                            <li style='font-family:Verdana'>Verdana</li>
                            <li style='font-family:Fearless'>Fearless</li>
                            <li style='font-family:Anton'>Anton</li>
                            <li style='font-family:Baloo Tammudu'>Baloo Tammudu</li>
                            <li style='font-family:Dancing Script'>Dancing Script</li>
                            <li style='font-family:Fjalla One'>Fjalla One</li>
                            <li style='font-family:Gamja Flower'>Gamja Flower</li>
                            <li style='font-family:Lato'>Lato</li>
                            <li style='font-family:Lobster'>Lobster</li>
                            <li style='font-family:Montserrat'>Montserrat</li>
                            <li style='font-family:Mukta'>Mukta</li>
                            <li style='font-family:Noto Serif+JP'>Noto Serif JP</li>
                            <li style='font-family:Open Sans'>Open Sans</li>
                            <li style='font-family:Oswald'>Oswald</li>
                            <li style='font-family:Roboto'>Roboto</li>
                            <li style='font-family:Roboto Condensed'>Roboto Condensed</li>
                            <li style='font-family:Ruslan Display'>Ruslan Display</li>
                            <li style='font-family:Shadows Into Light'>Shadows Into Light</li>
                            <li style='font-family:Source Sans Pro'>Source Sans+Pro</li>
                          </ul>
                    </div>
                  </div>
                  <button type='button' style='display:none' class='btn btn-light Custom add_to_cart_custom_button show_select' >Add to Cart</button>
                  
				</div>
			 </div>
			 
		  </div>
	   </div>
	</div>
<script>
  $(document).ready(function(){
    
    var horizantal_pos = $('#top_pos');
    var vertical_pos = $('#left_pos');
    var size_width = $('#size_width');
    
    horizantal_pos.on('change', function(pos){
      pos = horizantal_pos.val();
    	$('#embroidery_text_front').css('top', pos+'px');
        $('#embroidery_text_back').css('top', pos+'px');
    });
    
    vertical_pos.on('change', function(pos){
      pos = vertical_pos.val();
    	$('#embroidery_text_front').css('left', pos+'px');
      $('#embroidery_text_back').css('left', pos+'px');
    });
    
    size_width.on('change', function(pos){
      pos = size_width.val();
    	$('#embroidery_text_front').css('width', pos+'px');
      $('#embroidery_text_back').css('width', pos+'px');
    });
    
    $('ul.list_fonts li:first').clone().appendTo('#display_fonts');
  
    $('ul.list_fonts li').on('click',function(){
      $('#display_fonts').html('');
      $(this).clone().appendTo('#display_fonts');
      var font_family = $(this).text();
      $('#embroidery_text_front').css('font-family',font_family); 
      $('#embroidery_text_back').css('font-family',font_family);
      $('#font_of_embroidery').val(font_family);
      $('ul.list_fonts').slideUp();   
    });
    
    $('#display_fonts').click(function(){
    	$('ul.list_fonts').slideToggle();
    });
    
    $('.custom_color_awatch').click(function(){
    	var color = $(this).val();
      	$('#embroidery_text_front').css('color', color);
        $('#embroidery_text_back').css('color', color);
      
      	$('#embroidery_text_front_second').css('color', color);
        $('#embroidery_text_back_second').css('color', color);
      
        $('#color_of_embroidery').val(color);
    });

    
  });
</script>";
        $postData = [
                            "key" => "snippets/personalisation-popup.liquid",
                            "value" => $view
					];
		$themes_info = DB::Table('themesdata')->where('shopId', session('shopifyId') )->where('themeId', $theme_id)->first();
		if($themes_info)
		{
			return true;
		}else{
			$data2 = $this->shopify->setShopUrl($shopUrl)->setAccessToken($accessToken)->put('/admin/themes/'.$theme_id.'/assets.json',[ 'asset' => $postData ]);
			$template_name = "snippets/personalisation-popup.liquid";
			$theme_id = $data2['theme_id'];
			$id = DB::table('themesdata')->insertGetId(
				['key' => $template_name, 'value' => $view, 'themeId' => $theme_id , 'shopId' => session('shopifyId'), 'disable' => '1']
			);
			$this->include_template_files();
		}


    }

}