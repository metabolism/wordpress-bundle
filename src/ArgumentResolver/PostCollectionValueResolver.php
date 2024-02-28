<?php

namespace Metabolism\WordpressBundle\ArgumentResolver;

use Metabolism\WordpressBundle\Entity\PostCollection;
use Metabolism\WordpressBundle\Repository\PostRepository;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class PostCollectionValueResolver implements ArgumentValueResolverInterface {

    private $postRepository;

    /**
     * @param PostRepository $postRepository
     */
    public function __construct(PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return (PostCollection::class === $argument->getType() && in_array($argument->getName(), ['posts','pages']));
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return \Generator
     * @throws \Exception
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield $this->postRepository->findQueried($argument->isNullable());
    }
}
