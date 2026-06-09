<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallQueueEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $text_to_speak;
    public $display_data;
    public $type; // 'queue' atau 'food'

    public function __construct($text_to_speak, $display_data, $type = 'queue')
    {
        $this->text_to_speak = $text_to_speak;
        $this->display_data = $display_data;
        $this->type = $type;
    }

    // Channel public agar TV Display bisa mendengar tanpa harus login
    public function broadcastOn()
    {
        return new Channel('public-display');
    }

    public function broadcastAs()
    {
        return 'call-event';
    }
}
