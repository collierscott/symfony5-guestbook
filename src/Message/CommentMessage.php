<?php

namespace App\Message;

class CommentMessage
{
    /**
     * @var int $id
     */
    private $id;

    private $reviewUrl;

    /**
     * @var array $context
     */
    private $context;

    public function __construct(int $id, string $reviewUrl, array $context = [])
    {
        $this->id = $id;
        $this->context = $context;
        $this->reviewUrl = $reviewUrl;
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

    /**
     * @return string
     */
    public function getReviewUrl(): string
    {
        return $this->reviewUrl;
    }

}