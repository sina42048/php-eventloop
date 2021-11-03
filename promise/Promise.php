<?php

class Promise
{
    private $callback;
    private $thenCallback;
    private $rejectCallback;

    function __construct(callable $callback)
    {
        $this->callback = $callback;
        call_user_func($this->callback, [$this, 'resolve'], [$this, 'reject']);
        return $this;
    }

    public function then(callable $then)
    {
        $this->thenCallback = $then;
        return $this;
    }

    public function catch($callback)
    {
        $this->rejectCallback = $callback;
        return $this;
    }

    public function resolve($content = null)
    {
        Timer::setTimeout(function () use ($content) {
            call_user_func($this->thenCallback, $content);
        }, 0);
    }

    public function reject($content = null)
    {
        Timer::setTimeout(function () use ($content) {
            call_user_func($this->rejectCallback, $content);
        }, 0);
    }
}
