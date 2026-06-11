@if(config('chatbot.enabled'))
@php($cbPrimary = config('chatbot.primary', '#0056b3'))
<style>
    /* Bottom-RIGHT chat launcher; the WhatsApp icon stacks just above it (bottom:82px). */
    .inq-cb-launcher{position:fixed;right:20px;bottom:20px;z-index:9998;background:{{ $cbPrimary }};color:#fff;
        border:none;border-radius:50px;padding:12px 18px;font-weight:600;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.2);
        display:flex;align-items:center;gap:8px;font-size:14px;}
    .inq-cb-launcher svg{width:20px;height:20px;}
    .inq-cb-window{position:fixed;right:20px;bottom:20px;z-index:9999;width:340px;max-width:calc(100vw - 30px);
        height:480px;max-height:calc(100vh - 40px);background:#fff;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.25);
        display:none;flex-direction:column;overflow:hidden;font-size:14px;}
    @media (max-width:480px){ .inq-cb-launcher{right:16px;bottom:16px;} .inq-cb-window{right:8px;bottom:8px;} }
    .inq-cb-window.open{display:flex;}
    .inq-cb-head{background:{{ $cbPrimary }};color:#fff;padding:14px 16px;}
    .inq-cb-head h6{margin:0;font-size:15px;font-weight:700;}
    .inq-cb-head small{opacity:.85;font-size:12px;}
    .inq-cb-head .inq-cb-close{float:right;cursor:pointer;font-size:18px;line-height:1;opacity:.9;background:none;border:none;color:#fff;margin-left:8px;}
    .inq-cb-head .inq-cb-new{float:right;cursor:pointer;font-size:12px;line-height:1;opacity:.95;background:rgba(255,255,255,.18);border:none;color:#fff;border-radius:6px;padding:4px 8px;display:inline-flex;align-items:center;gap:4px;}
    .inq-cb-head .inq-cb-new:hover{background:rgba(255,255,255,.3);}
    .inq-cb-head .inq-cb-new svg{width:13px;height:13px;}
    .inq-cb-body{flex:1;overflow-y:auto;padding:14px;background:#f5f7fa;}
    .inq-cb-msg{margin-bottom:10px;display:flex;}
    .inq-cb-msg .bubble{padding:8px 12px;border-radius:12px;max-width:80%;word-wrap:break-word;}
    .inq-cb-msg.bot .bubble{background:#fff;border:1px solid #e3e8ef;}
    .inq-cb-msg.user{justify-content:flex-end;}
    .inq-cb-msg.user .bubble{background:{{ $cbPrimary }};color:#fff;}
    .inq-cb-foot{padding:10px;border-top:1px solid #eee;background:#fff;}
    .inq-cb-foot input{width:100%;border:1px solid #ccd3dd;border-radius:8px;padding:9px 10px;font-size:14px;}
    .inq-cb-opts{display:flex;flex-wrap:wrap;gap:6px;}
    .inq-cb-opts button,.inq-cb-send{background:{{ $cbPrimary }};color:#fff;border:none;border-radius:8px;padding:8px 12px;font-size:13px;cursor:pointer;}
    .inq-cb-opts button{background:#eef2f7;color:{{ $cbPrimary }};border:1px solid #d6deea;}
    .inq-cb-skip{background:none;border:none;color:#888;font-size:12px;cursor:pointer;margin-top:6px;text-decoration:underline;}
    .inq-cb-row{display:flex;gap:8px;}
</style>

<button class="inq-cb-launcher" id="inqCbLauncher" aria-label="Open chat">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.04 2 11c0 2.6 1.27 4.93 3.3 6.5L4.5 22l4.9-1.95c.82.2 1.7.3 2.6.3 5.52 0 10-4.04 10-9S17.52 2 12 2z"/></svg>
    <span>{{ \App\CPU\translate(config('chatbot.launcher', 'Chat with us')) }}</span>
</button>

<div class="inq-cb-window" id="inqCbWindow" aria-live="polite">
    <div class="inq-cb-head">
        <button class="inq-cb-close" id="inqCbClose" aria-label="Close">&times;</button>
        <button class="inq-cb-new" id="inqCbNew" aria-label="Start a new chat" title="Start over">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.65 6.35A7.95 7.95 0 0 0 12 4a8 8 0 1 0 7.74 10h-2.08A6 6 0 1 1 12 6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
            {{ \App\CPU\translate('New chat') }}
        </button>
        <h6 id="inqCbTitle">{{ config('chatbot.title') }}</h6>
        <small>{{ config('chatbot.subtitle') }}</small>
    </div>
    <div class="inq-cb-body" id="inqCbBody"></div>
    <div class="inq-cb-foot" id="inqCbFoot"></div>
</div>

<script>
(function(){
    var launcher=document.getElementById('inqCbLauncher'),
        win=document.getElementById('inqCbWindow'),
        body=document.getElementById('inqCbBody'),
        foot=document.getElementById('inqCbFoot'),
        closeBtn=document.getElementById('inqCbClose'),
        newBtn=document.getElementById('inqCbNew');
    var token=null, flow=[], step=0, started=false;
    var csrf=document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '{{ csrf_token() }}';
    var startUrl="{{ route('chatbot.start') }}", stepUrl="{{ route('chatbot.step') }}";

    function post(url,data){
        return fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(data)})
            .then(function(r){return r.ok?r.json():null;});
    }
    function addMsg(text,who){
        var d=document.createElement('div'); d.className='inq-cb-msg '+who;
        var b=document.createElement('div'); b.className='bubble'; b.textContent=text; d.appendChild(b);
        body.appendChild(d); body.scrollTop=body.scrollHeight;
    }
    function clearFoot(){ foot.innerHTML=''; }

    function render(){
        if(step>=flow.length){ clearFoot(); return; }
        var s=flow[step];
        addMsg(s.bot,'bot');
        clearFoot();
        if(s.type==='end'){ return; }
        if(s.type==='buttons'){
            var wrap=document.createElement('div'); wrap.className='inq-cb-opts';
            (s.options||[]).forEach(function(opt){
                var btn=document.createElement('button'); btn.textContent=opt;
                btn.onclick=function(){ answer(s,opt); };
                wrap.appendChild(btn);
            });
            foot.appendChild(wrap);
        } else {
            var row=document.createElement('div'); row.className='inq-cb-row';
            var inp=document.createElement('input');
            inp.type=(s.type==='tel'?'tel':'text');
            inp.placeholder=(s.type==='tel'?'e.g. 98xxxxxxxx':'Type your answer…');
            var send=document.createElement('button'); send.className='inq-cb-send'; send.textContent='Send';
            function go(){
                var v=inp.value.trim();
                if(s.validate==='phone' && v.replace(/[^0-9]/g,'').length<7){ inp.style.borderColor='#e74c3c'; return; }
                if(!v && !s.optional){ inp.style.borderColor='#e74c3c'; return; }
                answer(s,v);
            }
            send.onclick=go;
            inp.addEventListener('keydown',function(e){ if(e.key==='Enter') go(); });
            row.appendChild(inp); row.appendChild(send); foot.appendChild(row);
            if(s.optional){
                var skip=document.createElement('button'); skip.className='inq-cb-skip'; skip.textContent='Skip';
                skip.onclick=function(){ answer(s,''); }; foot.appendChild(skip);
            }
            inp.focus();
        }
    }
    function answer(s,value){
        if(value!=='') addMsg(value,'user');
        post(stepUrl,{token:token,key:s.key,value:value});
        step++; render();
    }
    function boot(){
        if(started) return; started=true;
        post(startUrl,{page_url:location.pathname}).then(function(res){
            if(!res){ addMsg('Sorry, chat is unavailable right now.','bot'); return; }
            token=res.token; flow=res.flow||[];
            if(res.ui && res.ui.title){ document.getElementById('inqCbTitle').textContent=res.ui.title; }
            step=0; render();
        });
    }
    function restart(){
        // wipe the conversation + start a fresh session (creates a new lead row)
        body.innerHTML=''; foot.innerHTML='';
        token=null; flow=[]; step=0; started=false;
        boot();
    }
    var wa=document.querySelector('.ind-whatsapp-float'); // may be null off-homepage
    function open(){ win.classList.add('open'); launcher.style.display='none'; if(wa) wa.style.display='none'; boot(); }
    function close(){ win.classList.remove('open'); launcher.style.display='flex'; if(wa) wa.style.display='inline-flex'; }
    launcher.onclick=open; closeBtn.onclick=close; if(newBtn) newBtn.onclick=restart;
})();
</script>
@endif
