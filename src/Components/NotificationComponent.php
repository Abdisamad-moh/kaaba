<?php 

// src/Components/NotificationComponent.php

namespace App\Components;

use App\Repository\MetierNotificationRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('notification_component')]
class NotificationComponent
{
    use DefaultActionTrait;

    private MetierNotificationRepository $notificationRepository;

    public int $userId;
    public int $unreadNotificationCount;
    public array $topUnreadNotifications;

    public function __construct(MetierNotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    public function mount(int $userId): void
    {
        $this->userId = $userId;
        $this->unreadNotificationCount = $this->notificationRepository->countUnreadNotifications($userId);
        $this->topUnreadNotifications = $this->notificationRepository->findUnreadNotifications($userId, 10);
    }
}


?>