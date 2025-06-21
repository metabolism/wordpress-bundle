<?php

namespace Metabolism\WordpressBundle\ArgumentResolver;

use Metabolism\WordpressBundle\Entity\TermCollection;
use Metabolism\WordpressBundle\Interface\BCValueResolverInterface;
use Metabolism\WordpressBundle\Repository\TermRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class TermCollectionValueResolver implements BCValueResolverInterface {

    private $termRepository;

    /**
     * @param TermRepository $termRepository
     */
    public function __construct(TermRepository $termRepository)
    {
        $this->termRepository = $termRepository;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return (TermCollection::class === $argument->getType() && $argument->getName() == 'terms');
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return \Generator
     * @throws \Exception
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$this->supports($request, $argument)) {
            return [];
        }

        yield $this->termRepository->findQueried($argument->isNullable());
    }
}
