<?php namespace Abreeden\TransmitsmsApi;
 
class TransmitsmsApi 
{
		public static $url='https://api.transmitsms.com/';
		
		protected static $version=2;
		
		protected static $authHeader;
		
		protected static $responseRawData;
		
		protected static $responseStatus;
		
		public function __construct($key, $secret)
		{
			self::$authHeader=array('Authorization: Basic '.base64_encode($key.':'.$secret));
		}
		
		protected static function generateError($code, $description)
		{
			$error=new stdClass();
			$error->error->code=$code;
			$error->error->description=$description;
			return $error;
		}		
		
		protected static function getRequestURL($method)
		{
			return self::$url.'/'.self::$version.'/'.$method.'.json';
		}
		
		protected static function request($method, $params=array())
		{
			$requestUrl=self::getRequestURL($method);
			
			$ch = curl_init($requestUrl);
			if (! $ch) {
				return self::generateError('REQUEST_FAILED' ,"Error connecting to the server {$requestUrl} : ". curl_errno($ch) .':'. curl_error($ch));
			}			
			
			$urlInfo = parse_url($requestUrl);
			$port = (preg_match("/https|ssl/i", $urlInfo["scheme"])) ? 443 : 80;	
			
//			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			curl_setopt($ch, CURLOPT_USERAGENT, "transmitsmsAPI v.2");
			curl_setopt($ch, CURLOPT_PORT, $port);
			curl_setopt($ch, CURLOPT_SSLVERSION, 6);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
			curl_setopt($ch, CURLOPT_HTTPHEADER, self::$authHeader);
			
			self::$responseRawData = curl_exec($ch);
			if (! self::$responseRawData) {
				return self::generateError('REQUEST_FAILED' ,"Problem executing request, try changing above set options and re-requesting : ".curl_errno($ch) .':' .curl_error($ch));
			}
			self::$responseStatus=curl_getinfo($ch, CURLINFO_HTTP_CODE);
			return json_decode(self::$responseRawData);//, false, 512, JSON_BIGINT_AS_STRING);
		}
		
		protected static function handleResponse($response)
		{
			if($response===null)
				return self::generateError("INVALID_RESPONSE", "Invalid response, received data: ".self::$responseRawData);
			//possible checks for login failure and other common mistakes	
			return $response;
		}
		
		protected static function indexCustomFields(&$params, $fields)
		{
			if(!count($fields))
				return;	
			if(isset($fields[0]))
			{
				// this is not an associative array, we iterate and indexify from 1 to 10
				$fieldIndex=1;
				foreach($fields as $field)
				{
					$params["field.{$fieldIndex}"]=$field;
					$fieldIndex++;
				}
			}
			else
			{
				// this is an associative array, we iterate and keep the indexes
				foreach($fields as $fieldIndex=>$field)
				{
					$params["field.{$fieldIndex}"]=$field;
				}				
			}	
		}
		
		protected static function prepareFieldsForEdit(&$params)
		{
			foreach ($params as $key=>$value) {
				if($value===null)
					unset($params[$key]);
			}
		}
		
