<?php

namespace App\Controller;

use App\Entity\Newsletters\Newsletters;
use App\Entity\Newsletters\Users;
use App\Form\NewslettersType;
use App\Form\NewslettersUsersType;
use App\Repository\Newsletters\NewslettersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;

/**
 * @Route("/newsletters", name="newsletters_")
 */
class NewslettersController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/", name="home")
     */
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $user = new Users();

        $form = $this->createForm(NewslettersUsersType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $token = hash('sha256', uniqid());

            $user->setValidationToken($token);

            $this->em->persist($user);
            $this->em->flush();

            $this->email = (new TemplatedEmail())
                ->from('newsletters@noreply')
                ->to($user->getEmail())
                ->subject('Validation de votre inscription à la newsletter')
                ->htmlTemplate('emails/inscription.html.twig')
                ->context(compact('user', 'token'))
            ;

            $mailer->send($this->email);

            $this->addFlash('message', 'Inscription en attente de validation : un e-mail de confirmation vient de vous être envoyé.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('newsletters/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/confirm/{id}/{token}", name="confirm")
     */
    public function confirm($token, Users $user): Response
    {
        if ($user->getValidationToken() !== $token) {
            throw $this->createNotFoundException('Page non trouvée');
        }

        $user->setIsValid(true);

        $this->em->persist($user);
        $this->em->flush();

        $this->addFlash('message', 'Compte activé');
        return $this->redirectToRoute('app_home');
    }

    /**
     * @Route("/prepare", name="prepare")
     */
    public function prepare(Request $request): Response
    {
        $newsletter = new Newsletters();

        $form = $this->createForm(NewslettersType::class, $newsletter);

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($newsletter);
            $this->em->flush();

            return $this->redirectToRoute('newsletters_list');
        }

        return $this->render('newsletters/prepare.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/list", name="list")
     */
    public function list(NewslettersRepository $newslettersRepo): Response
    {
        return $this->render('newsletters/list.html.twig', [
            'newsletters' => $newslettersRepo->findAll()
        ]);
    }

    /**
     * @Route("/send/{id}", name="send")
     */
    public function send(Newsletters $newsletter, MailerInterface $mailer): Response
    {

        $users = $newsletter->getCategories()->getUsers();

        foreach($users as $user) {
            if ($user->getIsValid()) {
                $this->email = (new TemplatedEmail())
                    ->from('newsletterq@noreply')
                    ->to($user->getEmail())
                    ->subject($newsletter->getName())
                    ->htmlTemplate('emails/newsletter.html.twig')
                    ->context(compact('newsletter',  'user'))
                ;

                $mailer->send($this->email);
            }
        }

        $newsletter->setIsSent(true);
        $this->em->flush();
        
        return $this->redirectToRoute('newsletters_list');
    }

    /**
     * @Route("/unsubscribe/{id}/{newsletter}/{token}", name="unsubscribe")
     */
    public function unsubscribe(Users $user, Newsletters $newsletter, $token): Response
    {
        if ($user->getValidationToken() !== $token) {
            throw $this->createNotFoundException('Page non trouvée');
        }

        if (count($user->getCategories()) > 1) {
            $user->removeCategory($newsletter->getCategories());
        } else {
            $this->em->remove($user);
        }

        $this->em->flush();

        $this->addFlash('message', 'Newsletter supprimée');
        return $this->redirectToRoute('app_home');
    }
}
