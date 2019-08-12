<?php

class Encryption
{

	private static $key;

	private static $cipher = MCRYPT_RIJNDAEL_128;

	private static $mode = MCRYPT_MODE_CBC;

	private static $block = 16;


	public static function encrypt($value)
	{
		$iv = self::getIv();

		$value = base64_encode(self::padAndMcrypt($value, $iv));

		$mac = self::hash($iv = base64_encode($iv), $value);

		return base64_encode(json_encode(compact('iv', 'value', 'mac')));
	}


	private static function padAndMcrypt($value, $iv)
	{
		$value = self::addPadding(serialize($value));

		return mcrypt_encrypt(self::$cipher, self::getKey(), $value, self::$mode, $iv);
	}


	private static function addPadding($value)
	{
		$pad = self::$block - (strlen($value) % self::$block);

		return $value.str_repeat(chr($pad), $pad);
	}


	private static function stripPadding($value)
	{
		$pad = ord($value[($len = strlen($value)) - 1]);

		return self::paddingIsValid($pad, $value) ? substr($value, 0, $len - $pad) : $value;
	}


	private static function paddingIsValid($pad, $value)
	{
		$beforePad = strlen($value) - $pad;

		return substr($value, $beforePad) == str_repeat(substr($value, -1), $pad);
	}


	private static function hash($iv, $value)
	{
		return hash_hmac('sha256', $iv.$value, self::getKey());
	}


	public static function decrypt($payload)
	{
		$payload = self::getJsonPayload($payload);

		$value = base64_decode($payload['value']);

		$iv = base64_decode($payload['iv']);

		return unserialize(self::stripPadding(self::mcryptDecrypt($value, $iv)));
	}


	private static function getJsonPayload($payload)
	{
		$payload = json_decode(base64_decode($payload), true);

		if ( ! $payload || self::invalidPayload($payload)) {
			throw new DecryptException('Invalid data.');
		}

		if ( ! self::validMac($payload)) {
			throw new DecryptException('MAC is invalid.');
		}

		return $payload;
	}


	private static function invalidPayload($data)
	{
		return ! is_array($data) || ! isset($data['iv']) || ! isset($data['value']) || ! isset($data['mac']);
	}


	protected static function validMac(array $payload)
	{
		return ($payload['mac'] === self::hash($payload['iv'], $payload['value']));
	}


	private static function mcryptDecrypt($value, $iv)
	{
		try {
			return mcrypt_decrypt(self::$cipher, self::getKey(), $value, self::$mode, $iv);
		}
		catch (Exception $e) {
			throw new DecryptException($e->getMessage());
		}
	}


	private static function getKey()
	{
		if (self::$key) {
			return self::$key;
		}

		return self::$key = substr(Cookie::getSecretKey(), 0, 32);
	}


	private static function getIv()
	{
		return mcrypt_create_iv(mcrypt_get_iv_size(self::$cipher, self::$mode), MCRYPT_RAND);
	}

}