		/**
		 * Send SMS messages.
		 * 
		 * @param string $message 
		 * @param string $to - required if list_id is not set
		 * @param string $from
		 * @param datetime $send_at
		 * @param int $list_id - required if to is not set
		 * @param string $dlr_callback
		 * @param string $reply_callback
		 * @param int $validity
         *
		 */
		public function sendSms($message, $to='', $from='', $send_at='', $list_id=0, $dlr_callback='', $reply_callback='', $validity=0)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('send-sms', $params));			
		}
		
		/**
		 * Get data about a sent message.
		 * 
		 * @param int $message_id
		 * 
		 */
		public static function getSms($message_id)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('get-sms', $params));
		}
		
		/**
		 * Get sent messages.
		 * 
		 * @param int $message_id
		 * @param int $page
		 * @param int $max
		 * @param string $optouts can be 'only', 'omit', 'include'
		 *  
		 */
		public static function getSmsSent($message_id, $page=1, $max=10, $optouts='include')
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('get-sms-sent', $params));
		}
		
		/**
		 * Get SMS responses.
		 * 
		 * @param int $message_id
		 * @param int $keyword_id
		 * @param string $keyword
		 * @param string $number
		 * @param string $msisdn
		 * @param int $page
		 * @param int $max
		 * 
		 */
		public static function getSmsResponses($message_id, $keyword_id=0, $keyword='', $number='', $msisdn='', $page=1, $max=10)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('get-sms-responses', $params));			
		}

		/**
		 * Get SMS responses for user
		 * 
		 * @param datetime $start
		 * @param datetime $end
		 * @param int $page
		 * @param int $max
		 * @param string $keywords 
		 * 
		 */
		public static function getUserSmsResponses($start=null, $end=null, $page=1, $max=10, $keywords='both', $number = '')
		{
			$params = get_defined_vars();
			self::prepareFieldsForEdit($params);
			return self::handleResponse(self::request('get-user-sms-responses', $params));			
		}

		/**
		 * Get SMS sent by user in certain time frame
		 * 
		 * @param datetime $start
		 * @param datetime $end
		 * @param int $page
		 * @param int $max
		 * 
		 */
		public static function getUserSmsSent($start=null, $end=null, $page=1, $max=10)
		{
			$params = get_defined_vars();
			self::prepareFieldsForEdit($params);
			return self::handleResponse(self::request('get-user-sms-sent', $params));			
		}
		
		/**
		 * Cancel a scheduled SMS
		 * 
		 * @param int $id
		 * 
		 */
		public static function cancelSms($id)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('cancel-sms', $params));			
		}
		
		
		/**
		 * Get information about a list and its members.
		 * 
		 * @param int $list_id
		 * @param int $page
		 * @param int $max
		 * @param string $members can be 'active', 'inactive', 'all', 'none'
		 * 
		 */
		public static function getList($list_id, $page=1, $max=10, $members='active')
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('get-list', $params));
		}
		
		/**
		 * Get the metadata of your lists.

		 * @param int $page
		 * @param int $max
		 * 
		 */
		public static function getLists($page=1, $max=10)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('get-lists', $params));			
		}
		
		/**
		 * Create a new list.
		 * 
		 * @param string $name
		 * @param array $fields
		 * 
		 */
		public static function addList($name, $fields=array())
		{
			$params['name']=$name;
			self::indexCustomFields($params, $fields);
			return self::handleResponse(self::request('add-list', $params));
		}
		
		/**
		 * Add a member to a list.
		 * 
		 * @param int $list_id
		 * @param string $msisdn
		 * @param string $first_name
		 * @param string $last_name
		 * @param array $fields
		 * 
		 */
		public static function addToList($list_id, $msisdn, $first_name='', $last_name='', $fields=array())
		{
			$params=get_defined_vars();
			unset($params['fields']);
			self::indexCustomFields($params, $fields);
			return self::handleResponse(self::request('add-to-list', $params));			
		}
		
		/**
		 * Edit a list member.
		 * 
		 * @param int $list_id
		 * @param string $msisdn
		 * @param string $first_name
		 * @param string $last_name
		 * @param array $fields
		 * 
		 */
		public static function editListMember($list_id, $msisdn, $first_name=null, $last_name=null, $fields=array())
		{
			$params=get_defined_vars();
			unset($params['fields']);
			self::indexCustomFields($params, $fields);
			self::prepareFieldsForEdit($params);
			return self::handleResponse(self::request('edit-list-member', $params));			
		}
		
		/**
		 * Remove a member from one list or all lists.
		 * 
		 * @param int $list_id
		 * @param string $msisdn
		 * 
		 */
		public static function deleteFromList($list_id, $msisdn)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('delete-from-list', $params));
		}
		
		/**
		 * Opt-out a member from one list or all lists.
		 * 
		 * @param int $list_id
		 * @param string $msisdn
		 * 
		 */
		public static function optoutListMember($list_id, $msisdn)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('optout-list-member', $params));
		}		
		
		/**
		 * Get leased number details 
		 * 
		 * @param string $number
		 * 
		 */
		
		public static function getNumber($number)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('get-number', $params));
		}
		
		/**
		 * Get a list of numbers.
		 * 
		 * @param int $page
		 * @param int $max
		 * @param string $filter
		 * 
		 */
		public static function getNumbers($page=1, $max=10, $filter='owned')
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('get-numbers', $params));
		}
		
		/**
		 * Lease a response number.
		 * 
		 * @param string $number
		 * 
		 */
		public static function leaseNumber($number='', $url='')
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('lease-number', $params));
		}
		
		/**
		 * Get a client.
		 * 
		 * @param int $client_id
		 * 
		 */
		public static function getClient($client_id)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('get-client', $params));
		}
		
		/**
		 * Get a list of clients.
		 * 
		 * @param int $page
		 * @param int $max
		 * 
		 */		
		public static function getClients($page=1, $max=10)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('get-clients', $params));			
		}
		
		/**
		 * Add a new client
		 * 
		 * @param string $name
		 * @param string $email
		 * @param string $password
		 * @param string $msisdn
		 * @param string $contact
		 * @param string $timezone
		 * @param bool $client_pays
		 * @param float $sms_margin
		 * 
		 */
		public static function addClient($name, $email, $password, $msisdn, $contact='', $timezone='', $client_pays=true, $sms_margin=0.0)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('add-client', $params));
		}
		
		/**
		 * Edit a client
		 *
		 * @param int $id
		 * @param string $name
		 * @param string $email
		 * @param string $password
		 * @param string $msisdn
		 * @param string $contact
		 * @param string $timezone
		 * @param bool $client_pays
		 * @param float $sms_margin
		 * 
		 */
		public static function editClient($client_id, $name=null, $email=null, $password=null, $msisdn=null, $contact=null, $timezone=null, $client_pays=null, $sms_margin=null)
		{
			$params = get_defined_vars();
			self::prepareFieldsForEdit($params);
			return self::handleResponse(self::request('edit-client', $params));
		}
		
		/**
		 * Add a keyword to your existing response number.
		 * 
		 * @param string $keyword
		 * @param string $number
		 * @param string $reference
		 * @param int $list_id
		 * @param string $welcome_message
		 * @param string $members_message
		 * @param bool $activate
		 * @param string $forward_url
		 * @param string $forward_email
		 * @param string $forward_sms
		 * 
		 */
		public static function addKeyword($keyword, $number, $reference='', $list_id=0, $welcome_message='', $members_message='', $activate=true, $forward_url='', $forward_email='', $forward_sms='')
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('add-keyword', $params));
		}
		
		/**
		 * Edit an existing keyword.
		 * 
		 * @param string $keyword
		 * @param string $number
		 * @param string $reference
		 * @param int $list_id
		 * @param string $welcome_message
		 * @param string $members_message
		 * @param string $status
		 * @param string $forward_url
		 * @param string $forward_email
		 * @param string $forward_sms
		 * 
		 */
		public static function editKeyword($keyword, $number, $reference=null, $list_id=null, $welcome_message=null, $members_message=null, $status=null, $forward_url=null, $forward_email=null, $forward_sms=null)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('edit-keyword', $params));
		}
		
		/**
		 * Get a list of existing keywords.
		 * 
		 * @param string $number
		 * @param int $page
		 * @param int $max
		 * 
		 */
		public static function getKeywords($number='',$page=1, $max=10)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('get-keywords', $params));			
		}
		
		/**
		 * Get a list of transactions for a client.
		 * 
		 * @param int $client_id
		 * @param datetime $start
		 * @param datetime $end
		 * 
		 */
		public static function getTransactions($client_id, $start=null, $end=null, $page=1, $max=10)
		{
			$params = get_defined_vars();
			self::prepareFieldsForEdit($params);
			return self::handleResponse(self::request('get-transactions', $params));
		}
		
		/**
		 * Get a transaction.
		 * 
		 * @param int $id
		 * 
		 */
		public static function getTransaction($id)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('get-transaction', $params));
		}
		
		/**
		 * Register an email address for Email to SMS.
		 * 
		 * @param string $email
		 * @param int $max_sms
		 * @param string $number
		 * 
		 */
		public static function addEmail($email, $max_sms=1, $number='')
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('add-email', $params));			
		}
		
		/**
		 * Remove an email address from Email to SMS.
		 * 
		 * @param string $email
		 * 
		 */
		public static function deleteEmail($email)
		{
			$params = get_defined_vars();
			return self::handleResponse(self::request('delete-email', $params));			
		}
		
		/**
		 * Get active user's balance
		 * 
		 */
		public static function getBalance()
		{
			return self::handleResponse(self::request('get-balance'));
		}
	}
?>
