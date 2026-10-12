<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDocumentationIsAccessible
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('docs.enabled')) {
            abort(404);
        }

        $environments = (array) config('docs.environments', []);

        if ($environments !== [] && ! in_array(app()->environment(), $environments, true)) {
            abort(404);
        }

        return $next($request);
    }
}
