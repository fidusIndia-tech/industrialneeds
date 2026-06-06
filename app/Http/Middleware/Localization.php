<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // The rest of the app stores the active locale under the 'local' session key
        // (see SharedController@lang and Helpers::default_lang()). Honour that key so
        // App::setLocale() actually reflects the visitor's chosen language.
        if (session()->has('local')) {
            App::setLocale(session()->get('local'));
        }
        return $next($request);
    }
}
