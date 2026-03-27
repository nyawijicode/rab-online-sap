<?php

use App\Models\RequestTeknisi;
use App\Models\RequestTeknisiHistory;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

// Simulate a user
$user = User::first();
if (!$user) {
    echo "No user found to simulate auth.\n";
    exit;
}
Auth::login($user);

echo "Simulating User: " . $user->name . "\n";

// 1. Create Request
echo "Creating new RequestTeknisi...\n";
$request = RequestTeknisi::create([
    'no_request' => 'TEST/001', // Will be overwritten by boot but good to have
    'nama_dinas' => 'Dinas Test',
    'nama_kontak' => 'Pak Budi',
    'no_telepon' => '08123456789',
    'jenis_pekerjaan' => 'Survey',
    'status' => 'request',
    'user_id' => $user->id,
]);

echo "Request Created ID: " . $request->id . "\n";

// Check History
$history = RequestTeknisiHistory::where('request_teknisi_id', $request->id)->get();
echo "History Count (Creation): " . $history->count() . "\n";
foreach ($history as $h) {
    echo " - " . $h->description . "\n";
}

// 2. Update Status
echo "\nUpdating Status to 'penjadwalan'...\n";
$request->update(['status' => 'penjadwalan']);

// 3. Update Schedule
echo "\nUpdating Schedule...\n";
$request->update(['tanggal_penjadwalan' => '2026-02-10']);

// Check History again
$history = RequestTeknisiHistory::where('request_teknisi_id', $request->id)->orderBy('id')->get();
echo "History Count (Total): " . $history->count() . "\n";
foreach ($history as $h) {
    echo " - [" . $h->created_at->format('H:i:s') . "] " . $h->description . " (Field: " . $h->field . " | Old: " . $h->old_value . " | New: " . $h->new_value . ")\n";
}

// Cleanup
echo "\nCleaning up...\n";
RequestTeknisiHistory::where('request_teknisi_id', $request->id)->delete();
$request->forceDelete();
echo "Done.\n";
