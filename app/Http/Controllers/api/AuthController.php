<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Matkul;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public $successStatus = 200;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 403);
        }

        $user = User::where('username', $request->username)->first();

        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('nApp')->accessToken;
                $response = ['token' => $token];
                return response($response, 200);
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 403);
            }
        } else {
            $error = ["message" => 'User does not exist'];
            return response()->json(['error' => $error], 403);
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nip' => 'required',
            'name' => 'required',
            'username' => 'required|unique:users,username|alpha_dash',
            'password' => [
                'required',
                'min:8', // must be at least 8 characters in length
                'regex:/[a-z]/', // must contain at least one lowercase letter
                'regex:/[A-Z]/', // must contain at least one uppercase letter
                'regex:/[0-9]/', // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
            ],
            'kode_matkul' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 403);
        }

        $input = $request->all();

        $matkul = Matkul::where('code', $input['kode_matkul'])->get()->first();

        if ($matkul == null) {
            $response = ["message" => "Matakuliah Tidak Terdaftar"];
            return response($response, 403);
        }

        $input['password'] = bcrypt($input['password']);
        
        $user = User::create([
            'nip' => $input['nip'],
            'name' => $input['name'],
            'username' => $input['username'],
            'password' => $input['password'],
        ]);

        DB::table('dosens')->insert([
            'id_matkul' => $matkul->id,
            'id_dosen' => $user->id,
        ]);

        $user->assignRole('dosen');
        $success['token'] =  $user->createToken('nApp')->accessToken;
        $success['username'] =  $user->username;
        $success['mata_kuliah'] =  $matkul->name;

        return response($success, 200);
    }
}
