<?php
class DerpAuthController extends BaseController {

    /*
     * Handle user authentication
     */

    public static function SecureThis($pin)
    {
        $salt = '$1$' . substr(microtime(),0,8);
        return crypt($pin, $salt);
    }

    public static function CheckThis($pin,$salt)
    {
        $this_salt = '$1$' . $salt;
        return crypt($pin,$this_salt);
    }

    public static function ParseHash($hash)
    {
        $hash_parts = explode('$',$hash);
        return $hash_parts;
    }

    public static function CleanKey($key){
        $int_key = intval($key);
        $clean_key = $int_key & 0x00FFFFFF;
        return $clean_key;
    }

    public static function login()
    {

        // Get and validate input
        $data = Input::all();
        $rules = array(
            'key' => 'required|integer',
            'pin' => 'required|integer'
        );
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return Redirect::to('login')->withInput()->withErrors($validator);
        }

        //Get user by specified key
        $user = User::getUserByKey($data['key']);

        if ($user) {
            //Find out whether hashes match
            $hashes_match = DerpAuthController::hashesMatch($user->hash, $data['pin']);

            if($hashes_match){

                Session::put('logged_in', 1);
                Session::put('ircName', $user->ircName);
                Session::put('key', $user->key);

                if($user->isAdmin){

                    return Redirect::to('admin');

                } elseif(!$user->isAdmin){

                    return Redirect::to('user');

                }
            } else {

                return Redirect::to('login')->with('bad_pin', true)->withInput();
            }

            return Redirect::to('login')->withInput();
        } else {
            return Redirect::to('login')->with('bad_key', true)->withInput();
        }
    }

    public static function hashesMatch($user_hash, $supplied_pin)
    {
        $stored = explode('$',$user_hash);
        $salt = '$1$' . $stored['2'];
        $supplied_check = crypt($supplied_pin, $salt);
        $supplied = explode('$',$supplied_check);
        $user_hash = $stored['3'];
        $supplied_hash = $supplied['3'];

        if($supplied_hash == $user_hash) {
            return true;
        } else {
            return false;
        }
    }

    public static function isLoggedIn()
    {
        if (Session::get('logged_in') == 1) {
            return true;
        } else {
            return false;
        }
    }

    public static function isAdmin()
    {
        $key = Session::get('key');
        $user = User::getUserByKey($key);
        if ($user && $user->isAdmin) {
            return true;
        } else {
            return false;
        }
    }

    public static function logout()
    {
        Session::flush();

        return Redirect::to('login');
    }

}