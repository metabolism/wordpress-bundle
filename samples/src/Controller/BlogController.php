<?php

namespace App\Controller;

use Metabolism\WordpressBundle\Service\ContextService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BlogController extends AbstractController
{
	public function frontAction(ContextService $context)
	{
		$context->add('section', 'Homepage');
		return $this->render('generic.html.twig', $context->toArray());
	}

	public function pageAction(ContextService $context)
	{
		$post = $context->getPost();

		if( !$post ){

			$context->add('section', '404');
			$response =  $this->render('generic.html.twig', $context->toArray());
			$response->setStatusCode(404);

			return $response;
		}

		$context->add('section', 'Single page');
		return $this->render('generic.html.twig', $context->toArray());
	}

	public function guideAction(ContextService $context)
	{
		$context->add('section', 'Single guide');
		return $this->render('generic.html.twig', $context->toArray());
	}

	public function guideArchiveAction(ContextService $context)
	{
		$context->add('section', 'Guide Archive');
		return $this->render('generic.html.twig', $context->toArray());
	}

	public function categoryAction(ContextService $context)
	{
		$category = $context->getTerm();
		$context->add('section', 'Single category : '.$category->title);

		return $this->render('generic.html.twig', $context->toArray());
	}
}