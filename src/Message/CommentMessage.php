<?php

namespace App\Message;

class CommentMessage
{
    /**
     * @var int $id
     */
    private $id;

    /**
     * @var array $context
     */
    private $context;

    public function __construct(int $id, array $context = [])
    {
        $this->id = $id;
        $this->context = $context;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

}