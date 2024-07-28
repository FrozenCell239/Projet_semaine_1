<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/post')]
class PostController extends AbstractController
{
    private const string PICTURES_DIR = 'posts';

    #[Route('/', name: 'app_post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository): Response
    {
        # Forbid access to not logged in users
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
        EntityManagerInterface $entity_manager,
        SluggerInterface $slugger,
        PictureService $pic_service
    ): Response
    {
        # Forbid access to not logged in users
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        };

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            # Setting up the slug
            $post->setSlug($slugger->slug($post->getTitle())->lower());

            # Setting up the author
            $post->setAuthor($this->getUser());

            # Getting the images
            $images = $form->get('images')->getData();
            foreach($images as $image){
                $file = $pic_service->addPicture($image, $this::PICTURES_DIR, 300, 300);
                $new_image = new Image();
                $new_image->setName($file);
                $post->addImage($new_image);
            };

            # Saving the new post
            $entity_manager->persist($post);
            $entity_manager->flush();

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route(
        '/{id}/show',
        name : 'app_post_show',
        requirements: ['id' => '[0-9]+'],
        methods: ['GET']
    )]
    public function show(Post $post): Response
    {
        # Forbid access to not logged in users
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        };

        # Forbid users to see posts that they don't own
        if($post->getAuthor() != $this->getUser()){
            return $this->redirectToRoute('app_index');
        };

        return $this->render('post/show.html.twig', ['post' => $post]);
    }

    #[Route(
        '/{id}/edit',
        name: 'app_post_edit',
        requirements: ['id' => '[0-9]+'],
        methods: ['GET', 'POST']
    )]
    public function edit(
        Request $request,
        Post $post,
        EntityManagerInterface $entity_manager,
        SluggerInterface $slugger,
        PictureService $pic_service
    ) : Response
    {
        # Forbid access to not logged in users
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        };

        # Forbid users to edit posts that they don't own
        if($post->getAuthor() != $this->getUser()){
            return $this->redirectToRoute('app_index');
        };

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            # Setting up the slug
            $post->setSlug($slugger->slug($post->getTitle())->lower());

            # Getting the images
            $images = $form->get('images')->getData();
            foreach($images as $image){
                $file = $pic_service->addPicture($image, $this::PICTURES_DIR, 300, 300);
                $new_image = new Image();
                $new_image->setName($file);
                $post->addImage($new_image);
            };

            # Saving the edited post
            $entity_manager->flush();

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route(
        '/{id}/delete',
        name: 'app_post_delete',
        requirements: ['id' => '[0-9]+'],
        methods: ['POST']
    )]
    public function delete(
        Request $request,
        Post $post,
        EntityManagerInterface $entity_manager,
        PictureService $pic_service
    ) : Response
    {
        # Forbid access to not logged in users
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        };

        # Forbid users to delete posts that they don't own
        if($post->getAuthor() != $this->getUser()){
            return $this->redirectToRoute('app_index');
        };

        # Deleting the post and its images
        if($this->isCsrfTokenValid('delete'.$post->getId(), $request->getPayload()->getString('_token'))){
            foreach($post->getImages() as $image){
                # Deleting the images on the server
                try{
                    $pic_service->deletePicture($image->getName(), $this::PICTURES_DIR, 300, 300);
                }
                catch(\Throwable $th){
                    return new JsonResponse(['error' => "Erreur de suppression."], 400);
                };

                # Unregistering the image from the database
                $entity_manager->remove($image);
            };

            $entity_manager->remove($post);
            $entity_manager->flush();
        };

        return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route(
        '/delete/image/{id}',
        name: 'app_post_image_delete',
        requirements: ['id' => '[0-9]+'],
        methods: ['DELETE']
    )]
    public function imageDelete(
        Image $image,
        Request $request,
        EntityManagerInterface $entityManager,
        PictureService $pic_service
    ) : JsonResponse
    {
        # Get request content
        $data = json_decode($request->getContent(), true);
        if($this->isCsrfTokenValid('delete'.$image->getId(), $data['_token'])){ //Checks token's validity.
            $name = $image->getName();
            try{
                $pic_service->deletePicture($name, $this::PICTURES_DIR, 300, 300);
            }
            catch(\Throwable $th){
                return new JsonResponse(['error' => "Erreur de suppression."], 400);
            };

            # Unregistering image from database
            $entityManager->remove($image);
            $entityManager->flush();

            return new JsonResponse(['success' => true], 200);
        };
        return new JsonResponse(['error' => "Invalid token."], 400);
    }
}