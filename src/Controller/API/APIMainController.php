<?php

namespace App\Controller\API;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class APIMainController extends AbstractController {
    #[Route('/posts_preview', name: 'api_posts_preview')]
    public function apiPostsPreview(PostRepository $postRepository) : Response {
        $posts = $postRepository->findAll();
        return $this->json($posts, 200, [], ['groups' => ['post_preview']]);
    }

    #[Route('/posts_details', name: 'api_posts_details')]
    public function apiPostsDetails(PostRepository $postRepository) : Response {
        $posts = $postRepository->findAll();
        return $this->json($posts, 200, [], ['groups' => ['post_preview', 'post_details']]);
    }

    #[Route(
        '/post_details/{slug}',
        name: 'api_post_show',
        requirements: ['slug' => '[a-zA-Z0-9-]+']
    )]
    public function apiPostShow(
        PostRepository $postRepository,
        string $slug
    ) : Response {
        $post = $postRepository->findBySlug($slug);
        return $this->json($post, 200, [], ['groups' => ['post_preview', 'post_details']]);
    }
}