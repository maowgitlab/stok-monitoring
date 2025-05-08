<?php

namespace App\Http\Controllers;

use App\Models\Pin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PinController extends Controller
{
    public function show(Request $request)
    {
        Log::debug('PinController::show - Session ID: ' . $request->session()->getId());
        return view('pin.verify');
    }

    public function verify(Request $request)
    {
        Log::debug('PinController::verify - Session ID: ' . $request->session()->getId());
        $request->validate([
            'pin' => 'required|digits:4',
        ]);

        // Cek lockout
        $attempts = $request->session()->get('pin_attempts', 0);
        $lockoutUntil = $request->session()->get('pin_lockout', 0);
        $now = now()->timestamp;

        Log::debug("PinController::verify - Attempts: {$attempts}, Lockout until: {$lockoutUntil}");

        if ($lockoutUntil > $now) {
            $remaining = ceil(($lockoutUntil - $now) / 60);
            return redirect()->route('pin.verify')
                ->with('error', "Terlalu banyak percobaan. Coba lagi dalam {$remaining} menit.");
        }

        // Validasi PIN
        $pin = Pin::first();
        if ($pin && Hash::check($request->pin, $pin->pin)) {
            $request->session()->put('pin_verified', true);
            $request->session()->forget(['pin_attempts', 'pin_lockout']);
            Log::info('PIN verified successfully');
            return redirect()->route('settings.index');
        }

        // PIN salah
        $attempts++;
        $request->session()->put('pin_attempts', $attempts);

        if ($attempts >= 3) {
            $request->session()->put('pin_lockout', now()->addMinutes(5)->timestamp);
            Log::warning('Too many PIN attempts, locked out');
            return redirect()->route('pin.verify')
                ->with('error', 'Terlalu banyak percobaan. Coba lagi dalam 5 menit.');
        }

        Log::warning('Invalid PIN attempt');
        return redirect()->route('pin.verify')
            ->with('error', 'PIN salah. Sisa ' . (3 - $attempts) . ' percobaan.');
    }
}