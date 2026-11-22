<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\CustomerInvoice;
class CustomerInvoiceController extends Controller
{
 public function update(Request $request,$id){
  $data=$request->validate(['lines'=>'required|array|min:1|max:100','lines.*.stock_id'=>'required|integer|min:1','lines.*.quantity'=>'required|integer|min:1|max:99','lines.*.addons_present'=>'nullable|in:1','lines.*.addon_ids'=>'nullable|array|max:10','lines.*.addon_ids.*'=>'required|integer|min:1']);
  try { CustomerInvoice::update($id,$data); }
  catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
   if(!in_array($e->getStatusCode(),[409,422]))throw $e;
   return redirect()->to($request->fullUrl())->withErrors(['invoice'=>$e->getMessage()]);
  }
  return redirect()->to($request->fullUrl())->with('customer_invoice_success','পরিবর্তন সংরক্ষিত হয়েছে।');
 }
}
