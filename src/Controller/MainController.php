<?php

namespace App\Controller;

use App\Form\ContactFormType;
use App\Repository\PostRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController {
    #[Route('/', name: 'app_index')]
    public function index(PostRepository $post_repo) : Response {
        $posts = $post_repo->findAll();
        foreach ($posts as $post){
            $summary_length = strlen($post->getSummary());
            if($summary_length > 128){
                $post->setSummary(substr($post->getSummary(), 0, 127)."...");
            };
        };
        return $this->render('main/index.html.twig', compact('posts'));
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(
        Request $request,
        MailerInterface $mailer
    ) : Response {
        # Creating the contact form
        $contact_form = $this->createForm(ContactFormType::class);
        $contact_form->handleRequest($request);

        # Checking if the form is submitted and is valid
        if($contact_form->isSubmitted() && $contact_form->isValid()){
            # Creating the email
            $help_seeker_address = $contact_form->get('email')->getData();
            $email = (new TemplatedEmail())
                ->from(new Address($help_seeker_address))
                ->to($this->getParameter('dev_email'))
                ->subject("Suggestion/demande d'assistance")
                ->htmlTemplate('emails/support_request_email.html.twig')
                ->context([
                    'help_seeker_address' => $help_seeker_address,
                    'message' => $contact_form->get('message')->getData()
                ])
            ;

            # Attempting to sending the email
            try{
                $mailer->send($email);
            }
            catch(\Throwable $th){
                $this->addFlash(
                    'error',
                    "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer ultérieurement."
                );
                return $this->render('main/contact.html.twig', compact('contact_form'));
            };
            $this->addFlash(
                'success',
                "Votre message a été envoyé avec succès ! Notre équipe le traîtera dans les plus brefs délais."
            );
            return $this->redirectToRoute('app_index');
        };

        # Displaying the form
        return $this->render('main/contact.html.twig', compact('contact_form'));
    }

    #[Route(
        '/see_post/{slug}',
        name: 'app_see_post',
        requirements: ['slug' => '[a-zA-Z0-9-]+']
    )]
    public function seePost(
        Request $request,
        PostRepository $post_repo,
        string $slug
    ) : Response
    {
        $post = $post_repo->findOneBy(['slug' => $slug]);
        return $this->render('main/see_post.html.twig', compact('post'));
    }
}