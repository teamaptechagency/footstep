<?php
namespace App\Http\Controllers;
use App\Support\InternationalInterfaceSettings;
use Illuminate\Http\Request;

class InternationalInterfaceController extends Controller
{
    private InternationalInterfaceSettings $settings;
    public function __construct(InternationalInterfaceSettings $settings) { $this->settings = $settings; }
    public function index() { abort_unless(auth()->user()->user_type === 'admin', 403); return view('backend.settings.international_interface', ['enabled' => $this->settings->enabled()]); }
    public function save(Request $request)
    {
        abort_unless($request->user()->user_type === 'admin', 403);
        $request->validate(['enabled' => ['required', 'boolean']]);
        try { $this->settings->save($request->boolean('enabled')); }
        catch (\Throwable $e) {
            return redirect()->route('settings.international')->withErrors(['enabled' => 'Could not save the setting. Your previous setting has been kept.']);
        }
        flash('International interface setting saved.')->success();
        return redirect()->route('settings.international');
    }
}
