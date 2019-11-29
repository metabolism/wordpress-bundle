<?php

namespace Metabolism\WordpressBundle\Plugin;

/**
 * Class MailPlugin
 * @description Send Emails from custom SMTP set in .env
 * @package Metabolism\WordpressBundle\Plugin
 */
class MailPlugin {

	protected $_smtp_config;

	public function __construct( $config )
	{
		$mailer_url = getenv('MAILER_URL');

		if( $mailer_url && !empty($mailer_url) ){

			$this->setSMTPConfig($mailer_url);

			if($this->_smtp_config['scheme'] != null) {

				add_action( 'phpmailer_init', array( $this, 'configureSmtp' ) );
				add_filter( 'wp_mail_content_type', function($content_type) { return "text/html"; } );
				add_filter( 'wp_mail_from', array( $this, 'fromEmail' ) );
				add_filter( 'wp_mail_from_name', array( $this, 'fromName' ) );
			}
		}
	}

	/**
	 * Set SMTP Config from MAILER_URL in .env.
	 * @param null|string $url
	 */
	public function setSMTPConfig( $url ){

		$this->_smtp_config = [];

		if(!empty($url)){

			$this->_smtp_config = parse_url($url);

			if(!empty($this->_smtp_config['query'])){

				parse_str($this->_smtp_config['query'], $query);
				$this->_smtp_config += $query;
			}
		}
	}

	/**
	 * Override From Name
	 * @param $name
	 * @return string|void
	 */
	public function fromName( $name ){

		if($blogName = get_bloginfo('blog_name'))
			return $blogName;

		return $name;
	}

	/**
	 * Override From Email
	 * @param $email
	 * @return mixed
	 */
	public function fromEmail( $email ){

		if(!empty($this->_smtp_config['user']) && is_email($this->_smtp_config['user']))
			return $this->_smtp_config['user'];

		return $email;
	}

	/**
	 * Configure PHPMailer
	 * @param $phpmailer
	 */
	public function configureSmtp( $phpmailer )
	{
		$phpmailer->isSMTP();
		$phpmailer->Host = $this->_smtp_config['host'];

		$SMTPAuth = (!empty($this->_smtp_config['auth_mode']) && $this->_smtp_config['auth_mode'] == 'login');

		$phpmailer->SMTPAuth = $SMTPAuth;

		if((bool) $SMTPAuth){
			$phpmailer->Port = $this->_smtp_config['port'] ?? 25;
			$phpmailer->Username = $this->_smtp_config['user'];
			$phpmailer->Password = urldecode($this->_smtp_config['pass']);
		}

		$phpmailer->SMTPSecure = $this->_smtp_config['encryption'] ?? null;
	}
}