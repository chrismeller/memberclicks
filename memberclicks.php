<?php

	class MemberClicks {
		
		public $org_id;
		public $api_key;
		public $username;
		public $password;
		
		public $token;
		
		public function __construct ( $org_id = null, $api_key = null, $username = null, $password = null ) {
			
			$this->org_id = $org_id;
			$this->api_key = $api_key;
			$this->username = $username;
			$this->password = $password;
			
			$this->url = 'http://' . $org_id . '.memberclicks.net/services';
			
		}
		
		public function login ( ) {
			
			$params = array(
				'apiKey' => $this->api_key,
				'username' => $this->username,
				'password' => $this->password,
			);
			
			// @ because invalid credentials result in a 404
			$response = @$this->request( 'POST', '/auth', $params );
			
			// if the request failed (ie: in a 404), return false
			if ( $response === false ) {
				return false;
			}
			
			// make sure there's a token
			if ( isset( $response->token ) ) {
				// save it
				$this->token = $response->token;
				
				return $response->token;
			}
			
			return false;
			
		}
		
		private function request ( $method = 'GET', $uri, $params = array() ) {
			
			$options = array(
				'http' => array(
					'method' => $method,
					'timeout' => 60,
					'header' => array(
						'Accept: application/json',
					),
				),
			);
			
			if ( $method == 'POST' ) {
				$options['http']['header'][] = 'Content-Type: application/x-www-form-urlencoded';
				
				if ( !empty( $params ) ) {
					$options['http']['content'] = http_build_query( $params );
				}
			}
			
			// if we have a token, include it
			if ( $this->token != null ) {
				$options['http']['header'][] = 'Authorization: ' . $this->token;
			}
			
			$context = stream_context_create( $options );
			
			$url = $this->url . $uri;
			
			if ( $method == 'GET' && !empty( $params ) ) {
				$url .= '?' . http_build_query( $params );
			}
			
			$result = file_get_contents( $url, false, $context );
			
			return json_decode( $result );
			
		}
		
		public function get_attributes ( ) {
			
			$response = $this->request( 'GET', '/attribute' );
			
			$attributes = array();
			foreach ( $response->attribute as $a ) {
				
				$attributes[ $a->attName ] = $a;
				
			}
			
			return $attributes;
			
		}
		
		public function get_user_attributes ( $user_id ) {
			
			$response = @$this->request( 'GET', '/user/' . $user_id . '/attribute' );
			
			// was it a valid user?
			if ( $response == false ) {
				return false;
			}
			
			$attributes = array();
			foreach ( $response->attribute as $a ) {
				
				$attributes[ $a->attName ] = $a;
				
			}
			
			return $attributes;
			
		}
		
		public function get_users ( $filters = array() ) {
			
			$defaults = array(
				'searchText' => null,
				'type' => 'quick',
				'page' => null,
				'pageSize' => null,
				'active' => true,
				'deleted' => false,
				'valid' => true,
			);
			
			$params = array_merge( $defaults, $filters );
			
			$params['active'] = ( $params['active'] ) ? 'true' : 'false';
			$params['deleted'] = ( $params['deleted'] ) ? 'true' : 'false';
			$params['valid'] = ( $params['valid'] ) ? 'true' : 'false';
			
			$response = $this->request( 'GET', '/user', $params );
			
			// did we get any uers back?
			if ( $response == false ) {
				return false;
			}
			
			$users = array();
			if ( is_array( $response->user ) ) {
				foreach ( $response->user as $u ) {
					$users[ $u->userId ] = $u;
				}
			}
			else {
				$users[ $response->user->userId ] = $response->user;
			}
				
			return $users;
			
		}
		
		public function get_user ( $user_id, $include_attributes = false ) {
			
			// the API expects literal strings, the 1 / 0 type conversion in PHP won't cut it
			if ( $include_attributes ) {
				$include_attributes = 'true';
			}
			else {
				$include_attributes = 'false';
			}
			
			$response = @$this->request( 'GET', '/user/' . $user_id, array( 'includeAtts' => $include_attributes ) );
			
			// did we get back a user?
			if ( $response == false ) {
				return false;
			}
			
			$user = $response;
			$user->attributes = array();
			foreach ( $user->attribute as $a ) {
				$user->attributes[ $a->attName ] = $a;
			}
			
			unset( $user->attribute );
			
			return $user;
			
		}
		
	}

?>