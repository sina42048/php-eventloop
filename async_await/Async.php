<?php

class Async
{

    private static $generator;

    private function __construct()
    {
    }

    public static function run($generator)
    {
        self::$generator = $generator();
        self::runGenerator();
    }

    public static function delay($delay)
    {
        return new Promise(function ($res, $rej) use ($delay) {
            Timer::setTimeout($res, $delay);
        });
    }

    private static function runGenerator()
    {
        try {
            echo self::$generator->getReturn();
        } catch (Exception $err) {
            if (self::$generator->current() !== null) {
                if (self::$generator->current() instanceof Promise) {
                    self::$generator->current()->then(function ($content = null) {
                        self::$generator->send($content);
                        self::runGenerator(self::$generator);
                    })->catch(function ($error) {
                        self::$generator->throw(new Error($error));
                        self::runGenerator(self::$generator);
                    });
                } else {
                    self::$generator->next();
                    self::runGenerator(self::$generator);
                }
            }
        }
    }
}
