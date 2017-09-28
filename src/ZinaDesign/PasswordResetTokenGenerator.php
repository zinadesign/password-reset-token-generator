<?php
namespace ZinaDesign;
class PasswordResetTokenGenerator
{

  const PASSWORD_RESET_TIMEOUT_DAYS = 1;

  const KEY_SALT = "django.contrib.auth.tokens.PasswordResetTokenGenerator";

  /**
   * Check that a password reset token is correct for a given user.
   *
   * @param $user
   * @param $token
   *
   * @return bool
   */
  public static function check_token($user, $token)
  {
    if (!($user && $token)) {
      return false;
    }
    # Parse the token
    $p = explode('-', $token);
    if (count($p) !== 2) {
      return false;
    }
    list($ts_b36, $hash) = $p;
    try {
      $ts = self::base36_to_int($ts_b36);
    } catch (Kohana_Exception $e) {
      return false;

    }
    # Check that the timestamp/uid has not been tampered with
    if (self::_make_token_with_timestamp($user, $ts) !== $token) {
      return false;
    }
    # Check the timestamp is within limit
    if ((self::_num_days(new DateTime('now')) - $ts) > self::PASSWORD_RESET_TIMEOUT_DAYS) {
      return false;
    }
    return true;
  }

  /**
   * Converts an integer to a base36 string
   *
   * @param int $i
   *
   * @return mixed
   */
  public static function int_to_base36($i)
  {
    return base_convert($i, 10, 36);
  }

  public static function base36_to_int($s)
  {
    return base_convert($s, 36, 10);
  }

  public static function salted_hmac($key_salt, $value, $secret = null)
  {
    if (is_null($secret)) {
      $secret = '2!*^*2%#i#9cd_(m12qs6gn0y@k((gxzr5%2r=d1exk^5=8bju';
    }
    $key = sha1($key_salt . $secret);
    return hash_hmac("sha1", $value, $key);
  }

  private static function _make_hash_value($user, $timestamp)
  {

    if (!$user->last_login) {
      $login_timestamp = '';
    } else {
      $login_timestamp = $user->last_login;
    }
    return ((string)$user->id . (string)$user->password . $login_timestamp . $timestamp);
  }

  /**
   * @param \DateTime $dt
   *
   * @return mixed
   */
  private static function _num_days($dt)
  {
    $from = new DateTime('2001-01-01');
    return $dt->diff($from)->days;
  }

  private static function _make_token_with_timestamp($user, $timestamp)
  {
    $ts_b36 = self::int_to_base36($timestamp);
    $key_salt = "django.contrib.auth.tokens.PasswordResetTokenGenerator";
    $hash = self::salted_hmac(
      $key_salt,
      self::_make_hash_value($user, $timestamp)
    );
    $token = '';
    for ($i = 0; $i < strlen($hash); $i++) {
      $ch = $hash[$i];
      if ($i % 2 === 0) {
        continue;
      }
      $token .= $ch;
    }
    return "{$ts_b36}-{$token}";
  }

  public static function make_token($user)
  {
    return self::_make_token_with_timestamp($user,
      self::_num_days(new DateTime('now')));
  }
}