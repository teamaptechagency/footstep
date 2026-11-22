<?php
namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ManualReviewController extends Controller
{
    private function admin(){ abort_unless(auth()->check() && auth()->user()->user_type === 'admin',403); }
    private function form(Review $review)
    {
        $this->admin();
        $products=Product::select('id','name','num_of_sale')->orderByDesc('num_of_sale')->orderBy('name')->get();
        $metadata=$review->manualMetadata?json_decode($review->manualMetadata->value,true):[];
        $metadata=is_array($metadata)?$metadata:[];
        return view('backend.product.reviews.manual',compact('review','products','metadata'));
    }
    public function create(){ $this->admin();return $this->form(new Review()); }
    public function edit($id){ $this->admin();$review=Review::findOrFail($id);abort_unless($review->is_manual,404);return $this->form($review); }
    public function delete(Request $request)
    {
        $this->admin();
        $data=$request->validate(['ids'=>'required|array|min:1|max:100','ids.*'=>'required|integer|min:1|distinct']);
        $count=DB::transaction(function()use($data){
            $reviews=Review::whereIn('id',$data['ids'])->orderBy('id')->lockForUpdate()->get();
            if($reviews->count()!==count($data['ids']))throw \Illuminate\Validation\ValidationException::withMessages(['ids'=>'Some reviews no longer exist. Refresh the list / তালিকা refresh করুন।']);
            foreach($reviews as $review)abort_unless($review->is_manual,403);
            $products=$reviews->pluck('product_id')->unique();
            BusinessSetting::whereIn('type',$reviews->map(function($review){return 'manual_review_'.$review->id;})->all())->delete();
            Review::whereIn('id',$reviews->pluck('id'))->delete();
            foreach($products as $id){
                $average=(float)Review::where('product_id',$id)->where('status',1)->avg('rating');
                Product::where('id',$id)->update(['rating'=>$average]);
            }
            return $reviews->count();
        });
        Cache::forget('business_settings');Cache::forget('todays_deal_products');
        return redirect()->route('platform.reviews.index')->with('manual_review_success',$count.' review(s) deleted / রিভিউ মুছে ফেলা হয়েছে।');
    }
    public function samples()
    {
        $this->admin();
        $count=\App\Services\ReviewSampleDrafts::create(auth()->id());
        return redirect()->route('platform.reviews.index')->with('manual_review_success',$count ? '40 sample drafts created / ৪০টি অপ্রকাশিত নমুনা তৈরি হয়েছে।' : 'Sample batch already exists, or no eligible products / নমুনা আগে তৈরি হয়েছে অথবা উপযুক্ত product নেই।');
    }
    public function save(Request $request,$id=null)
    {
        $this->admin();
        $data=$request->validate(['product_id'=>'required|integer|exists:products,id','reviewer_name'=>'required|string|max:100','rating'=>'required|integer|min:1|max:5','comment'=>'required|string|max:5000','review_date'=>'required|date_format:Y-m-d|before_or_equal:today','published'=>'nullable|boolean','sample_confirmed'=>'nullable|boolean','source_note'=>'nullable|string|max:300','order_reference'=>'nullable|string|max:100']);
        DB::transaction(function()use($request,$data,$id){
            $review=$id?Review::lockForUpdate()->findOrFail($id):new Review();
            if($id)abort_unless($review->is_manual,404);
            $oldMeta=$review->manualMetadata?json_decode($review->manualMetadata->value,true):[];
            $sample=($oldMeta['source']??'')==='sample';
            if($sample && $request->boolean('sample_confirmed')){
                if(trim($data['reviewer_name'])===trim($oldMeta['sample_name']??$oldMeta['name']??'') || trim($data['comment'])===trim($oldMeta['sample_comment']??$review->comment) || strpos($data['reviewer_name'],'নমুনা')!==false || strpos($data['comment'],'[নমুনা')!==false){
                    throw \Illuminate\Validation\ValidationException::withMessages(['comment'=>'Replace the sample name and comment with real customer feedback first / আসল ক্রেতার নাম ও feedback দিন।']);
                }
                $sample=false;
            }
            if($sample && $request->boolean('published'))throw \Illuminate\Validation\ValidationException::withMessages(['published'=>'Sample drafts cannot be published / নমুনা সরাসরি প্রকাশ করা যাবে না।']);
            $previous=$review->product_id;
            $review->product_id=$data['product_id'];
            if(!$review->exists)$review->user_id=auth()->id();
            $review->rating=$data['rating'];$review->comment=$data['comment'];$review->status=$request->boolean('published')?1:0;$review->viewed=1;$review->created_at=$data['review_date'].' 12:00:00';$review->save();
            $meta=BusinessSetting::where('type','manual_review_'.$review->id)->first()?:new BusinessSetting();$meta->type='manual_review_'.$review->id;$meta->value=json_encode(['source'=>$sample?'sample':'imported','name'=>trim($data['reviewer_name']),'source_note'=>trim($data['source_note']??''),'order_reference'=>trim($data['order_reference']??''),'sample_name'=>$sample?($oldMeta['sample_name']??$oldMeta['name']??''):null,'sample_comment'=>$sample?($oldMeta['sample_comment']??$review->comment):null],JSON_UNESCAPED_UNICODE);$meta->save();
            foreach(array_unique(array_filter([$previous,$review->product_id])) as $productId){
                $rating=(float)Review::where('product_id',$productId)->where('status',1)->avg('rating');
                Product::where('id',$productId)->update(['rating'=>$rating]);
            }
        });
        Cache::forget('business_settings');Cache::forget('todays_deal_products');
        return redirect()->route('platform.reviews.index')->with('manual_review_success',$id?'Imported review updated.':'Imported review added.');
    }
}
