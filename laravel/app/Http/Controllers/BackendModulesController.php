<?php

namespace App\Http\Controllers;

use App\Services\BackendModules;
use Illuminate\Http\Request;

class BackendModulesController extends Controller
{
    private function authorizeAdmin()
    {
        abort_unless(auth()->check() && auth()->user()->user_type === 'admin' && !auth()->user()->banned, 403);
    }

    public function index()
    {
        $this->authorizeAdmin();
        return response()->view('backend.platform.modules', ['modules' => BackendModules::catalog(), 'moduleState' => BackendModules::state()])->header('Cache-Control', 'private, no-store');
    }

    public function save(Request $request)
    {
        $this->authorizeAdmin();
        $data = $request->validate(['flags' => 'required|array', 'revision' => 'required|string|size:64']);
        BackendModules::save($data['flags'], $data['revision']);
        return redirect()->route('admin.modules.index')->with('module_success', 'Modules updated / পরিবর্তন সেভ হয়েছে। বন্ধ feature backend থেকে লুকানো হয়েছে।');
    }
}
