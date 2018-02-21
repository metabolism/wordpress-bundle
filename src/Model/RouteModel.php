<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressLoader\Model;


class RouteModel extends \Symfony\Component\Routing\Route
{

    public function bind($string)
    {
        return $this;
    }

    public function assert($variable, $regexp)
    {
	    $this->setRequirement($variable, $regexp);
        return $this;
    }

    public function value($variable, $default)
    {
	    $this->setDefault($variable, $default);
        return $this;
    }

	public function convert($variable, $callback)
	{
		$converters = $this->getOption('_converters');
		$converters[$variable] = $callback;
		$this->setOption('_converters', $converters);

		return $this;
	}

	public function method($method)
	{
		$this->setMethods(explode('|', $method));
		return $this;
	}

    public function values($string)
    {
        return $this;
    }

    public function option($string)
    {
        return $this;
    }
}
