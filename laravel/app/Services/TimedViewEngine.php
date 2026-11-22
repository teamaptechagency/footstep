<?php
namespace App\Services;
class TimedViewEngine implements \Illuminate\Contracts\View\Engine {
 private $engine;
 public function __construct($engine){$this->engine=$engine;}
 public function get($path,array $data=[]){return PerformanceLog::measure('views',function()use($path,$data){return $this->engine->get($path,$data);});}
 public function __call($method,$args){return $this->engine->$method(...$args);}
}
