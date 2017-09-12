<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;

class SessionsController extends Controller
{
    public function create()
    {
        return view('sessions.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|max:255',
            'password' => 'required'
        ]);

        /* 登录操作 */
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $request->has('remember'))) {
            // 登录成功后的相关操作
            session()->flash('success', '欢迎回来!');
            return redirect()->route('users.show', [Auth::user()]);
        } else {
            // 登录失败后的相关操作
            session()->flash('danger', '很抱歉,您的邮箱和密码不匹配');
            return redirect()->back();
        }
    }

    /* 退出操作 */
    public function destroy()
    {
        Auth::logout();
        session()->flash('success', '您已成功退出!');
        return redirect('login');
    }
}
