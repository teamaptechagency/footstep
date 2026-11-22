<?php
namespace App\Services;
class TimedControllerDispatcher extends \Illuminate\Routing\ControllerDispatcher {
 public function dispatch(\Illuminate\Routing\Route $route,$controller,$method){return PerformanceLog::measure('controller',function()use($route,$controller,$method){return parent::dispatch($route,$controller,$method);});}
}
