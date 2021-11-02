<?php

class Timer
{
    public static $timers = [];
    public static $futureTicks = [];

    public static function setTimeout($callback, $delay)
    {
        $delay = $delay === 0 ? 0.1 : $delay;
        self::$timers[] = [
            'type' => 'timeout',
            'time' => hrtime(true) + ($delay * 1000000),
            'callback' => $callback,
            'happend' => false
        ];
        return array_key_last(self::$timers);
    }

    public static function setInterval($callback, $delay)
    {
        $delay = $delay === 0 ? 0.1 : $delay;
        self::$timers[] = [
            'type' => 'interval',
            'delay' => ($delay * 1000000),
            'time' => hrtime(true) + ($delay * 1000000),
            'callback' => $callback,
            'happend' => false // never change
        ];
        return array_key_last(self::$timers);
    }

    public static function setImmediate($callback)
    {
        self::$futureTicks[] = [
            'callback' => $callback
        ];
    }

    public static function clearTimeout($id)
    {
        unset(self::$timers[$id]);
    }

    public static function clearInterval($id)
    {
        unset(self::$timers[$id]);
    }
}
