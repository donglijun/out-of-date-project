<?php

/**
 *
 * @version Version 0.1 / slightly modified for phpBB 3.0.x (using $H$ as hash type identifier)
 *
 * Portable PHP password hashing framework.
 *
 * Written by Solar Designer <solar at openwall.com> in 2004-2006 and placed in
 * the public domain.
 *
 * There's absolutely no warranty.
 *
 * The homepage URL for this framework is:
 *
 *	http://www.openwall.com/phpass/
 *
 * Please be sure to update the Version line if you edit this file in any way.
 * It is suggested that you leave the main version number intact, but indicate
 * your project name (after the slash) and add your own revision information.
 *
 * Please do not change the "private" password hashing method implemented in
 * here, thereby making your hashes incompatible.  However, if you must, please
 * change the hash type identifier (the "$P$") to something different.
 *
 * Obviously, since this code is in the public domain, the above are not
 * requirements (there can be none), but merely suggestions.
 *
 *
 * Hash the password
 */
function phpbb_hash($password)
{
    $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    $random_state = unique_id();
    $random = '';
    $count = 6;

    if (($fh = @fopen('/dev/urandom', 'rb')))
    {
        $random = fread($fh, $count);
        fclose($fh);
    }

    if (strlen($random) < $count)
    {
        $random = '';

        for ($i = 0; $i < $count; $i += 16)
        {
            $random_state = md5(unique_id() . $random_state);
            $random .= pack('H*', md5($random_state));
        }
        $random = substr($random, 0, $count);
    }

    $hash = _hash_crypt_private($password, _hash_gensalt_private($random, $itoa64), $itoa64);

    if (strlen($hash) == 34)
    {
        return $hash;
    }

    return md5($password);
}

/**
 * Return unique id
 * @param string $extra additional entropy
 */
function unique_id($extra = 'c')
{
    //static $dss_seeded = false;
    //global $config;

//	$val = $config['rand_seed'] . microtime();
// 	$val = md5($val);
// 	$config['rand_seed'] = md5($config['rand_seed'] . $val . $extra);

// 	if ($dss_seeded !== true && ($config['rand_seed_last_update'] < time() - rand(1,10)))
// 	{
// 		set_config('rand_seed_last_update', time(), true);
// 		set_config('rand_seed', $config['rand_seed'], true);
// 		$dss_seeded = true;
// 	}

    $val = md5(microtime().$extra);
    return substr($val, 4, 16);
}

/**
 * Check for correct password
 *
 * @param string $password The password in plain text
 * @param string $hash The stored password hash
 *
 * @return bool Returns true if the password is correct, false if not.
 */
function phpbb_check_hash($password, $hash)
{
    $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    if (strlen($hash) == 34)
    {
        return (_hash_crypt_private($password, $hash, $itoa64) === $hash) ? true : false;
    }

    return (md5($password) === $hash) ? true : false;
}

/**
 * Generate salt for hash generation
 */
function _hash_gensalt_private($input, &$itoa64, $iteration_count_log2 = 6)
{
    if ($iteration_count_log2 < 4 || $iteration_count_log2 > 31)
    {
        $iteration_count_log2 = 8;
    }

    $output = '$H$';
    $output .= $itoa64[min($iteration_count_log2 + ((PHP_VERSION >= 5) ? 5 : 3), 30)];
    $output .= _hash_encode64($input, 6, $itoa64);

    return $output;
}

/**
 * Encode hash
 */
function _hash_encode64($input, $count, &$itoa64)
{
    $output = '';
    $i = 0;

    do
    {
        $value = ord($input[$i++]);
        $output .= $itoa64[$value & 0x3f];

        if ($i < $count)
        {
            $value |= ord($input[$i]) << 8;
        }

        $output .= $itoa64[($value >> 6) & 0x3f];

        if ($i++ >= $count)
        {
            break;
        }

        if ($i < $count)
        {
            $value |= ord($input[$i]) << 16;
        }

        $output .= $itoa64[($value >> 12) & 0x3f];

        if ($i++ >= $count)
        {
            break;
        }

        $output .= $itoa64[($value >> 18) & 0x3f];
    }
    while ($i < $count);

    return $output;
}

/**
 * The crypt function/replacement
 */
function _hash_crypt_private($password, $setting, &$itoa64)
{
    $output = '*';

    // Check for correct hash
    if (substr($setting, 0, 3) != '$H$' && substr($setting, 0, 3) != '$P$')
    {
        return $output;
    }

    $count_log2 = strpos($itoa64, $setting[3]);

    if ($count_log2 < 7 || $count_log2 > 30)
    {
        return $output;
    }

    $count = 1 << $count_log2;
    $salt = substr($setting, 4, 8);

    if (strlen($salt) != 8)
    {
        return $output;
    }

    /**
     * We're kind of forced to use MD5 here since it's the only
     * cryptographic primitive available in all versions of PHP
     * currently in use.  To implement our own low-level crypto
     * in PHP would result in much worse performance and
     * consequently in lower iteration counts and hashes that are
     * quicker to crack (by non-PHP code).
     */
    if (PHP_VERSION >= 5)
    {
        $hash = md5($salt . $password, true);
        do
        {
            $hash = md5($hash . $password, true);
        }
        while (--$count);
    }
    else
    {
        $hash = pack('H*', md5($salt . $password));
        do
        {
            $hash = pack('H*', md5($hash . $password));
        }
        while (--$count);
    }

    $output = substr($setting, 0, 12);
    $output .= _hash_encode64($hash, 16, $itoa64);

    return $output;
}

