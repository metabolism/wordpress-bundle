<?php

namespace App\Controller;

use App\Service\Context;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BlogController extends AbstractController
{
	public function frontAction(Context $context)
	{
		$context->add('section', 'Homepage');
		return $this->render('generic.html.twig', $context->toArray());
	}

	public function pageAction(Context $context)
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

	public function guideAction(Context $context)
	{
		$context->add('section', 'Single guide');
		return $this->render('generic.html.twig', $context->toArray());
	}

	public function guideArchiveAction(Context $context)
	{
		$context->add('section', 'Guide Archive');
		return $this->render('generic.html.twig', $context->toArray());
	}

	public function categoryAction(Context $context)
	{
		$category = $context->getTerm();
		$context->add('section', 'Single category : '.$category->title);

		return $this->render('generic.html.twig', $context->toArray());
	}
}