<?php
namespace App\Services;

use App\Models\{Product,Review,BusinessSetting};
use Illuminate\Support\Facades\{DB,Cache};

class ReviewSampleDrafts
{
    public static function create($adminId)
    {
        $count=DB::transaction(function()use($adminId){
            // A stable product row serializes repeated clicks without a new schema.
            if(!Product::orderBy('id')->lockForUpdate()->first())return 0;
            $key='sample_review_drafts_batch_v1';
            if(BusinessSetting::where('type',$key)->exists())return 0;
            $products=Product::where('published',1)->where('approved',1)->where('num_of_sale','>',0)->orderByDesc('num_of_sale')->orderBy('id')->limit(5)->get();
            if($products->isEmpty())return 0;
            foreach(self::comments() as $i=>$comment){
                $review=new Review();$review->product_id=$products[$i%$products->count()]->id;$review->user_id=$adminId;
                $review->rating=$i%3===0?5:4;$review->comment='[নমুনা draft — বাস্তব review নয়] '.$comment;$review->status=0;$review->viewed=1;$review->save();
                $meta=new BusinessSetting();$meta->type='manual_review_'.$review->id;
                $meta->value=json_encode(['source'=>'sample','name'=>'নমুনা ক্রেতা '.sprintf('%02d',$i+1),'sample_name'=>'নমুনা ক্রেতা '.sprintf('%02d',$i+1),'sample_comment'=>$review->comment,'source_note'=>'Private sample template; replace with real feedback','order_reference'=>''],JSON_UNESCAPED_UNICODE);$meta->save();
            }
            $marker=new BusinessSetting();$marker->type=$key;$marker->value=json_encode(['count'=>40,'created_by'=>(int)$adminId,'at'=>date(DATE_ATOM)]);$marker->save();
            return 40;
        });
        if($count)Cache::forget('business_settings');
        return $count;
    }

    public static function comments()
    {
        return [
            'জুতার ফিট ভালো লেগেছে, তবে নতুন অবস্থায় একটু শক্ত মনে হয়েছে।',
            'Comfortable for short walks. I would prefer a little more cushioning.',
            'ছবির রঙের সঙ্গে কিছুটা পার্থক্য আছে, ডিজাইন সুন্দর।',
            'The sizing feels slightly snug; checking the size chart helped.',
            'প্যাকেট ঠিক ছিল। কয়েকদিন ব্যবহারের পর টেকসই নিয়ে বলতে পারব।',
            'Looks neat with casual outfits. The laces could be a bit longer.',
            'সোলের গ্রিপ মোটামুটি ভালো, ভেজা জায়গায় এখনো পরীক্ষা করিনি।',
            'Light enough for my daily commute, though the heel needs breaking in.',
            'পায়ের সামনে জায়গা ঠিক আছে, সরু পায়ের জন্য ফিট ভালো হতে পারে।',
            'The stitching looks tidy overall. One small finishing detail could improve.',
            'ডিজাইন পছন্দ হয়েছে। সারাদিন পরে থাকার অভিজ্ঞতা পরে জানাব।',
            'The color combination works well; indoor lighting makes it look darker.',
            'দাম অনুযায়ী অভিজ্ঞতা ভালো, প্যাডিং আরেকটু হলে ভালো হতো।',
            'A good everyday style, but I would not judge its durability yet.',
            'সাইজ মেপে নেওয়ায় সমস্যা হয়নি। গোড়ালির কাছে একটু টাইট।',
            'Easy to match with jeans. The toe area feels a little firm initially.',
            'জুতার ওজন নিয়ে সন্তুষ্ট, ফিনিশিংয়ে সামান্য উন্নতির সুযোগ আছে।',
            'The upper feels comfortable during light use; longer walks are untested.',
            'প্যাকেজিং পরিষ্কার ছিল, বক্সে ছোট একটা দাগ ছিল।',
            'The sole feels supportive, although I prefer a softer insole.',
            'রঙটা সামনে থেকে বেশি ভালো লাগে। ফিট নিয়ে বড় অভিযোগ নেই।',
            'The shape is appealing. I needed a short adjustment period.',
            'হাঁটার সময় আরাম পেয়েছি, দৌড়ানোর জন্য ব্যবহার করিনি।',
            'Decent finishing for the price. I would like more size guidance.',
            'লেস বাঁধার পর ফিট ভালো হয়েছে, খুলে পরতে একটু সময় লাগে।',
            'The material feels fine for casual use; weather resistance is untested.',
            'আমার সাধারণ সাইজ ঠিক হয়েছে। পায়ের প্রস্থ অনুযায়ী মিলিয়ে নেওয়া ভালো।',
            'The design stands out without being too bright. Cushioning is average.',
            'সামনে কিছুটা শক্ত, কয়েকবার পরার পর আরাম বেড়েছে।',
            'Looks close to the listing photos, with minor shade differences.',
            'সোল নমনীয় মনে হয়েছে, দীর্ঘদিনের ব্যবহার নিয়ে এখনই মন্তব্য করব না।',
            'Comfort is reasonable for errands. A softer collar would be welcome.',
            'গোড়ালির সাপোর্ট ভালো লেগেছে, ইনসোল ব্যক্তিভেদে বদলানো লাগতে পারে।',
            'The pair looks balanced and clean. There is room to improve the packaging.',
            'কাজে যাতায়াতে ব্যবহার উপযোগী মনে হয়েছে। শুরুতে একটু টাইট ছিল।',
            'A practical casual pair. I am still getting used to the fit.',
            'ছবির ডিজাইন পছন্দ করেই নিয়েছিলাম, সামনে দেখেও ভালো লেগেছে।',
            'The grip seems fine indoors. I have not tried it on wet surfaces.',
            'দাম ও ডিজাইন বিবেচনায় সন্তুষ্ট, আরাম নিয়ে আরও কিছুদিন দেখে বলব।',
            'Overall a pleasant first impression; a longer wear test is still needed.'
        ];
    }
}
