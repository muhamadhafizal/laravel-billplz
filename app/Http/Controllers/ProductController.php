<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Billplz\Laravel\Billplz;

class ProductController extends Controller
{
    public function index(Request $request){

            $email = $request->input('email');
            $mobile = $request->input('mobile');
            $name = $request->input('name');
            $tempprice = $request->input('price');
            $token = $request->input('token');

            $price = $tempprice * 100;

            $bill = Billplz::bill('v3')->create(

                $collectionId = "kfstwuda",
                $email,
                $mobile,
                $name,
                $price,
                '-',
                $token,
                [
                    // 'reference_1_label' => 'Bank Code', // if select bank account
                    // 'reference_1' => $request->bank_code, // if select bank account
                    'redirect_url' => 'http://codeviable.com/ecommerce/public/redirect' // will be the page to  show the receipt
                ]
        

            );  
            return redirect($bill->toArray()['url']);

    }

    public function bill(Request $request){

        $billid = $request->input('billid');
        $bill = Billplz::bill('v3')->get($billid);

        $finalbill = $bill->toArray();
   
        return response()->json(['status'=>'success','value'=>$finalbill]);
    }
    

    public function redirect(){
        $a = $request->all();

        dd($a);
    }

}