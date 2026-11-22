@php($returningInfo=\App\Services\ReturningCustomers::info($returningOrder??$order??null))
@if($returningInfo['returning'])
<div style="border:1px solid #d8cda7;background:#fffcf3;padding:15px 18px;margin:16px 0;border-radius:8px;color:#6d5830;page-break-inside:avoid;">
<span style="display:inline-block;background:#efe4c4;color:#735c27;padding:5px 9px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:1px;">RETURNING CUSTOMER · WELCOME BACK</span>
<p style="margin:10px 0 5px;font-size:14px;font-weight:600;">আবারও Footstep বেছে নেওয়ার জন্য আন্তরিক ধন্যবাদ।</p>
<p style="margin:0;font-size:12px;line-height:1.8;">আপনার আস্থা আমাদের কাছে বিশেষ মূল্যবান। আপনাকে আবার পাশে পেয়ে আমরা আনন্দিত।</p>
<p style="margin:7px 0 0;font-size:11px;line-height:1.7;">Thank you for choosing us again. Your trust means a great deal to us.</p>
</div>
@endif
