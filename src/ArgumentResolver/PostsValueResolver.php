<?php

namespace Metabolism\WordpressBundle\ArgumentResolver;

use Metabolism\WordpressBundle\Repository\PostRepository;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class PostsValueResolver implements ArgumentValueResolverInterface {

    private $postRepository;

    public function __construct(PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;
    }

    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return ('array' === $argument->getType() && in_array($argument->getName(), ['posts','pages']));
    }

    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->postRepository->findQueried();
    }
}
