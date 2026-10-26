<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PondDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'username'    => 'required|string|max:255|unique:user,username',
            'email'       => 'required|string|email|max:255|unique:user,email',
            'password'    => 'required|string|min:8|confirmed',
            'mac_address' => 'required|string|exists:pond_devices,mac_address',
        ], [
            'mac_address.exists' => 'The provided MAC Address is not registered in our system.',
        ]);

        // 2. Cari ID Peranti berdasarkan MAC Address
        $device = PondDevice::where('mac_address', $request->mac_address)->first();

        // 3. Cipta Pengguna Baharu (Role: Customer)
        User::create([
            'username'  => $request->username,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => 'Customer',
            'device_id' => $device->device_id ?? $device->id,
        ]);

        // 4. Redirect ke Skrin Login
        return redirect()->route('login')->with('success', 'Registration successful! Please login.');
    }
}