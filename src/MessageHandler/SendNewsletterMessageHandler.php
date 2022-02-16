<?php

namespace App\MessageHandler;

use App\Entity\Newsletters\Newsletters;
use App\Entity\Newsletters\Users;
use App\Message\SendNewsletterMessage;
use App\Service\SendNewsletterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class SendNewsletterMessageHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;
    private SendNewsletterService $sendNewsletterService;

    public function __construct(EntityManagerInterface $em, SendNewsletterService $sendNewsletterService)
    {
        $this->em = $em;
        $this->sendNewsletterService = $sendNewsletterService;
    }

    public function __invoke(SendNewsletterMessage $message)
    {
        $user = $this->em->find(Users::class, $message->getUserID());
        $newsletter = $this->em->find(Newsletters::class, $message->getNewsID());

        if ($user !== null && $newsletter !== null) {
            $this->sendNewsletterService->send($user, $newsletter);
        }
    }
}
