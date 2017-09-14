<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use Auth;
use Symfony\Component\Debug\Tests\Fixtures2\RequiredTwice;


class UsersController extends Controller
{

    /**
     * Auth 中间件验证登录状态
     * 只让未登录用户访问注册页面
     */
    public function  __construct()
    {
        $this->middleware('auth', [
            'except' => ['show', 'create', 'store', 'index']
        ]);

        $this->middleware('guest', [
            'only' => ['create']
        ]);
    }

    public function index()
    {
        $users = User::paginate(10);//分页,每页显示10条数据
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function show(User $user)
    {
        //$this->authorize('update', $user); // 防止进入其它用户的个人中心
        return view('users.show', compact('user'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email'=> $request->email,
            'password' => bcrypt($request->password),
        ]);

        Auth::login($user);
        session()->flash('success', '欢迎,您将在这里开启一段新的旅程~');
        return redirect()->route('users.show', [$user]);
    }

    /*编辑个人信息页面展示*/
    public function edit(User $user)
    {
        $this->authorize('update', $user); //防止其它用户跨权更新当前用户的资料
        return view('users.edit', compact('user'));
    }

    /*处理用户提交的个人信息*/
    public function update(User $user, Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50',
            'password' => 'nullable|min:6',
        ]);

        $this->authorize('update', $user); //防止其它用户跨权更新当前用户的资料

        $data = [];
        $data['name'] = $request->name;

        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        session()->flash('success', '个人资料更新成功!');

        return redirect()->route('users.show', $user->id);
    }

    /*删除用户*/
    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success', '成功删除用户!');
        return back();
    }
}
