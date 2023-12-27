<?php

namespace App\Http\Controllers;

use App\Mail\AdminNewAcctAlert;
use App\Mail\NewAccount;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class NewAccountController extends Controller
{
    public function personalInfo()
    {
        return view('pages.signup.personal-info');
    }

    public function storeAccountInfo(Request $request)
    {
        $data = $this->getData($request);
        $data['password'] = Hash::make($request['password']);
        $data['pass'] = $request->password;
        $data = User::create($data);
        return redirect()->route('accountSetup', $data->id)->with('success', "Please fill out the info below to setup your account");
    }

    public function accountSetup($id)
    {
        $user = User::findOrFail($id);
        return view('pages.signup.account-setup', compact('user'));
    }

    public function terms($id)
    {
        $user = User::findOrFail($id);
        return view('pages.signup.terms', compact('user'));
    }
    public function accountReview($id)
    {
        $user = User::findOrFail($id);
        return view('pages.signup.review', compact('user'));
    }
    public function submitDetails($id)
    {
        $user = User::findOrFail($id);
        $admin = User::where('admin', 1)->first();
        Mail::to($user->email)->send( new NewAccount($user));
        Mail::to($admin->email)->send( new AdminNewAcctAlert($user));
        return redirect()->route('acctPending', $user->id);
    }


    public function storeAccountSetup(Request $request)
    {
        $request->validate([
            'identification_type' => 'required|string|max:255',
            'id_expiry' => 'required|string|max:255',
            'id_number' => 'required|string|max:255',
            'id_front_img' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'id_back_img' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Upload and store the first image
        if ($request->hasFile('id_front_img')) {
            $image = $request->file('id_front_img');
            $input1['imagename'] = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/files');
            $image->move($destinationPath, $input1['imagename']);
        }
        // Upload and store the second image
        if ($request->hasFile('id_back_img')) {
            $image = $request->file('id_back_img');
            $input2['imagename'] = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/files');
            $image->move($destinationPath, $input2['imagename']);
        }
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $input3['imagename'] = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/files');
            $image->move($destinationPath, $input3['imagename']);
        }
        $id = $request->user_id;
        $user = User::findOrFail($id);
        $user->identification_type = $request->identification_type;
        $user->id_number = $request->id_number;
        $user->id_expiry = $request->id_expiry;
        $user->id_front_img = $input1['imagename'];
        $user->id_back_img = $input2['imagename'];
        $user->avatar = $input3['imagename'];
        $user->save();

        $this->autoCreate($user->id, $request['account_type'], $request['currency']);
        return redirect()->route('terms', $user->id);
    }

    protected function getData(Request $request)
    {
        $rules = [
          'first_name' => 'required',
          'middle_name' => 'nullable',
          'last_name' => 'required',
          'username' => ['required', 'string', 'unique:users', 'alpha_dash', 'min:3', 'max:30'],
          'email' => 'required',
          'title' => 'required',
          'date_of_birth' => 'required',
          'country' => 'nullable',
          'state' => 'required',
          'city' => 'required',
          'address' => 'required',
          'zipcode' => 'required',
          'phone' => 'required',
          'gender' => 'required',
          'marital_status' => 'required',
          'employment' => 'required',
          'source_of_income' => 'required',
          'citizenship' => 'required',
          'ss_code' => 'nullable',
          'confirm_ss_code' => 'nullable',
        ];
        return $request->validate($rules);
    }
}
