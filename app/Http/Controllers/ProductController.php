<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Billplz\Laravel\Billplz;
use App\User;
use App\Purchase;
use App\History;

class ProductController extends Controller
{
    public function index(Request $request){

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

            $email = $user->user_email;
            $mobile = $user->user_contact;
            $name = $finalname;
            $tempprice = $netprice;
            $token = $quantitytoken . ' token';

            $price = $tempprice * 100;

            //create bill
            $bill = Billplz::bill('v3')->create(

                $collectionId = 'ioy8du_r',
                $email,
                $mobile,
                $name,
                $price,
                '-',
                $token,
                [
                    'redirect_url' => 'http://codeviable.com/testbillplz/public/redirect'
                ]

            );

            //save to purchase table
            $purchase = new Purchase;
            $purchase->billid = $bill->toArray()['id'];
            $purchase->userid = $user_id;
            $purchase->token = $quantitytoken;
            $purchase->price = $netprice;

            $purchase->save();

            return redirect($bill->toArray()['url']);

        }   
           

    }

    public function bill(Request $request){

        $billid = $request->input('billid');
        $bill = Billplz::bill('v3')->get($billid);

        $finalbill = $bill->toArray();
   
        return response()->json(['status'=>'success','value'=>$finalbill]);
    }
    

    public function redirect(Request $request){

        $result = $request->all();
        
        $billid = $result['billplz']['id'];
        $status = $result['billplz']['paid'];

        if($status == 'false'){
            return response()->json(['status'=>'error','value'=>'sorry your transaction process is not valid']);
        } else {

            //getbill
            //$bill = Billplz::bill('v3')->get($billid);
            // $finalbill = $bill->toArray();

            $billinfo = Purchase::where('billid',$billid)->first();

            $user = User::where('user_id',$billinfo->userid)->first();

            $balancetoken = $user->balancetoken;

            $newtoken = $balancetoken + $billinfo->token;

            $history = new History;
            $history->user_id = $billinfo->userid;
            $history->type = 'token';
            $history->name = $billinfo->token;
            $history->price = $billinfo->price;

            $user->balancetoken = $newtoken;

            $history->save();
            $user->save();

            return response()->json(['status'=>'success','value'=>'token added to user account']);

        }
    }

}