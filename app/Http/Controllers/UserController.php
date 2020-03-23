<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestUser;
use App\Http\Requests\UpdatePassword;
use App\Http\Requests\UpdatePasswordUser;
use App\Person;
use App\Repositories\User\UserRepositoryInterface;
use \App\Repositories\Person\PersonRepositoryInterface;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected $userRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        PersonRepositoryInterface $personRepository)
    {
        $this->userRepository = $userRepository;
        $this->personRepository = $personRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::orderBy('updated_at', 'desc')->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::pluck('name', 'name')->all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(RequestUser $request)
    {
        $data = $request->all();
        $data['roles'] == ['Admin'] ? $data['admin'] = 1 : $data['admin'] = 0;
        if ($data['password'] == $data['re-password']) {
            $user = $this->userRepository->create($data);
            $user->assignRole($request['roles']);
        } else {
            return redirect()->back()->with('flash_message_error', 'Password not a coincidence re-password');
        }
        $this->personRepository->create([
            'email' => $user->email,
            'image' => '',
            'phone' => '',
            'name' => '',
            'faculty_id' => 1,
            'gender' => 1,
            'slug' => '',
            'date' => Carbon::now(),
            'address' => '',
            'user_id' => $user->id,
        ]);

        Auth::login($user);
        return redirect('/login/user')->with('flash_message_success', 'Register success!');

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = $this->userRepository->getListById($id);
        $roles = Role::pluck('name', 'name')->all();
        $userRole = $user->roles->pluck('name', 'name')->all();
        return view('admin.users.update', compact('user', 'roles', 'userRole'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(RequestUser $request, $id)
    {
        $data = $request->all();
        $this->userRepository->update($id, $data);
        $user = $this->userRepository->getListById($id);
        DB::table('model_has_roles')->where('model_id', $id)->delete();
        $user->assignRole($request['roles']);

        return redirect()->route('user.index')->with('success', 'Update success');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $persons = User::where('id',$id)->get();
        foreach ($persons as $person){
            DB::table('persons')->where('user_id',$person->id)->delete();
        }
        User::where('id',$user->id)->delete();
        DB::table('model_has_roles')->where('model_id', $id)->delete();
        return redirect()->back()->with('success', 'Delete user success');
    }

    public function createLogin()
    {
        return view('admin.login');
    }

    public function storeLogin(Request $request)
    {
        $data = $request->only('email', 'password');
        if (Auth::attempt($data)) {
            if (auth()->user()->admin == 1) {
                return redirect()->to('en/person')->with('success', 'Login success');
            } else {
                $persons = DB::table('persons')->select(['slug', 'email', 'id'])->get();
                $user = $persons->whereIn('email', $data['email']);
                if ($user) {
                    foreach ($user as $value) {
                        $id = $value->id;
                        $slug = $value->slug;
                    }
                    if ($slug == '') {
                        return redirect('/person-update/' . $id)->with('info', 'Login success');
                    } else {
                        return redirect($slug . '/person/en')->with('info', 'Login success');
                    }
                    return redirect('/person-update/' . $id)->with('info', 'Login success');
                } else {
                    return redirect()->back()->with('error', 'Login error');
                }
            }
        } else {
            return redirect()->back()->with('flash_message_error', 'Login error');
        }

    }

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $user = Socialite::driver('google')->user();
            $getInfo = User::where('social_id', $user->id)->first();
            if ($getInfo) {
                Auth::login($getInfo);
                $persons = DB::table('persons')->select(['slug', 'user_id'])->get();
                $user = $persons->whereIn('user_id', $getInfo['id']);
                if ($user) {
                    foreach ($user as $value) {
                        $slug = $value->slug;
                    }
                }
                return redirect($slug . '/person/en')->with('info', 'Login success');
            } else {
                $newUser = User::create([
                    'email' => $user->email,
                    'social_id' => $user->id,
                    'password' => '',
                    'admin' => 0,
                ]);
                $newUser->assignRole(\request('roles'));
                $person = Person::create([
                    'name' => '',
                    'phone' => 1,
                    'email' => $user->email,
                    'user_id' => $newUser->id,
                    'date' => Carbon::now(),
                    'image' => '',
                    'address' => '',
                    'gender' => 1,
                    'slug' => '',
                    'faculty_id' => 1
                ]);
                Auth::login($newUser);
                $persons = DB::table('persons')->select(['slug', 'user_id'])->get();
                $user = $persons->whereIn('user_id', $newUser['id']);
                if ($user) {
                    foreach ($user as $value) {
                        $slug = $value->slug;
                    }
                }
                return redirect('en/person/' . $slug)->with('info', 'Login success');
            }
        } catch (Exception $e) {
            return redirect('auth/google');
        }
    }

    public function changePassword()
    {
        return view('admin.admin_settings');
    }

    public function changePasswordStore(UpdatePassword $request)
    {
        $data = $request->all();
        User::find(auth()->user()->id)->update(['password' => $data['password']]);
        return redirect()->route('person.index')->with('success', 'Update password success!');
    }

    public function changeUpdatePassword($id)
    {
        $user = $this->userRepository->getListById($id);
        return view('admin.update_password', compact('user'));
    }

    public function changeUpdatePasswordStore(UpdatePasswordUser $request, $id)
    {
        $user = $this->userRepository->getListById($id);
        $data = $request->all();
        User::find($user->id)->update(['password' => $data['password']]);
        return redirect()->route('user.index')->with('success', 'Update password success!');
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login/user')->with('success', 'Logout success!');
    }
}
