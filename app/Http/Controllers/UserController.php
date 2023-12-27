<?php

namespace App\Http\Controllers;

use App\Models\AddFund;
use App\Models\Transfer;
use App\Models\User;
use App\Rules\MatchOldPassword;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function dashboard()
    {
        $user = Auth::user();
        $transactions = Transfer::whereUserId(auth()->id())->latest()->paginate(4);
        $income = AddFund::whereUserId(\auth()->id())->whereDate('created_at', Carbon::today())->select('amount')->sum('amount');
        $expenses = Transfer::whereUserId(\auth()->id())->whereDate('created_at', Carbon::today())
            ->where('debit_inflow', true)->select('amount')->sum('amount');
        return view('dashboard.index', compact('user', 'transactions', 'income', 'expenses'));
    }
    public function acctPending($id)
    {
        $user = User::findOrFail($id);
        if (auth()->user()->status > 0)
        {
            return view('dashboard.index', compact('user'));
        }else
        return view('dashboard.pending', compact('user'));
    }

    public function testing($id)
    {
        $user = User::findOrFail($id);
        return view('emails.debit-alert', compact('user'));
    }

    public function profile()
    {
        $user = Auth::user();
        return view('dashboard.user.profile', compact('user'));
    }

    public function password()
    {
        return view('dashboard.user.password');
    }

    public function storePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', new MatchOldPassword],
            'new_password' => ['required'],
            'new_confirm_password' => ['same:new_password'],
        ]);
        User::find(auth()->user()->id)->update(['password'=> Hash::make($request->new_password)]);
        return redirect()->back()->with('success', "Password Updated Successfully");
    }

    public function support()
    {
        return view('dashboard.support');
    }

}
