@extends('backend.layouts.layout')

@section('content')

<style>
.admin-entry{min-height:100vh;min-height:100dvh;display:grid;place-items:center;padding:24px;background:#eef3f2;color:#142b33}
.admin-entry-shell{width:min(100%,920px);display:grid;grid-template-columns:1fr 1.1fr;background:#fff;border:1px solid #dce7e5;border-radius:24px;overflow:hidden;box-shadow:0 24px 64px rgba(20,43,51,.12)}
.admin-entry-brand{display:flex;flex-direction:column;justify-content:space-between;gap:64px;padding:48px;background:radial-gradient(ellipse at top left,#246d62,#102e37 72%);color:#fff}
.admin-entry-logos{display:flex;align-items:center;flex-wrap:wrap;gap:10px}
.admin-entry-brand img{width:auto;max-width:145px;height:56px;object-fit:contain;align-self:flex-start;background:#fff;border-radius:12px;padding:6px}
.admin-entry-astral{display:inline-flex;overflow:hidden;border-radius:12px}
.admin-entry-astral img{transform:scale(2.3);transform-origin:center}
.admin-entry-brand span{font-size:14px;color:#c4ded9}
.admin-entry-wordmark{font-size:clamp(34px,5vw,56px);font-weight:400;line-height:1.05;letter-spacing:.035em;margin:10px 0 14px}
.admin-entry-credit{display:flex;align-items:center;gap:8px;font-size:12px;color:#c4ded9}
.admin-entry-credit::before{content:"";width:18px;height:1px;background:#7ed8c2}
.admin-entry-credit b{color:#a6ebd9;font-weight:600;letter-spacing:.15em}
.admin-entry-panel{padding:clamp(24px,5vw,56px)}
.admin-entry-panel h1{font-size:clamp(26px,4vw,34px);letter-spacing:-.035em;color:#142b33;margin:0 0 8px}
.admin-entry-panel p{color:#52686c;margin:0 0 28px}
.admin-entry-panel .form-control{min-height:46px;border-radius:9px}
.admin-entry-panel .entry-submit{min-height:46px;background:#086553;color:#fff;border-radius:9px;font-weight:700}
.admin-entry-panel .entry-submit:hover,.admin-entry-panel .entry-submit:focus-visible{background:#064d40;color:#fff}
.admin-entry-panel a{color:#086553}
@media(max-width:640px){.admin-entry{padding:16px}.admin-entry-shell{grid-template-columns:1fr;border-radius:18px}.admin-entry-brand{gap:16px;padding:20px}.admin-entry-brand img{height:44px;max-width:95px;align-self:center}.admin-entry-wordmark{font-size:36px;margin:6px 0 9px}}
</style>

<main class="admin-entry">
    <div class="admin-entry-shell">
        <div class="admin-entry-brand">
            <div class="admin-entry-logos">
            <img src="{{ static_asset('assets/img/footstep-brand.png') }}" alt="Footstep">
                <span class="admin-entry-astral"><img src="{{ static_asset('assets/img/astral-labs-admin.svg') }}" alt="Astral Labs"></span>
            </div>
            <div><span>{{ translate('Admin workspace') }}</span><div class="admin-entry-wordmark">FOOTSTEP</div><div class="admin-entry-credit">powered by <b>ASTRAL LABS</b></div></div>
        </div>
        <div class="admin-entry-panel">
            <h1>{{ translate('Admin login') }}</h1>
            <p>{{ translate('Login to your account.') }}</p>
                        <form method="POST" role="form" action="{{ route('login') }}">
                            @csrf
                            <div class="form-group">
                                <label for="email">{{ translate('Email') }}</label>
                                <input autocomplete="username" id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required autofocus placeholder="{{ translate('Email') }}">
                                @if ($errors->has('email'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="password">{{ translate('Password') }}</label>
                                <input autocomplete="current-password" id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required placeholder="{{ translate('Password') }}">
                                @if ($errors->has('password'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-6">
                                    <div class="text-left">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                            <span>{{ translate('Remember Me') }}</span>
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                </div>
                                @if(env('MAIL_USERNAME') != null && env('MAIL_PASSWORD') != null)
                                    <div class="col-sm-6">
                                        <div class="text-right">
                                            <a href="{{ route('password.request') }}" class="text-reset fs-14">{{translate('Forgot password ?')}}</a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <button type="submit" class="btn entry-submit btn-block">
                                {{ translate('Login') }}
                            </button>
                        </form>
                        @if (env("DEMO_MODE") == "On")
                            <div class="mt-4">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <td>demo@example.invalid</td>
                                            <td>123456</td>
                                            <td><button class="btn btn-info btn-xs" onclick="autoFill()">{{ translate('Copy') }}</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif
        </div>
    </div>
</main>


@endsection

@section('script')
    <script type="text/javascript">
        function autoFill(){
            $('#email').val('demo@example.invalid');
            $('#password').val('123456');
        }
    </script>
@endsection
