<?php

namespace App\Http\Controllers;
use App\Purchase;
use App\History;
use App\User;
use Illuminate\Http\Request;

class ToyyibpayController extends Controller
{
    //Toyyib Pay Settings Sandbox
    //private $payment_url = 'https://dev.toyyibpay.com/index.php/api/';
    //private $userSecretKey = 'lmyctl1r-6wmc-nq7a-b19z-rj5p5pejgsus'; 
    //private $categorycode = 'o4ylbpyk';
    //private $envtoyyib = 'https://dev.toyyibpay.com/';

    //Toyyib Pay Settings Optimal
    private $payment_url = 'https://toyyibpay.com/index.php/api/';
    private $userSecretKey = 'q9iw91gg-94v8-jip3-qx68-8m9ntkx0k1p0';
    private $categorycode = '3lcvd33k';
    private $envtoyyib = 'https://toyyibpay.com/';

    //Local or live environment
    //private $env = 'http://localhost:8000/';
    private $env = 'http://waste2wealth.my/engine/testbillplz/public/';

    public function index(){
        return response()->json(['status'=>'success','value'=>'engine toyyibpay']);
    }

    public function createBill(Request $request){

        $user_id = $request->input('user_id');
        $netprice = $request->input('netprice');
        $quantitytoken = $request->input('quantitytoken');

        $user = User::where('user_id',$user_id)->first();

        if($user == null){
            return response()->json(['status'=>'failed','value'=>'user not exist']);
        } else {

            if($user->user_type == 'company'){
                $finalname = $user->companyname;
            } else {
                $finalname = $user->user_fname;
            }

            $some_data = array(
                'userSecretKey'=>$this->userSecretKey,
                'categoryCode'=>$this->categorycode,
                'billName'=>'Topup token '.$quantitytoken,
                'billDescription'=>'Purchase token '.$quantitytoken,
                'billPriceSetting'=>1,
                'billPayorInfo'=>1,
                'billAmount'=>($netprice*100), //Amount in cent. e.g. 100 = RM1
                'billReturnUrl'=> $this->env.'toyyibpay/returnUrl',
                'billCallbackUrl'=>'http://localhost:8000/api/paymentCallback', 
                'billExternalReferenceNo' => 'W2W-TOKEN2-01',
                'billTo'=>$finalname,
                'billEmail'=>$user->user_email,
                'billPhone'=>$user->user_contact,
                'billSplitPayment'=>0,
                'billSplitPaymentArgs'=>'',
                'billPaymentChannel'=>'2',
                // 'billContentEmail'=>'Thank you for purchasing our token RM20',
                'billChargeToCustomer'=>1
                // billChargeToCustomer - [OPTIONAL] Below are the values available :
                // 1. Leave blank to set charges for both FPX and Credit Card on bill owner.
                // 2. Set "0" to charge FPX to customer, Credit Card to bill owner.
                // 3. Set "1" to charge FPX bill owner, Credit Card to customer.
                // 4. Set "2" to charge both FPX and Credit Card to customer.
              ); 
    
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_URL, $this->payment_url .'createBill');  
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $some_data);
            
                $result = curl_exec($curl);
                $info = curl_getinfo($curl);  
                curl_close($curl);
                $obj = json_decode($result);
                $billid = $obj[0]->BillCode;
          
                //save to purchase table
                $purchase = new Purchase;
                $purchase->billid = $billid;
                $purchase->userid = $user_id;
                $purchase->token = $quantitytoken;
                $purchase->price = $netprice;
                $purchase->status = 'process';
                
                $purchase->save();

                $url = $this->envtoyyib.$billid;
                return response($url, 200);

        }

        
    }

    public function returnUrl(Request $request){
        
        $status_id = $request->status_id;
        $billcode = $request->billcode;
        $order_id = $request->order_id;
        $msg = $request->msg;
        $transaction_id = $request->transaction_id;

        if($status_id != 1){
            $billinfo = Purchase::where('billid',$billcode)->first();

            $billinfo->status = 'pending';
            $billinfo->save();

            return redirect()->to('https://waste2wealth.my/payment-failed.html');
        } else {

            $billinfo = Purchase::where('billid',$billcode)->first();

            if($billinfo){
                $user = User::where('user_id',$billinfo->userid)->first();
                $balancetoken = $user->balancetoken;
                $newtoken = $balancetoken + $billinfo->token;

                $history = new History;
                $history->user_id = $billinfo->userid;
                $history->billid = $billcode;
                $history->type = 'token';
                $history->name = $billinfo->token;
                $history->price = $billinfo->price;

                $user->balancetoken = $newtoken;

                $billinfo->status = 'succes';

                $history->save();
                $user->save();
                $billinfo->save();

                return redirect()->to('https://waste2wealth.my/payment-success.html');
            }
        }

      }
}

