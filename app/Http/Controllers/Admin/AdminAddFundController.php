<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DepositMail;
use App\Mail\FundDebit;
use App\Models\AddFund;
use App\Models\DebitFund;
use App\Models\Funding;
use App\Models\User;
use App\Notifications\DepositAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class AdminAddFundController extends Controller
{

    public function addFund()
    {
        $users = User::all();
        $deposits = Funding::where('type', 2)->latest()->get();
        $debit = Funding::where('type', 1)->latest()->get();
        return view('admin.funding.funding', compact('users', 'deposits', 'debit'));
    }

    public function storeDeposit(Request $request)
    {
        $request->validate([
            'from' => 'required',
            'amount' => 'required',
            'note' => 'nullable',
        ]);

        if ($request->type == 'debit') {
            $debit = new Funding();
            $debit->type = 1;
            $debit->from = $request->from;
            $debit->amount = $request->amount;
            $debit->note = $request->note;
            $debit->status = 1;
            $debit->user_id = $request->user_id;
            $debit->created_at = $request->created_at;
            $debit->save();
            $user = User::findOrFail($request->user_id);
            $user->account->balance -= $request->amount;
            $user->account->save();
            $data = ['user' => $user, 'debit' => $debit];
            Mail::to($user->email)->send(new FundDebit($data));
            return redirect()->back()->with('success', "Money Debited");
        } else {
            $deposit = new Funding();
            $deposit->type =2;
            $deposit->from = $request->from;
            $deposit->amount = $request->amount;
            $deposit->note = $request->note;
            $deposit->status = 1;
            $deposit->user_id = $request->user_id;
            $deposit->created_at = $request->created_at;
            $deposit->save();
            $user = User::findOrFail($request->user_id);
            $user->account->balance += $request->amount;
            $user->account->save();
            Mail::to($user->email)->send(new DepositMail($deposit));
            return redirect()->back()->with('success', "Money Added");
        }


    }

    public function editInfo($id)
    {
        $user = User::findOrFail($id);
        return view('admin.edit-info', compact('user'));
    }

    public function deleteDeposit($id)
    {
        $deposit = AddFund::findOrFail($id);
        $deposit->delete();
        return redirect()->back();
    }
}
