<?php
namespace App\Services;

class AdminDeliveryNote
{
    public const DEFAULT_TEXT='ডেলিভারি করে আমার হোয়াটসঅ্যাপে স্ক্রিনশট পাঠাবেন 01846131618 আপনার রেটিং এবং চার্জ এর ব্যবস্থা আছে ঢাকার মধ্যে ডেলিভারি চার্জ ১০০ টাকা ঢাকার বাহিরে ১৫০ টাকা প্রতি জোড়ার জন্য';

    public static function text($orderId)
    {
        $note=StoreOperations::get('admin_delivery_note_'.(int)$orderId);
        return $note['text']??self::DEFAULT_TEXT;
    }
}
