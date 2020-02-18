<?php

namespace Metabolism\WordpressBundle\Plugin;


use Dflydev\DotAccessData\Data;
use Metabolism\WordpressBundle\Traits\SingletonTrait;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class GUIDPlugin {

	use SingletonTrait;

	/**
	 * RFC 4122 compliant UUID version 5.
	 *
	 * @param  string $name    The name to generate the UUID from.
	 * @param  string $ns_uuid Namespace UUID. Default is for the NS when name string is a URL.
	 * @return string          The UUID string.
	 */
	private function uuid( $name, $ns_uuid = '6ba7b811-9dad-11d1-80b4-00c04fd430c8' ) {

		// Compute the hash of the name space ID concatenated with the name.
		$hash = sha1( $ns_uuid . $name );

		// Intialize the octets with the 16 first octets of the hash, and adjust specific bits later.
		$octets = str_split( substr( $hash, 0, 16 ), 1 );

		/*
		 * Set version to 0101 (UUID version 5).
		 *
		 * Set the four most significant bits (bits 12 through 15) of the
		 * time_hi_and_version field to the appropriate 4-bit version number
		 * from Section 4.1.3.
		 *
		 * That is 0101 for version 5.
		 * time_hi_and_version is octets 6–7
		 */
		$octets[6] = chr( ord( $octets[6] ) & 0x0f | 0x50 );

		/*
		 * Set the UUID variant to the one defined by RFC 4122, according to RFC 4122 section 4.1.1.
		 *
		 * Set the two most significant bits (bits 6 and 7) of the
		 * clock_seq_hi_and_reserved to zero and one, respectively.
		 *
		 * clock_seq_hi_and_reserved is octet 8
		 */
		$octets[8] = chr( ord( $octets[8] ) & 0x3f | 0x80 );

		// Hex encode the octets for string representation.
		$octets = array_map( 'bin2hex', $octets );

		// Return the octets in the format specified by the ABNF in RFC 4122 section 3.
		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( implode( '', $octets ), 4 ) );
	}


	/**
	 * Update UUID
	 * @param $post_ID
	 * @param null $post
	 * @param bool $update
	 */
	public function update($post_ID, $post = null, $update = false){

		if ( !$update ) {

			global $wpdb;

			$where = ['ID' => $post_ID];

			$wpdb->update( $wpdb->posts, array(
				'guid' => 'urn:uuid:' . $this->uuid( WP_HOME.'?p='.$post_ID ),
			), $where );
		}
	}


	/**
	 * constructor.
	 * @param Data $config
	 */
	public function __construct($config){

		add_action( 'save_post', [$this, 'update'], 10, 3 );
	}
}
