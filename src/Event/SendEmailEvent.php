<?php 

namespace App\Event;

use App\Service\MailService;
use Symfony\Contracts\EventDispatcher\Event;

class SendEmailEvent extends Event
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }
}