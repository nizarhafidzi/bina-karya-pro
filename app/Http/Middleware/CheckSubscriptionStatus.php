<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Pastikan user login
        if (!auth()->check()) {
            return $next($request);
        }

        // 2. Ambil Tenant Aktif
        // Karena sudah di tenantMiddleware, ini SEHARUSNYA tidak null
        $tenant = Filament::getTenant();

        // Jika entah kenapa tenant null (misal di halaman profil user global), skip
        if (!$tenant) {
            return $next($request);
        }

        // 3. Cek Subscription
        // Kita gunakan Eager Loading 'subscription' di model Team agar performa bagus, 
        // tapi di sini kita akses langsung relasi.
        $subscription = $tenant->subscription; 

        // Skenario Blokir:
        // A. Tidak punya subscription sama sekali
        // B. Punya subscription, tapi ends_at sudah lewat (Expired)
        if (!$subscription || ($subscription->ends_at && $subscription->ends_at->isPast())) {
            
            // PENTING: Izinkan akses ke halaman Billing agar user bisa bayar!
            // Ganti 'filament.app.pages.billing' dengan route billing Anda yang sebenarnya nanti.
            // Untuk sementara, kita cek string URL-nya.
            if ($request->routeIs('filament.app.pages.billing') || str_contains($request->url(), '/billing')) {
                return $next($request);
            }

            // Tampilkan Error 403 Forbidden dengan pesan jelas
            abort(403, 'AKSES DITOLAK: Masa berlangganan tim ' . $tenant->name . ' telah berakhir. Silakan lakukan pembayaran.');
        }

        return $next($request);
    }
}