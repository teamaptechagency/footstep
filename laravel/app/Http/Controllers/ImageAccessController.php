<?php
namespace App\Http\Controllers;
use RecursiveIteratorIterator; use RecursiveDirectoryIterator; use FilesystemIterator;
class ImageAccessController extends Controller {
 public function index(){
 abort_unless(auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);
 $token=htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8');
 return response(<<<HTML
<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Public image permission repair</title><style>body{font:16px system-ui;max-width:650px;margin:60px auto;padding:20px;color:#173b34}button{padding:14px 22px;border:0;border-radius:8px;background:#008c55;color:white;cursor:pointer}progress{width:100%;margin:20px 0}p{line-height:1.6}</style><h1>Image access repair</h1><p>Public folders: 755 · public files: 644. শুধুমাত্র public assets/photos-এর permission ঠিক হবে।</p><button id="start">Start repair / শুরু করুন</button> <button id="stop" disabled>Stop</button><progress id="progress" hidden></progress><p id="state" role="status">Ready</p><script>
let stopped=false,after=0,total=0;const start=document.getElementById('start'),stop=document.getElementById('stop'),state=document.getElementById('state'),progress=document.getElementById('progress');stop.onclick=()=>{stopped=true;stop.disabled=true;};start.onclick=async()=>{start.disabled=true;stop.disabled=false;stopped=false;progress.hidden=false;try{while(!stopped){const r=await fetch(location.pathname,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':'$token'},body:JSON.stringify({after})});const d=await r.json();if(!r.ok)throw Error(d.message||'Request failed');after=d.after;total+=d.processed;state.textContent=total+' entries processed · '+d.failed+' permission failures in this batch';if(d.done){state.textContent+=' · Complete. Reload website; then delete this repair PHP file.';break;}}}catch(e){state.textContent=e.message+' · Login again or retry.';}finally{start.disabled=false;stop.disabled=true;progress.hidden=true;}};
</script>
HTML)->header('Cache-Control','private, no-store');
 }
 public function repair(\Illuminate\Http\Request $request){
 abort_unless(auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);
 abort_unless(auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);
 $d=$request->validate(['after'=>'required|integer|min:0|max:10000000']);
 $root=realpath(public_path());abort_unless($root&&is_dir($root),422);
 $ok=@chmod($root,0755);$failed=$ok?0:1;$index=0;$processed=0;$done=true;
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);
 foreach($it as $f){if($index++<$d['after'])continue;if($f->isLink()){$processed++;continue;}
  $real=$f->getRealPath();if(!$real||strpos(str_replace('\\','/',$real),str_replace('\\','/',$root).'/')!==0){$failed++;continue;}
  if(!@chmod($real,$f->isDir()?0755:0644))$failed++;$processed++;
  if($processed>=1000){$done=false;break;}
 }
 return response()->json(['after'=>$index,'processed'=>$processed,'failed'=>$failed,'done'=>$done])->header('Cache-Control','private, no-store');
 }
}
