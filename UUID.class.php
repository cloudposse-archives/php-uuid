<?
/* UUID.class.php - Class for Universally Unique IDs
 * Copyright (C) 2007 Erik Osterman <e@osterman.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/* File Authors:
 *   Erik Osterman <e@osterman.com>
 */


class UUID
{
  const RANDOM  = 0x00;  // /dev/random
  const URANDOM = 0x01;  // /dev/urandom
  const MD5     = 0x02;  // PHP's uniqid + random + md5 
  const TIME    = 0x04;  // RFC's original proposal
  const ANY     = 0x08;  // Any type of UUID
  const BINARY  = 0x10;  // Binary UUID
  const TEXT    = 0x20;  // Text formatted UUID

  const NULL    = '00000000-0000-0000-0000-000000000000';

  private $id;

  public function __construct( $id )
  {
    $len = strlen($id);
    if( $len == 16 )
      $this->setBinary($id);
    elseif($len == 36 )
      $this->setText($id);
    else
      throw new Exception( get_class($this) . "::__construct invalid id");
  }

  public function __destruct()
  {
    unset($this->id);
  }

  public function __get( $property )
  {
    switch( $property )
    {
      case 'generate':
        return UUID::generate();
      case 'binary':
        return $this->id;
      case 'text':
        return UUID::text($this->id);
      case 'str':
        return $this->text;
      case 'is_null':
        return $this->text == UUID::NULL;
      default:
        throw new Exception( get_class($this) . "::$property not defined");
    }
  }

  public function __set( $property, $value )
  {
    switch( $property )
    {
      case 'id':
        switch( strlen($value) )
        {
          case 16:
            $this->setBinary($value);
            break;
          case 36:
            $this->setText($value);
            break;
          default:
            throw new Exception( get_class($this) . "::__construct unrecognized UUID format " . Debug::describe($value) );
          
        }
        break;
      default:
        throw new Exception( get_class($this) . "::$property cannot be set");
    }
  }

  public function __toString()
  {
    return $this->text;
  }

  public function __key()
  {
    return $this->id;
  }
  
  public function __hash()
  {
    return $this->id;
  }

  public function __unset($property)
  {
    throw new Exception( get_class($this) . "::$property cannot be unset");
  }

  public function setText($string)
  {
    $this->id = UUID::binary($string);
  }

  public function setBinary($blob)
  {
    $this->id = $blob;
  }
  
  public function str()
  {
    return $this->str;
  }

  public static function validate( $uuid, $type = UUID::ANY )
  {
    if( !is_scalar($uuid) )
      return false;
    
    if( $type == UUID::ANY || $type == UUID::TEXT )
    {
      // Example: 31c5e283-29d9-47df-8e86-965f31998e4b
      // 8-4-4-4-12
      $hex = '[a-f0-9]';
      $exp = "/^$hex{8,8}-$hex{4,4}-$hex{4,4}-$hex{4,4}-$hex{12,12}$/";
      if( strlen($uuid) == 36 && preg_match($exp, $uuid) )
        return true;
    }
    
    if( $type == UUID::ANY || $type == UUID::BINARY )
    {
      // byte format 4-2-2-2-6
      if( strlen($bin) == 16 )
        return true;
    }

    return false;
  }

  public function matches( $object )
  {
    if( $object instanceof UUID )
      return $this->id == $object->id;
    $text_uuid = PHP::toString($object);
    return $this->text == $text_uuid;
  }

  public static function generate( $method = UUID::URANDOM )
  {
    switch( true )
    {
      case $method & UUID::RANDOM:
        throw new Exception( __CLASS__ . "::generate(UUID::RANDOM) not implemented");
      case $method & UUID::MD5:
        // Generates a 16 byte raw UUID with high entropy
        $uuid = md5(uniqid(rand(), true), true);
        break;
      case $method & UUID::TIME:
        throw new Exception( __CLASS__ . "::generate(UUID::TIME) not implemented");
      default:
      case $method & UUID::URANDOM:
        // See MAN page for uuid_generate. This uses the same algo, reading 16 bytes of randomness using /dev/urandom 
        $uuid = Random::bytes(16);
        break;
    }

    if($method & UUID::TEXT )
      return UUID::text($uuid);
    else
      return $uuid;
  }
  
  public static function binary( $uuid )
  {
    if( !is_scalar($uuid) )
      throw new Exception( __CLASS__ . "::binary expected a scalar text string but got " . Debug::describe($uuid) );

    if( strlen($uuid) == 36 )
      return pack('H*', str_replace('-', '', $uuid));
    else 
      throw new Exception( __CLASS__ . "::binary invalid text ($uuid) UUID of length " . strlen($uuid) );
  }

  public static function text( $bin )
  {
    // byte format 4-2-2-2-6
    // Example: 31c5e283-29d9-47df-8e86-965f31998e4b
    if( !is_scalar($bin) )
      throw new Exception( __CLASS__ . "::text expected a scalar binary string but got " . Debug::describe($bin) );

    if( strlen($bin) == 16 )
      return preg_replace('/^(.{8})(.{4})(.{4})(.{4})(.{12})$/', '$1-$2-$3-$4-$5', bin2hex($bin));
    else 
      throw new Exception("Invalid binary UUID [$bin] of length " . strlen($bin) );
  }
}
/*
// Example Usage:
include 'Autoloader.class.php';
$t = new Timer(1);
$t->start;
print UUID::generate(UUID::TEXT) . "\n";
printf("elapsed: %.5f\n", $t->elapsed);
*/

?>
