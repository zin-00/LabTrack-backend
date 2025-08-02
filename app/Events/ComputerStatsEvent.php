<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComputerStatsEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $computer;
    public function __construct($computer)
    {
        $this->computer = $computer;
    }

    public function broadcastAs()
    {
        return 'ComputerStatsEvent';
    }

    public function broadcastWith(){
        return [
            'computer' => $this->computer,
            'ip_address' => $this->computer->ip_address,
            'is_online' => $this->computer->is_online,
            'is_lock' => $this->computer->is_lock,
        ];
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('computers'),
        ];
    }
}
