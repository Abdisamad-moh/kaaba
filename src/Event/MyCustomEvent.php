<?php 

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MyCustomEvent extends Event
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData(): string
    {
        return $this->data;
    }
}