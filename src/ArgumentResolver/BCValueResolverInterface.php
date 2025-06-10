<?php
declare(strict_types=1);

namespace Metabolism\WordpressBundle\ArgumentResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * For BC compatibility with Symfony < 6.2
 */
if (interface_exists(ValueResolverInterface::class)) {
    interface BCValueResolverInterface extends ValueResolverInterface
    {
        public function supports(Request $request, ArgumentMetadata $argument): bool;
    }
} else {
    interface BCValueResolverInterface extends ArgumentValueResolverInterface
    {
    }
}
