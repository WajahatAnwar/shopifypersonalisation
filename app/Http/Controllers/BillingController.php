<?php

namespace App\Http\Controllers;

use DB;
use Log;
use App\Objects\ScriptTag;
use Illuminate\Http\Request;
use App\Objects\Billing\Charge;
use App\Shop;
use Oseintow\Shopify\Shopify;
use App\Objects\ShopifyWebhook;
use Oseintow\Shopify\Exceptions\ShopifyApiException;

class BillingController extends Controller
{
    protected $shopify;

    function __construct(Shopify $shopify)
    {
    	$this->shopify = $shopify;
    }

    /**
     * @param Request $request
     */
    public function charge(Request $request)
    {
        $newCharge = new Charge();
        try{
            $response = $newCharge->charge();
            $confirmationUrl = $response['confirmation_url'];
            return view('billing.escape_iframe' , ['url' => $confirmationUrl]);
        }catch (ShopifyApiException $e){
            Log::error('Error occured while creating charge for ' . session('myshopifyDomain') . ' ' . $e->getMessage());
            abort(500);
        }

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function callback(Request $request)
    {
        $chargeId = $request->charge_id;
        if(!is_null($chargeId))
        {
            try{
                $charge = new Charge();
                $chargeStatus = $charge->status($chargeId);
                // dd($chargeStatus);
                if($chargeStatus['status'] === 'active' || $chargeStatus['status'] === 'accepted')
                {

                    $activateChargeResponse = $charge->activate($chargeStatus);
                    // dd($activateChargeResponse);
                    if($activateChargeResponse['status'] === 'active')
                    {
                        ScriptTag::register();
                        $shop = Shop::where('myshopify_domain' , session('myshopifyDomain'))->first();
                        // dd($shop);
                        $shop->is_premium = true;
                        $shop->charge_status = 'active';
                        $shop->save();
                        $shop2 = Shop::where('myshopify_domain' , session('myshopifyDomain'))->first();
                        // dd($shop2);
                        session(['is_premium_shop' => true]);
                        // dd($shopf);
                        // $this->create_template();
                        return redirect("https://".session('myshopifyDomain')."/admin/apps");
                    }else{
                        Log::error('Their status was not active ' . session('myshopifyDomain'));
                        abort(500);
                    }
                }else{
                    Log::error('They did not accept the charge somehow ' . session('myshopifyDomain'));
                    dd('fuck');
                    // return redirect()->route('billing.declined');
                }
            }catch (ShopifyApiException $e){
                Log::error('Error occured while activating charge for ' . session('myshopifyDomain') . ' ' . $e->getMessage());
                dd('fuck2');
                // return redirect()->route('billing.declined');
            }
        }else{
            abort(500);
        }

    }

    public function declined()
    {
        return view('billing.declined');
    }

}
