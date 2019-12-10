<?php

namespace App\Service;

use Metabolism\WordpressBundle\Traits\ContextTrait as WordpressContext;

class Context
{
	use WordpressContext;

	protected $data;

	/**
	 * Add generic entry
	 *
	 * @see Post
	 * @internal param null $id
	 * @param $key
	 * @param $value
	 */
	public function add($key, $value='')
	{
		if( is_array($key) )
		{
			$this->data = array_merge($this->data, $key);
		}
		else
		{
			if( is_object($this->data) )
				$this->data->$key = $value;
			else
				$this->data[$key] = $value;
		}
	}


	/**
	 * Remove generic entry
	 *
	 * @param $key
	 * @internal param null $id
	 * @see Post
	 */
	public function remove($key)
	{
		if( isset($this->data[$key]) )
			unset($this->data[$key]);
	}


	/**
	 * Get entry using dot notation
	 *
	 * @param $key
	 * @param bool $fallback
	 * @param bool $strict
	 * @return array|bool
	 * @see Post
	 * @internal param null $id
	 */
	public function get($key, $fallback=false, $strict=false)
	{
		$keys = explode('.', $key);
		$data = $this->data;

		foreach ($keys as $key)
		{
			if( isset(((array)$data)[$key]) )
				$data = is_object($data)?$data->$key:$data[$key];
			else
				return $fallback;
		}

		return (!$strict || $data ) ? $data : $fallback;
	}


	/**
	 * Output context Json Formatted
	 *
	 */
	public function debug()
	{
		header('Content-Type: application/json');

		echo json_encode($this->data);

		exit(0);
	}


	/**
	 * Return Context as Array
	 * @return array
	 */
	public function toArray()
	{
		$this->add('environment', $_SERVER['APP_ENV']);

		if( isset($_GET['debug']) && $_GET['debug'] == 'context' && $_SERVER['APP_ENV'] == 'dev' )
			$this->debug();

		return is_array($this->data) ? $this->data : [];
	}
}
