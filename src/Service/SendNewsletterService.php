<?php

namespace App\Service;

use App\Entity\Newsletters\Users;
use App\Entity\Newsletters\Newsletters;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class SendNewsletterService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function send(Users $user, Newsletters $newsletter): void
    {
        /** Sert uniquement pour tester */
        // sleep(3);
        // throw new \Exception('- TEST : Message non envoyé TEST -');
        
        $this->email = (new TemplatedEmail())
            ->from('newsletterq@noreply')
            ->to($user->getEmail())
            ->subject($newsletter->getName())
            ->htmlTemplate('emails/newsletter.html.twig')
            ->context(compact('newsletter',  'user'))
        ;
    
        $this->mailer->send($this->email);
    }
}
