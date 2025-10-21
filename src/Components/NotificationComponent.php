<?php 

// src/Components/NotificationComponent.php

namespace App\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('notification_component')]
class NotificationComponent
{
    use DefaultActionTrait;

  

    public int $userId;
    public int $unreadNotificationCount;
    public array $topUnreadNotifications;

   

    public function mount(int $userId): void
    {
        
    }
}


?>