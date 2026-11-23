<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Sesi berakhir atau Anda belum masuk. Silakan login kembali.');
        }

        $user = auth()->user();

        if (!in_array($user->role, $roles)) {
            // Log unauthorized attempt as per SRS OR-001
            AuditLog::log(
                action: 'akses_ditolak',
                modelType: 'route',
                modelId: $request->path(),
                newValues: ['attempted_url' => $request->fullUrl(), 'required_roles' => $roles, 'user_role' => $user->role]
            );

            // Abort with 403 or redirect with error
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak: di luar kewenangan peran Anda.'], 403);
            }

            return redirect()->route($user->role === 'admin' ? 'admin.dashboard' : 'gizi.dashboard')
                ->with('error', 'Akses ditolak: Anda tidak memiliki izin untuk membuka halaman tersebut.');
        }

        return $next($request);
    }
}

