<?php

namespace App\Message;

final class SendNewsletterMessage
{
    /*
     * Add whatever properties & methods you need to hold the
     * data for this message class.
     */
    private $userID;
    private $newsID;

    public function __construct(int $userID, int $newsID)
    {
        $this->userID = $userID;
        $this->newsID = $newsID;
    }

   public function getUserID(): int
    {
        return $this->userID;
    }

   public function getNewsID(): int
    {
        return $this->newsID;
    }
}
