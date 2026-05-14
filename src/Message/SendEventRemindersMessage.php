<?php
// src/Message/SendEventRemindersMessage.php

namespace App\Message;

class SendEventRemindersMessage
{
    public function __construct(
        private int $hoursBefore = 24
    ) {}

    public function getHoursBefore(): int
    {
        return $this->hoursBefore;
    }
}