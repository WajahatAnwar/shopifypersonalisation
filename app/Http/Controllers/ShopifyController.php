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
    			return view('home.index' , ['shop' => $shop , 'settings' => $shop->settings, 'success' => '2']);
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
		$view .= "{% include 'ecom-popup' %}{% assign event_identifier = product.metafields.event_identify %}
		<input type='hidden' value='{{ event_identifier['event'] }}' id='event_identifier' class='event_identifier' />

		<input type='hidden' value='{{ product.variants.first.id }}' id='product_id' class='product' />
		<input type='hidden' value='{{ product.id }}' id='product_id_real' class='product' />
		<p id='demo1'></p>";
		
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
	  
	  
	  </style>
	<div id='ecom-upsell-modal-window'>
	   <div id='ecom-modal' class='ecom-modal ecom-modal--animated'>
		  <div id='ecom-modal__window' class='ecom-modal__window'>
			 <div id='ecom-modal-first__window' style=''>
				<a id='ecom-modal__btn-close' class='ecom-modal__btn ecom-modal__btn-close'>x</a>
				<div id='ecom-modal__content' class='ecom-modal__content ecom-upsell'>
				   
				   <div class='ecom-upsell__triger-product-container'>
					  <div class='ecom-upsell__triger-image-container' style='background: url(https://cdn.shopify.com/s/files/1/0062/9817/3498/products/camper-car-fir-trees-24698.jpg?v=1527281716);background-size: contain;background-repeat: no-repeat;background-position: center; width:85px; height:85px;'>
					  </div>
					  <div class='ecom-product__info'>
						 <div class='ecom-product__quantity'>You added 1</div>
						 <div class='ecom-product__title'>T-Shirt</div>
						 <div class='ecom-product__variant'></div>
						 <div class='ecom-product__pricing' style='height: 29px;'>
							<div class='ecom-product__price'><del class='ecom-product__price--deleted money'></del></div>
							<div class='ecom-product__price money'>Rs.1,213.00</div>
						 </div>
					  </div>
				   </div>
				   <div class='ecom-modal__slider'>
					  <div id='product_list' class='ecom-grid ecom-upsell__products-list'>
						 <article id='prod_id_1329330913338_0' class='ecom-product ecom-grid offer_id_218652' data-ecom-component-id='upsell_for-upsell'>
							<div class='ecom-product__image-container ecom-grid__column ecom-grid__column--half'>
							   <img src='//upsells.ecomapps.net/assets/no-image.png' alt='' class='ecom-product__image'>
							</div>
							<div class='ecom-product__details ecom-grid__column ecom-grid__column--half'>
							   <div class='ecom-product__info'>
								  <div class='ecom-product__title' style='font-size: 26px; height: 22px;'>For upsell</div>
							   </div>
							   <div class='qty_container' style='height: 0px;'>
								  <div class='ecom-control-group__item ecom-product__quantity qty_input_container' style='display:none;'>
									 <label class='ecom-product__control-label' for='qty_input' title='Quantity'>Qty :</label>
									 <input type='number' class='ecom-product__control ecom-product__quantity-field qty_input' value='1' min='1'>
								  </div>
							   </div>
							   <div class='ecom-product__pricing' style='height: 29px;'>
								  <div class='ecom-product__price'><del class='ecom-product__price--deleted money'>Rs.1,560.00</del></div>
								  <div class='ecom-product__price current_price money'>Rs.0.00</div>
								  <div class='ecom-product__message limit_disclaimer' style='color: red;'>Limited Time Offer</div>
							   </div>
                              <div class='ecom-upsell__intro1'>
                                <p id='demo'></p>
							  </div>
							  <div class='ecom-upsell__intro1'>
                                <p>Available Stock: <span class='fake_stock'></span></p>
                              </div>
							   <div class='ecom-product__actions ecom-product-options__actions' style='display: none;'>
								  <a href='#add-to-cart' class='ecom_options_btn ecom-product__control ecom-product__button ecom-product__button--primary'>Customize and Add to Cart</a>
							   </div>
							   <div class='ecom-product__actions ecom-product-upsell__actions'>
								  <a href='#add-to-cart' data-ecom-component-id='' class='add-to-cart ecom-product__control ecom-product__button ecom-product__button--primary standard_primary' added='false'>Take This Offer</a>
							   </div>
							</div>
						 </article>
					  </div>
				   </div>
				</div>
				<div class='ecom-modal__footer ecom-upsell__actions'>
				   <a href='#' class='ecom-upsell__button ecom-upsell__button--secondary' data-ecom-component-id='upsell_no_thanks'>No Thanks</a>
				   <a href='#' class='ecom-upsell__button ecom-upsell__button--primary' data-ecom-component-id='upsell_continue'>Continue Without Offer</a>
				</div>
			 </div>
			 <div id='ecom-modal-second__window' style='display:none'>
			 </div>
		  </div>
	   </div>
	</div>
	<script>
	$(document).ready(function(){
		var dating= document.getElementById('timeanddate').value;
        if(dating != '')
		{
			var countDownDate = new Date(dating).getTime();
		}else
		{
			var dating2= document.getElementById('timer2').value;
			var countDownDate = new Date(dating2).getTime();
		}
		// Set the date we're counting down to
		
	
		// Update the count down every 1 second
		var x = setInterval(function() {
	
		// Get todays date and time
		var now = new Date().getTime();
		
		// Find the distance between now an the count down date
		var distance = countDownDate - now;
		
		// Time calculations for days, hours, minutes and seconds
		var days = Math.floor(distance / (1000 * 60 * 60 * 24));
		var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
		var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
		var seconds = Math.floor((distance % (1000 * 60)) / 1000);
		
		// Output the result in an element with id='demo'
		document.getElementById('demo').innerHTML = days + 'd ' + hours + 'h '
		+ minutes + 'm ' + seconds + 's ';
		document.getElementById('demo1').innerHTML = days + 'd ' + hours + 'h '
		+ minutes + 'm ' + seconds + 's ';
		
		// If the count down is over, write some text 
		if (distance < 0) {
			clearInterval(x);
			document.getElementById('demo').innerHTML = 'EXPIRED';
			document.getElementById('demo1').innerHTML = 'EXPIRED';
		}
		}, 1000);
      
      $('#demo1').insertBefore ('.btn--wide:contains(Add to Cart)'); 
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