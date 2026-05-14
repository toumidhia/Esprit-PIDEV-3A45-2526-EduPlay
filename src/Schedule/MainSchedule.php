<?php
// src/Schedule/MainSchedule.php

namespace App\Schedule;

use App\Message\SendEventRemindersMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]  // 👈 CET ATTRIBUT EST CRUCIAL
class MainSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                RecurringMessage::cron('0 8 * * *', new SendEventRemindersMessage(24))
            )
            ->add(
                RecurringMessage::cron('0 12 * * *', new SendEventRemindersMessage(4))
            )
            ->stateful($this->cache);
    }
}