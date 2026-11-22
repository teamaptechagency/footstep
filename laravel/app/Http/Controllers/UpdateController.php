<?php
namespace App\Http\Controllers;

/** Legacy updater URLs cannot extract ZIPs or execute SQL anymore. */
class UpdateController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }

    public function step0() { return $this->releaseCenter(); }
    public function step1() { return $this->releaseCenter(); }
    public function step2() { return $this->releaseCenter(); }

    private function releaseCenter()
    {
        abort_unless(auth()->user()->user_type === 'admin',403);
        return redirect()->route('platform.releases');
    }
}
