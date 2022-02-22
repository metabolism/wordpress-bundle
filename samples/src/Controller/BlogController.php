<?php

namespace App\Controller;

use Metabolism\WordpressBundle\Entity\Post;
use Metabolism\WordpressBundle\Entity\Term;

use Metabolism\WordpressBundle\Entity\User;
use Metabolism\WordpressBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BlogController extends AbstractController
{
    public function homeAction(array $posts, PaginationService $paginationService)
    {
        return $this->render('generic.html.twig', [
            'pagination'=>$paginationService->build(),
            'posts'=>$posts
        ]);
    }

    public function pageAction(Post $post)
    {
        return $this->render('generic.html.twig', ['post'=>$post]);
    }

    public function postAction(Post $post)
    {
        return $this->render('generic.html.twig', ['post'=>$post]);
    }

    public function guideAction(Post $post)
    {
        return $this->render('generic.html.twig', ['post'=>$post]);
    }

    public function searchAction(array $posts, PaginationService $paginationService, $search)
    {
        return $this->render('generic.html.twig', [
            'pagination'=>$paginationService->build(),
            'search_query'=>$search,
            'posts'=>$posts
        ]);
    }

    public function guideArchiveAction(array $posts, PaginationService $paginationService)
    {
        return $this->render('generic.html.twig', [
            'pagination'=>$paginationService->build(),
            'posts'=>$posts
        ]);
    }

    public function categoryAction(Term $term, array $posts, PaginationService $paginationService)
    {
        return $this->render('generic.html.twig', [
            'pagination'=>$paginationService->build(),
            'posts'=>$posts,
            'term'=>$term
        ]);
    }

    public function authorAction(User $user, array $posts, PaginationService $paginationService)
    {
        return $this->render('generic.html.twig', [
            'pagination'=>$paginationService->build(),
            'posts'=>$posts,
            'author'=>$user
        ]);
    }

    public function errorAction(\Throwable $exception)
    {
        $code = $exception->getCode();

        if( $code == 503 )
            $response = $this->render( 'generic.html.twig', ['error'=>true, 'code'=>$code] );
        else if( $code == 404 )
            $response = $this->render( 'generic.html.twig', ['error'=>true, 'code'=>$code] );
        else
            $response = $this->render( 'generic.html.twig', ['error'=>true, 'exception'=>$exception, 'code'=>$code] );

        $response->setStatusCode($code?:500);

        return $response;
    }
}