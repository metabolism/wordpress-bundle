<?php

namespace Metabolism\WordpressBundle\ArgumentResolver;

use Metabolism\WordpressBundle\Entity\Blog;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class BlogValueResolver implements ArgumentValueResolverInterface {

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return Blog::class === $argument->getType();
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return \Generator
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield Blog::getInstance();
    }
}
