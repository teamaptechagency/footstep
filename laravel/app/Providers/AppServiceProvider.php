<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \App\Models\Order::observe(\App\Observers\OrderSmsObserver::class);
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Http\Client\Events\RequestSending::class, function ($e) {
            \App\Services\PerformanceLog::httpStart($e->request);
        });
        foreach ([\Illuminate\Http\Client\Events\ResponseReceived::class, \Illuminate\Http\Client\Events\ConnectionFailed::class] as $event) {
            \Illuminate\Support\Facades\Event::listen($event, function ($e) {
                \App\Services\PerformanceLog::httpEnd($e->request);
            });
        }
        $this->app->terminating(function () {
            try {
                if (app()->runningInConsole() || !auth()->check() || auth()->user()->user_type !== 'admin') {
                    return;
                }
                $name = request()->route() ? request()->route()->getName() : null;
                if (in_array($name, ['platform.releases', 'platform.releases.install'], true)) {
                    \App\Services\RollbackRetention::prune();
                }
            } catch (\Throwable $ignored) {
            }
        });
        Schema::defaultStringLength(191);
        Paginator::useBootstrap();
        $resolver = app('view.engine.resolver');
        foreach (['blade', 'php'] as $type) {
            $engine = $resolver->resolve($type);
            $resolver->register($type, function () use($engine) {
                return new \App\Services\TimedViewEngine($engine);
            });
        }
    }
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(\App\Providers\StorefrontServiceProvider::class);
        $this->app->singleton(\Illuminate\Routing\Contracts\ControllerDispatcher::class, function ($app) {
            return new \App\Services\TimedControllerDispatcher($app);
        });
        //
    }
}
