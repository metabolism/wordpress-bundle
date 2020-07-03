<?php

namespace Metabolism\WordpressBundle\Helper;

class DirFilter extends \RecursiveFilterIterator
{
	protected $exclude;

	public function __construct($iterator, array $exclude)
	{
		parent::__construct($iterator);
		$this->exclude = $exclude;
	}

	public function accept()
	{
		return !($this->isDir() && in_array($this->getFilename(), $this->exclude));
	}

	public function getChildren()
	{
		return new DirFilter($this->getInnerIterator()->getChildren(), $this->exclude);
	}
}
