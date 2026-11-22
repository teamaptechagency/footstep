<?php

namespace App\Http\Controllers;

use App\Services\BackendMenuOrder;
use Illuminate\Http\Request;

class BackendMenuOrderController extends Controller
{
    private function authorizeAdmin()
    {
        abort_unless(auth()->check() && auth()->user()->user_type === 'admin' && !auth()->user()->banned, 403);
    }

    public function index()
    {
        $this->authorizeAdmin();
        return response()->view('backend.platform.menu_order', ['menuNodes' => BackendMenuOrder::arrangedCatalog(), 'menuState' => BackendMenuOrder::state(), 'menuDefaults' => BackendMenuOrder::defaults()])->header('Cache-Control', 'private, no-store');
    }

    public function save(Request $request)
    {
        $this->authorizeAdmin();
        $data = $request->validate(['menu_order' => 'required|json|max:32000', 'revision' => 'required|string|size:64']);
        $order = json_decode($data['menu_order'], true);
        if (!is_array($order)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['menu_order' => 'Invalid menu order.']);
        }
        BackendMenuOrder::save($order, $data['revision']);
        return redirect()->route('admin.menu-order.index')->with('menu_order_success', 'Menu order saved / Menu-এর নতুন ক্রম সেভ হয়েছে।');
    }
}
