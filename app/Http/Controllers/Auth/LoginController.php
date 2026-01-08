<?php



namespace App\Http\Controllers\Auth;



use App\User;

use Auth;
use Hash;
use Socialite;

use App\Http\Controllers\Controller;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;


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

    protected $redirectTo = '/home';



    /**

     * Create a new controller instance.

     *

     * @return void

     */

    public function __construct()

    {

        $this->middleware('guest')->except('logout');

    }



    /**

     * Redirect the user to the OAuth Provider.

     *

     * @return Response

     */

    public function redirectToProvider($provider)

    {

        return Socialite::driver($provider)->redirect();

    }

    public function companyLogin(){
        return view('auth.company-login');
    }
    
    public function companyRegister(){
        return view('auth.company-register');
    }

    public function login(Request $request)
{
    // Validate the incoming request
    $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    // Retrieve the user by email
    $user = User::where('email', $request->email)->first();

    if ($user) {
        // Check if the password matches either bcrypt or MD5 hash
        if (Hash::check($request->password, $user->password) || md5($request->password) === $user->password) {
            // Log the user in
            Auth::login($user, $request->filled('remember'));

            // Regenerate the session to prevent fixation attacks
            $request->session()->regenerate();

            // Redirect to intended page or home
            return redirect()->intended('/home');
        }
    }

    // If login attempt failed, redirect back with an error message
    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
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

        $user = Socialite::driver($provider)->user();

        $authUser = $this->findOrCreateUser($user, $provider);

        Auth::login($authUser, true);

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

            $authUser = User::where('email', 'like', $user->getEmail())->first();

            if ($authUser) {

                /* $authUser->provider = $provider;

                  $authUser->provider_id = $user->getId();

                  $authUser->update(); */

                return $authUser;

            }

        }

        $str = $user->getName() . $user->getId() . $user->getEmail();

        return User::create([

                    'first_name' => $user->getName(),

                    'middle_name' => $user->getName(),

                    'last_name' => $user->getName(),

                    'name' => $user->getName(),

                    'email' => $user->getEmail(),

                    //'provider' => $provider,

                    //'provider_id' => $user->getId(),

                    'password' => bcrypt($str),

                    'is_active' => 1,

                    'verified' => 1,

        ]);

    }



}

