<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/post')]
class PostController extends AbstractController
{
    #[Route('/', name: 'app_post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository): Response
    {
        // Forbid access to not logged in users
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        };

        return $this->render('post/index.html.twig', [
            'posts' => $postRepository->findBy(['author' => $this->getUser()]),
        ]);
    }

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response
    {
        // Forbid access to not logged in users
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        };

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            # Setting up the slug
            $post->setSlug($slugger->slug($post->getTitle()));

            # Setting up the author
            $post->setAuthor($this->getUser());

            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/show', name: 'app_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        // Forbid access to not logged in users
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        };

        // Forbid users to see posts that they don't own
        if($post->getAuthor() != $this->getUser()){
            return $this->redirectToRoute('app_index');
        };

        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Post $post,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response
    {
        // Forbid access to not logged in users
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        };

        // Forbid users to edit posts that they don't own
        if($post->getAuthor() != $this->getUser()){
            return $this->redirectToRoute('app_index');
        };

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            # Setting up the slug
            $post->setSlug($slugger->slug($post->getTitle()));

            $entityManager->flush();

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        // Forbid access to not logged in users
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        };

        // Forbid users to delete posts that they don't own
        if($post->getAuthor() != $this->getUser()){
            return $this->redirectToRoute('app_index');
        };

        if($this->isCsrfTokenValid('delete'.$post->getId(), $request->getPayload()->getString('_token'))){
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
    }
}