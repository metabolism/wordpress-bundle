<?php

namespace Metabolism\WordpressBundle\ArgumentResolver;

use Metabolism\WordpressBundle\Entity\Term;
use Metabolism\WordpressBundle\Repository\TermRepository;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class TermValueResolver implements ArgumentValueResolverInterface {

    private $termRepository;

    public function __construct(TermRepository $termRepository)
    {
        $this->termRepository = $termRepository;
    }

    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return Term::class === $argument->getType();
    }

    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->termRepository->findQueried();
    }
}
