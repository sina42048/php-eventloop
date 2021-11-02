<?php

class Promise
{
    private $callback;
    private $thenCallback;

    function __construct(callable $callback)
    {
        $this->callback = $callback;
        call_user_func($this->callback, [$this, 'resolve']);
        return $this;
    }

    public function then(callable $then)
    {
        $this->thenCallback = $then;
    }

    public function resolve($content)
    {
        call_user_func($this->thenCallback, $content);
    }
}
