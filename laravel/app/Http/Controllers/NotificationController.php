<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class NotificationController extends Controller
{
    public function index(Request $request) {
        if(in_array(Auth::user()->user_type,['admin','staff'],true)){
            $request->validate(['category'=>'nullable|in:placed,confirmed,delivery,cancelled,other','unread'=>'nullable|in:1']);
            $counts=\App\Services\OrderNotificationFeed::counts(auth()->user());$query=\App\Services\OrderNotificationFeed::filter(auth()->user()->notifications(),$request->input('category'));
            if($request->boolean('unread'))$query->whereNull('read_at');
            $notifications=$query->latest()->paginate(20)->withQueryString();
            return view('backend.notification.index',compact('notifications','counts'));
        }
        $notifications = auth()->user()->notifications()->latest()->paginate(15);
        
        auth()->user()->unreadNotifications->markAsRead();
        
        if(Auth::user()->user_type == 'admin') {
            return view('backend.notification.index', compact('notifications'));
        }
        
        if(Auth::user()->user_type == 'seller') {
            return view('frontend.user.seller.notification.index', compact('notifications'));
        }
        
        if(Auth::user()->user_type == 'customer') {
            return view('frontend.user.customer.notification.index', compact('notifications'));
        }
        
    }
}
