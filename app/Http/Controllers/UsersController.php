<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use Auth;
use Symfony\Component\Debug\Tests\Fixtures2\RequiredTwice;
use Mail;


class UsersController extends Controller
{

    /**
     * Auth 中间件验证登录状态
     * 只让未登录用户访问注册页面
     */
    public function  __construct()
    {
        $this->middleware('auth', [
            'except' => ['show', 'create', 'store', 'index', 'confirmEmail']
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
        $statuses = $user->statuses()
                        ->orderBy('created_at', 'desc')
                        ->paginate(30);
        //$this->authorize('update', $user); // 防止进入其它用户的个人中心
        return view('users.show', compact('user', 'statuses'));
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

        $this->sendEmailConfirmationTo($user);
        session()->flash('success', '验证邮箱已发送到你的注册邮箱上,请注意查收.');
        return redirect('/');
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

    /*发送邮件*/
    protected function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm'; //包含邮件消息的视图名称
        $data = compact('user'); //要传递给该视图的数据数组
        $from = '654789795@qq.com';
        $name = 'huping';
        $to = $user->email;
        $subject = "感谢注册 Sample 应用!请确认你的邮箱.";

        Mail::send($view, $data, function ($message) use ($from, $name, $to, $subject) {
            $message->from($from, $name)->to($to)->subject($subject);
        });
    }

    public function confirmEmail($token)
    {
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success', '恭喜你，激活成功！');
        return redirect()->route('users.show', [$user]);
    }

    public function followings(User $user)
    {
        $users = $user->followings()->paginate(30);
        $title = '关注的人';
        return view('users.show_follow', compact('users', 'title'));
    }

    public function followers(User $user)
    {
        $users = $user->followers()->paginate(30);
        $title = '粉丝';
        return view('users.show_follow', compact('users', 'title'));
    }
}
