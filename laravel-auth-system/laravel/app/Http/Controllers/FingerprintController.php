<?php

namespace App\Http\Controllers;

use App\Models\Fingerprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FingerprintController extends Controller
{
    /**
     * Store a newly created fingerprint in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'fingerprint_hash' => 'required|string|max:255',
            'components' => 'nullable|array',
        ]);

        // Optional: Check if we already have this fingerprint for this session/IP recently to avoid spam
        // For "collecting all", we might want every visit or just unique ones.
        // Let's store one per session or update timestamp if exists?
        // The user said "save them all". Let's simply create a new record.

        $fingerprint = Fingerprint::create([
            'user_id' => Auth::id(),
            'fingerprint_hash' => $request->fingerprint_hash,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_data' => $request->components,
        ]);

        return response()->json(['status' => 'stored', 'id' => $fingerprint->id]);
    }
}
