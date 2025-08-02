<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComputerUnlockRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $computerId;
    public $studentId;
    public function __construct($computerId, $studentId)
    {
        $this->computerId = $computerId;
        $this->studentId = $studentId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('computer.' .$this->computerId),
        ];
    }

    public function broadcastAs(){
        return 'ComputerUnlockRequested';
    }
}
