<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController {
    #[Route('/', name: 'app_index')]
    public function index(PostRepository $post_repo) : Response {
        $posts = $post_repo->findAll();
        return $this->render('main/index.html.twig', compact('posts'));
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