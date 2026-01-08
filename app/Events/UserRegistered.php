<?php

namespace App\Events;

use App\User;
use Illuminate\Queue\SerializesModels;

class UserRegistered
{
    use SerializesModels;

    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle()
    {
        // Activate the user's account
        $this->user->is_active = 1;
        $this->user->save();
    }
}
