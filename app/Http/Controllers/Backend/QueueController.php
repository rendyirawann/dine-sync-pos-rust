<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Queue;
use App\Events\CallQueueEvent;
use App\Events\NewQueueEvent; // 🔥 WAJIB DITAMBAHKAN AGAR TIDAK ERROR 500
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class QueueController extends Controller
{
    // --- FRONTEND: HALAMAN KIOSK (Ambil Antrian) ---
    public function kiosk()
    {
        return view('frontend.queue.kiosk');
    }

    public function takeQueue(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'pax' => 'required|integer|min:1'
        ]);

        $prefix = 'A';
        if ($request->pax >= 3 && $request->pax <= 4) $prefix = 'B';
        elseif ($request->pax >= 5) $prefix = 'C';

        $today = Carbon::today();
        $lastQueue = Queue::whereDate('created_at', $today)
            ->where('queue_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')->first();

        $nextNumber = 1;
        if ($lastQueue) {
            $nextNumber = intval(substr($lastQueue->queue_number, 1)) + 1;
        }

        $queue_number = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $queue = Queue::create([
            'queue_number' => $queue_number,
            'customer_name' => $request->customer_name,
            'pax' => $request->pax,
            'status' => 'waiting'
        ]);

        // 🔥 TEMBAKKAN SINYAL KE KASIR BAHWA ADA ANTRIAN BARU!
        // Gunakan try-catch agar tidak Error 500 jika Reverb mati
        try {
            broadcast(new NewQueueEvent());
        } catch (\Exception $e) {
            \Log::error("Gagal Broadcast Antrian Baru: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'queue_number' => $queue->queue_number,
            'customer_name' => $queue->customer_name,
            'message' => 'Antrian berhasil diambil!'
        ]);
    }

    // --- FRONTEND: HALAMAN TV DISPLAY ---
    public function display()
    {
        $today = \Carbon\Carbon::today();

        $lastA = Queue::whereDate('created_at', $today)->where('status', 'called')->where('queue_number', 'like', 'A%')->orderBy('updated_at', 'desc')->first();
        $lastB = Queue::whereDate('created_at', $today)->where('status', 'called')->where('queue_number', 'like', 'B%')->orderBy('updated_at', 'desc')->first();
        $lastC = Queue::whereDate('created_at', $today)->where('status', 'called')->where('queue_number', 'like', 'C%')->orderBy('updated_at', 'desc')->first();

        $waitingA = Queue::whereDate('created_at', $today)->where('status', 'waiting')->where('queue_number', 'like', 'A%')->orderBy('created_at', 'asc')->limit(5)->get();
        $waitingB = Queue::whereDate('created_at', $today)->where('status', 'waiting')->where('queue_number', 'like', 'B%')->orderBy('created_at', 'asc')->limit(5)->get();
        $waitingC = Queue::whereDate('created_at', $today)->where('status', 'waiting')->where('queue_number', 'like', 'C%')->orderBy('created_at', 'asc')->limit(5)->get();

        $setting = \App\Models\Setting::first();

        return view('frontend.queue.display', compact('lastA', 'lastB', 'lastC', 'waitingA', 'waitingB', 'waitingC', 'setting'));
    }

    public function index()
    {
        $today = Carbon::today();
        $queues = Queue::whereDate('created_at', $today)
            ->orderByRaw("
                CASE status 
                    WHEN 'waiting' THEN 1 
                    WHEN 'called' THEN 2 
                    WHEN 'seated' THEN 3 
                    WHEN 'cancelled' THEN 4 
                    ELSE 5 
                END
            ")
            ->orderBy('created_at', 'asc')
            ->get();

        $lastCall = Cache::get('last_audio_call');
        $cooldownLeft = 0;
        if ($lastCall) {
            $elapsed = time() - $lastCall;
            if ($elapsed < 15) {
                $cooldownLeft = 15 - $elapsed;
            }
        }

        return view('backend.queue.index', compact('queues', 'cooldownLeft'));
    }

    public function callQueue(Request $request)
    {
        $lastCall = Cache::get('last_audio_call');
        if ($lastCall && (time() - $lastCall) < 15) {
            return response()->json([
                'success' => false,
                'message' => 'Harap tunggu! Sedang ada pemanggilan lain yang berlangsung.'
            ], 429);
        }

        $queue = Queue::findOrFail($request->id);
        $queue->update(['status' => 'called']);

        $textToSpeak = "Nomor antrian, " . implode('-', str_split($queue->queue_number)) . ", atas nama, " . $queue->customer_name . ". Silakan menuju meja resepsionis.";

        $displayData = [
            'number' => $queue->queue_number,
            'name' => $queue->customer_name
        ];

        Cache::put('last_audio_call', time(), 15);

        // Gunakan try-catch agar tidak Error 500 jika Reverb mati
        try {
            broadcast(new CallQueueEvent($textToSpeak, $displayData, 'queue'));
        } catch (\Exception $e) {
            \Log::error("Gagal Memanggil Antrian (Broadcast): " . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Memanggil antrian ' . $queue->queue_number]);
    }

    public function updateStatus(Request $request, $id)
    {
        $queue = Queue::findOrFail($id);
        $queue->update(['status' => $request->status]);
        return response()->json(['success' => true]);
    }
}