function phpbb_email_hash($email)
{
    return sprintf('%u', crc32(strtolower($email))) . strlen($email);
}



// function request_var($var_name, $default, $multibyte = false, $cookie = false)
// {
// 	if (!$cookie && isset($_COOKIE[$var_name]))
// 	{
// 		if (!isset($_GET[$var_name]) && !isset($_POST[$var_name]))
// 		{
// 			return (is_array($default)) ? array() : $default;
// 		}
// 		$_REQUEST[$var_name] = isset($_POST[$var_name]) ? $_POST[$var_name] : $_GET[$var_name];
// 	}

// 	$super_global = ($cookie) ? '_COOKIE' : '_REQUEST';
// 	if (!isset($GLOBALS[$super_global][$var_name]) || is_array($GLOBALS[$super_global][$var_name]) != is_array($default))
// 	{
// 		return (is_array($default)) ? array() : $default;
// 	}

// 	$var = $GLOBALS[$super_global][$var_name];
// 	if (!is_array($default))
// 	{
// 		$type = gettype($default);
// 	}
// 	else
// 	{
// 		list($key_type, $type) = each($default);
// 		$type = gettype($type);
// 		$key_type = gettype($key_type);
// 		if ($type == 'array')
// 		{
// 			reset($default);
// 			$default = current($default);
// 			list($sub_key_type, $sub_type) = each($default);
// 			$sub_type = gettype($sub_type);
// 			$sub_type = ($sub_type == 'array') ? 'NULL' : $sub_type;
// 			$sub_key_type = gettype($sub_key_type);
// 		}
// 	}

// 	if (is_array($var))
// 	{
// 		$_var = $var;
// 		$var = array();

// 		foreach ($_var as $k => $v)
// 		{
// 			set_var($k, $k, $key_type);
// 			if ($type == 'array' && is_array($v))
// 			{
// 				foreach ($v as $_k => $_v)
// 				{
// 					if (is_array($_v))
// 					{
// 						$_v = null;
// 					}
// 					set_var($_k, $_k, $sub_key_type, $multibyte);
// 					set_var($var[$k][$_k], $_v, $sub_type, $multibyte);
// 				}
// 			}
// 			else
// 			{
// 				if ($type == 'array' || is_array($v))
// 				{
// 					$v = null;
// 				}
// 				set_var($var[$k], $v, $type, $multibyte);
// 			}
// 		}
// 	}
// 	else
// 	{
// 		set_var($var, $var, $type, $multibyte);
// 	}

// 	return $var;
// }

// function set_var(&$result, $var, $type, $multibyte = false)
// {
// 	settype($var, $type);
// 	$result = $var;

// 	if ($type == 'string')
// 	{
// 		$result = trim(htmlspecialchars(str_replace(array("\r\n", "\r", "\0"), array("\n", "\n", ''), $result), ENT_COMPAT, 'UTF-8'));

// 		if (!empty($result))
// 		{
// 			// Make sure multibyte characters are wellformed
// 			if ($multibyte)
// 			{
// 				if (!preg_match('/^./u', $result))
// 				{
// 					$result = '';
// 				}
// 			}
// 			else
// 			{
// 				// no multibyte, allow only ASCII (0-127)
// 				$result = preg_replace('/[\x80-\xFF]/', '?', $result);
// 			}
// 		}

// 		//$result = (STRIP) ? stripslashes($result) : $result;
// 	}
// }

function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
        foreach ($matches[0] AS $xip) {
            if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {

                $ip = $xip;
                break;
            }
        }
    }
    return $ip;
}


function remote_json_response($code,$message,$result = ''){
    $ret = array('code'=>$code,'msg'=>$message,'result'=>$result);
    echo json_encode($ret);
    exit;
}

/**
 * Generates a user-friendly alphanumeric random string of given length
 * We remove 0 and O so users cannot confuse those in passwords etc.
 *
 * @return string
 */
function gen_rand_string_friendly($num_chars = 8){
    $rand_str = unique_id();

    // Remove Z and Y from the base_convert(), replace 0 with Z and O with Y
    // [a, z] + [0, 9] - {z, y} = [a, z] + [0, 9] - {0, o} = 34
    $rand_str = str_replace(array('0', 'O'), array('Z', 'Y'), strtoupper(base_convert($rand_str, 16, 34)));

    return substr($rand_str, 0, $num_chars);
}

/**
 * Generates an alphanumeric random string of given length
 *
 * @return string
 */
function gen_rand_string($num_chars = 8)
{
    // [a, z] + [0, 9] = 36
    return substr(strtoupper(base_convert(unique_id(), 16, 36)), 0, $num_chars);
}
