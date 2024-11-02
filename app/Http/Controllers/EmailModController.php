<?php

namespace App\Http\Controllers;
use App\Models\EmailMod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class EmailModController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sql = EmailMod::first();
        return response()->json($sql);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $sql = EmailMod::first();
        if($sql){
            EmailMod::where('id', $sql->id)->update([
                'email' => $request->email,
                'SMTPAuth' => $request->SMTPAuth === 'true' ? 1 : 0,
                'SMTPSecure' => $request->SMTPSecure,
                'host' => $request->host,
                'port' => $request->port,
                'password' => $request->password,
                'from' => $request->fromE,
                'api' => $request->api,
                'token' => $request->token,
                'view' => $request->view,
                'user_id' => Auth::user()->id
            ]);
        } else {
            EmailMod::Create([
                'email' => $request->email,
                'SMTPAuth' => $request->SMTPAuth === 'true' ? 1 : 0,
                'SMTPSecure' => $request->SMTPSecure,
                'host' => $request->host,
                'port' => $request->port,
                'password' => $request->password,
                'from' => $request->fromE,
                'api' => $request->api,
                'token' => $request->token,
                'view' => $request->view,
                'user_id' => Auth::user()->id
            ]);
        }
        return response()->json(['message' => 'Email guardado exitosamente.'], 200);
    }
}
