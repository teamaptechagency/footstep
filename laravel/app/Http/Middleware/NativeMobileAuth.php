<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\{Auth,DB};
use App\Models\User;

/** Dedicated bearer tokens: never accepted by the legacy v2/Sanctum API. */
class NativeMobileAuth
{
    public function handle($request, Closure $next)
    {
        $raw=$request->bearerToken();
        abort_unless(is_string($raw)&&preg_match('/^[a-f0-9]{64}$/D',$raw),401,'Please sign in.');
        $token=DB::table('footstep_mobile_tokens')->where('token_hash',hash('sha256',$raw))->where('expires_at','>',now())->first();
        abort_unless($token,401,'Your session expired. Please sign in again.');
        $user=User::find($token->user_id);
        abort_unless($user&&!$user->banned&&in_array($user->user_type,['admin','customer'],true),401,'This account is unavailable.');
        $request->attributes->set('native_mobile_token_id',$token->id);
        $request->setUserResolver(fn()=>$user); Auth::guard('web')->setUser($user);
        return $next($request);
    }
}
