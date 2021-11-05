<?php

class Promise
{
    private $callback;
    private $thenCallback;
    private $rejectCallback;

    function __construct(callable $callback)
    {
        $this->callback = $callback;
        Timer::setTimeout(function () {
            call_user_func($this->callback, [$this, 'resolve'], [$this, 'reject']);
        }, 0);
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
        call_user_func($this->thenCallback, $content);
    }

    public function reject($content = null)
    {
        call_user_func($this->rejectCallback, $content);
    }
}
