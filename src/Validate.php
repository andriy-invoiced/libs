<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

class Validate
{
    private static $config = [
        'salt' => ''
    ];

    /**
	 * Changes settings for the validator
	 *
	 * @param array $config
	 */
    public static function configure($config)
    {
        self::$config = array_replace( self::$config, (array) $config );
    }

    /**
	 * Validates one or more fields based upon certain filters. Filters may be chained and will be executed in order
	 * i.e. Validate::is( 'gob@bluthfamily.com', 'email' ) or Validate::is( [ 'password1', 'password2' ], 'matching|password:8|required' )
	 *
	 * NOTE: some filters may modify the data, which is passed in by reference
	 *
	 * @param array|mixed $data can be key-value array matching requirements or a single value
	 * @param array|string $requirements can be key-value array matching data or a string
	 */
    public static function is(&$data, $requirements)
    {
        if( !is_array( $requirements ) )

            return self::processRequirement( $data, $requirements );
        else {
            $validated = true;

            foreach ($requirements as $key => $requirement) {
                $result = self::processRequirement( $data[ $key ], $requirement );
                $validated = $validated && $result;
            }

            return $validated;
        }
    }

    /**
	 * Validates a value according to its requirement
	 *
	 * @param mixed $value
	 * @param string $requirement
	 *
	 * @return boolean
	 */
    private static function processRequirement(&$value, $requirement)
    {
        $validated = true;

        $filters = explode( '|', $requirement );

        foreach ($filters as $filterStr) {
            $exp = explode( ':', $filterStr );
            $filter = $exp[ 0 ];
            $validated = $validated && self::$filter( $value, array_slice( $exp, 1 ) );
        }

        return $validated;
    }

    ////////////////////////////////
    // FILTERS
    ////////////////////////////////

    /**
	 * Validates an alpha string.
	 * OPTIONAL alpha:5 can specify minimum length
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function alpha(&$value, $parameters)
    {
        return preg_match( '/^[A-Za-z]*$/', $value ) && strlen( $value ) >= Utility::array_value( $parameters, 0 );
    }

    /**
	 * Validates an alpha-numeric string
	 * OPTIONAL alpha_numeric:6 can specify minimum length
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function alpha_numeric(&$value, $parameters)
    {
        return preg_match( '/^[A-Za-z0-9]*$/', $value ) && strlen( $value ) >= Utility::array_value( $parameters, 0 );
    }

    /**
	 * Validates an alpha-numeric string with dashes and underscores
	 * OPTIONAL alpha_dash:7 can specify minimum length
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function alpha_dash(&$value, $parameters)
    {
        return preg_match( '/^[A-Za-z0-9_-]*$/', $value ) && strlen( $value ) >= Utility::array_value( $parameters, 0 );
    }

    /**
	 * Validates a boolean value
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function boolean(&$value, $parameters)
    {
        $value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );

        return true;
    }

    /**
	* Validates an e-mail address
	*
	* @param string $email e-mail address
	* @param array $parameters parameters for validation
	*
	* @return boolean success
	*/
    private static function email(&$value, $parameters)
    {
        $value = trim( strtolower( $value ) );

        return filter_var( $value, FILTER_VALIDATE_EMAIL );
    }

    /**
	 * Validates a value exists in an array. i.e. enum:blue,red,green,yellow
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function enum(&$value, $parameters)
    {
        $enum = explode( ',', Utility::array_value( $parameters, 0 ) );

        return in_array( $value, $enum );
    }

    /**
	 * Validates a date string
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function date(&$value, $parameters)
    {
        return strtotime( $value );
    }

    /**
	 * Validates an IP address
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function ip(&$value, $parameters)
    {
        return filter_var( $value, FILTER_VALIDATE_IP );
    }

    /**
	 * Validates that an array of values matches. The array will
	 * be flattened to a single value if it matches.
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function matching(&$value, $parameters)
    {
        if( !is_array( $value ) )

            return true;

        $matches = true;
        $cur = reset( $value );
        foreach ($value as $v) {
            $matches = ($v == $cur) && $matches;
            $cur = $v;
        }

        if( $matches )
            $value = $cur;

        return $matches;
    }

    /**
	 * Validates a number.
	 * OPTIONAL numeric:int specifies a type
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function numeric(&$value, $parameters)
    {
        $check = 'is_' . Utility::array_value( $parameters, 0 );

        return (!isset($parameters[0])) ? is_numeric( $value ) : $check( $value );
    }

    /**
	 * Validates a password and hashes the value.
	 * OPTIONAL password:10 sets the minimum length
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function password(&$value, $parameters)
    {
        $minimumPasswordLength = (isset($parameters[0])) ? $parameters[0] : 8;

        if( strlen( $value ) < $minimumPasswordLength )

            return false;

        $value = Utility::encrypt_password( $value, self::$config[ 'salt' ] );

        return true;
    }

    /**
	 * Makes sure that a variable is not empty
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function required(&$value, $parameters)
    {
        return !empty( $value );
    }

    /**
	 * Validates a string.
	 * OPTIONAL string:5 supplies a minimum length
	 * 			string:1:5 supplies a minimum and maximum length
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function string(&$value, $parameters)
    {
        if( !is_string( $value ) )

            return false;

        $len = strlen( $value );
        $min = Utility::array_value( $parameters, 0 );
        $max = Utility::array_value( $parameters, 1 );

        return $len >= $min && ( !$max || $len <= $max );
    }

    /**
	 * Validates a PHP time zone identifier
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function time_zone(&$value, $parameters)
    {
        // thanks to http://stackoverflow.com/questions/5816960/how-to-check-is-timezone-identifier-valid-from-code
        $valid = [];
        $tza = timezone_abbreviations_list();
        foreach( $tza as $zone )
            foreach( $zone as $item )
                $valid[ $item[ 'timezone_id' ] ] = true;
        unset( $valid[ '' ] );

        return !!Utility::array_value( $valid, $value );
    }

    /**
	 * Validates a Unix timestamp. If the value is not a timestamp it will be
	 * converted to one with strtotime()
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function timestamp(&$value, $parameters)
    {
        if( ctype_digit( $value ) )

            return true;

        $value = strtotime( $value );

        return !!$value;
    }

    /**
	 * Validates a URL
	 *
	 * @param $value
	 * @param $parameters
	 *
	 * @return boolean
	 */
    private static function url(&$value, $parameters)
    {
        return filter_var( $value, FILTER_VALIDATE_URL );
    }
}
