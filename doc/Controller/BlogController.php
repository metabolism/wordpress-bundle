<?php

namespace App\Controller;

use App\Service\Context;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BlogController extends AbstractController
{
    public function frontAction(Context $context)
    {
        return $this->render('page/front.twig', $context->toArray());
    }
}