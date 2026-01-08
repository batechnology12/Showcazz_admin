<?php



namespace App\Http\Controllers\Company\Auth;



use App\Company;

use Auth;

use Socialite;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use Illuminate\Support\Str;

use Illuminate\Foundation\Auth\AuthenticatesUsers;



class LoginController extends Controller

{

    /*

      |--------------------------------------------------------------------------

      | Login Controller

      |--------------------------------------------------------------------------

      |

      | This controller handles authenticating users for the application and

      | redirecting them to your home screen. The controller uses a trait

      | to conveniently provide its functionality to your applications.

      |

     */



use AuthenticatesUsers;



    /**

     * Where to redirect users after login.

     *

     * @var string

     */

    protected $redirectTo = '/company-home';



    /**

     * Create a new controller instance.

     *

     * @return void

     */

    public function __construct()

    {

        $this->middleware('company.guest')->except('logout');

    }



    /**

     * Show the application's login form.

     *

     * @return \Illuminate\Http\Response

     */

    public function showLoginForm()

    {

        return view('company_auth.login');

    }



    /**

     * Log the user out of the application.

     *

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */
/*
    public function logout(Request $request)

    {

        $this->guard('company')->logout();

        $request->session()->invalidate();

        return redirect('/login');

    }*/



    /**

     * Get the guard to be used during authentication.

     *

     * @return \Illuminate\Contracts\Auth\StatefulGuard

     */

    protected function guard()

    {

        return Auth::guard('company');

    }



    /**

     * Redirect the user to the OAuth Provider.

     *

     * @return Response

     */

    public function redirectToProvider($provider)

    {

        //echo config("services.{$provider}.redirect").'<br>';



        config(["services.{$provider}.redirect" => url("login/employer/{$provider}/callback")]);


        //echo config("services.{$provider}.redirect");exit;

        return Socialite::driver($provider)->redirect();

    }



    /**

     * Obtain the user information from provider.  Check if the user already exists in our

     * database by looking up their provider_id in the database.

     * If the user exists, log them in. Otherwise, create a new user then log them in. After that 

     * redirect them to the authenticated users homepage.

     *

     * @return Response

     */

    public function handleProviderCallback($provider)

    {
        config(["services.{$provider}.redirect" => url("login/employer/{$provider}/callback")]);
        
        $user = Socialite::driver($provider)->user();

        $authUser = $this->findOrCreateUser($user, $provider);

        Auth::guard('company')->login($authUser, true);

        return redirect($this->redirectTo);

    }



    /**

     * If a user has registered before using social auth, return the user

     * else, create a new user object.

     * @param  $user Socialite user object

     * @param $provider Social auth provider

     * @return  User

     */

    public function findOrCreateUser($user, $provider)

    {

        if ($user->getEmail() != '') {

            $authUser = Company::where('email', 'like', $user->getEmail())->first();

            if ($authUser) {

                /* $authUser->provider = $provider;

                  $authUser->provider_id = $user->getId();

                  $authUser->update(); */

                return $authUser;

            }

        }

        $str = $user->getName() . $user->getId() . $user->getEmail();

        $slug = Str::slug($user->getName(), '-');

        $slugs = unique_slug($slug, 'companies', $field = 'slug', $key = NULL, $value = NULL);

        return Company::create([

                    'name' => $user->getName(),

                    'email' => $user->getEmail(),

                    'slug' => $slugs,

                    //'provider' => $provider,

                    //'provider_id' => $user->getId(),

                    'password' => bcrypt($str),

                    'is_active' => 1,

                    'verified' => 1,

        ]);

    }


        public function logout(Request $request)
    {
        $userEmail = Auth::guard('company')->user()->email;
        Auth::guard('company')->logout();

        $filePath = base_path('../shared/shared_session.txt');
            $secretKey = '262646-mycode-4684927';
            if(file_exists($filePath)){
                $sessionData = json_decode(file_get_contents($filePath), true) ?? [];



                // Generate a token by encoding the email with HMAC
                $token = hash_hmac('sha256', $userEmail, $secretKey);
                unset($sessionData[$token]);
                
                // Save the updated session data back to the file
                file_put_contents($filePath, json_encode($sessionData));


                $cookieName = 'user_token';
                $cookieValue = '';
                $expiration = -3600; // Cookie expires in 30 days
                $path = ''; // Path on the server where the cookie is available

                // Set the cookie
                setcookie($cookieName, $cookieValue, $expiration, $path, '', false, true);   
            }

  

        // Optionally, you can invalidate the session and regenerate the CSRF token
       // $request->session()->invalidate();
       // $request->session()->regenerateToken();

        return redirect('/company-login');
    }



}

