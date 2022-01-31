<?php

namespace App\Http\Controllers;

use App\Mail\ChangePassword;
use App\Mail\OTPCodeValidator;
use App\Mail\RegisterCompany;
use App\Models\Company;
use App\Models\CompanyInscripcion;
use App\Models\CompanyUser;
use App\Models\CompanyUserStore;
use App\Models\Customer;
use App\Models\GeoCity;
use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\Parameters;
use App\Models\Store;
use App\Models\Tag;
use App\Models\User;
use App\Object\Result;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use PHPMailer\PHPMailer\PHPMailer;
use JWTAuth;
use Nette\Utils\Strings;
use Ramsey\Uuid\Uuid;
use Tymon\JWTAuth\Facades\JWTAuth as FacadesJWTAuth;
use Tymon\JWTAuth\JWT;

class ApiController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Info(
     *   title="LucaPOS API",
     *   version="1.0.0",
     *   contact={
     *     "email": "developers@tulivery.com"
     *   }
     * )
     */

    /**
     * @OA\SecurityScheme(
     *     type="http",
     *     description="Inicie sesión con correo electrónico y contraseña para obtener el token de autenticación",
     *     name="Basado en token: bearer",
     *     in="header",
     *     scheme="bearer",
     *     bearerFormat="JWT",
     *     securityScheme="apiAuth",
     * )
     */

    /**
     * @OA\Post(
     *   tags={"Seguridad"},
     *   path="/api/login",
     *   summary="Inciar sesión",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"email","password"},
     *       @OA\Property(property="email", type="string"),
     *       @OA\Property(property="password", type="string")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Login exitoso",
     *     @OA\JsonContent(
     *       type="object",
     *       required={"code","message","data"},
     *       @OA\Property(property="code", type="string"),
     *       @OA\Property(property="message", type="string"),
     *       @OA\Property(property="data", type="object"),
     *     )
     *   ),
     *   @OA\Response(response=401, description="No Autorizado"),
     * )
     */

    public function login(Request $request)
    {
        $result = new Result();

        $credentials = request(['email', 'password']);

        $userExist = User::where('email','=',$request->email)->first();

        if(!isset($userExist)) {

            $result->statusCode = Response::HTTP_UNAUTHORIZED;
            $result->statusMessage = 'El usuario no se encuentra afiliado.';
            $result->result = (object) [
                'id'    => 1,
                'status' => 'El usuario no se encuentra afiliado.',
            ];
            $result->id = 1;
            $result->size = 1;
            $result->isSucess = false;
            $result->date = date('Y-m-d');
            $result->error = (object) [
                'id'    => 1,
                'status' => 'El usuario no se encuentra afiliado.',
            ];


            return response()->json($result, $result->statusCode);
        }

        if (!$token = Auth::attempt($credentials)) {

            $result->statusCode = Response::HTTP_UNAUTHORIZED;
            $result->statusMessage = 'Su contraseña es incorrecta';
            $result->result = (object) [
                'id'    => 1,
                'status' => 'Su contraseña es incorrecta',
            ];
            $result->id = 1;
            $result->size = 1;
            $result->isSucess = false;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);
        }

        $user = User::select('user.id as id', 'cia_id' , 'user.first_name', 'user.last_name', 'user.email', 'user.password', 'role','user.check_email')
        ->leftJoin('company_user', 'user.id', '=', 'company_user.user_id')
        ->where('user.email', $request->email)
        ->whereIn('user.status', [1,3])
        ->first();

        if($user) {
            if($user->check_email == 1){
                $count_company = CompanyUser::where('user_id','=', $user->id)->count();
                if($count_company > 0) {
                    if($count_company >= 1) {
                        $companies = CompanyUser::where('user_id','=',$user->id)->select('cia_id')->get();

                        $ids = [];
                        foreach($companies AS $company){
                                array_push($ids, $company->cia_id);
                        }

                        $companies = Company::whereIn('id',$ids)->select('id as cia_id', 'name as company_name', 'legal_name', 'legal_number', 'logo as icon', 'status')
                            ->orderBy('name','ASC')
                            ->whereIn('status',[1,3])
                            ->get();

                            $user->password = null;

                            $result->statusCode = Response::HTTP_OK;
                            $result->statusMessage = 'Inicio sesión';
                            $result->result = (object) [
                                'has_companies' => true,
                                'user'    => $user,
                                /* 'companies' => $companies, */
                                'access_token' => $token,
                                'token_type' => 'bearer',
                                'expires_in' => Auth::factory()->getTTL() * 60
                            ];
                            $result->id = 1;
                            $result->size = 1;
                            $result->isSucess = true;
                            $result->date = date('Y-m-d');

                    }
                } else {
                    $result->statusCode = Response::HTTP_UNAUTHORIZED;
                    $result->statusMessage = 'No hay compañías registradas con ese usuario';
                    $result->result = (object) [
                        'id'    => 1,
                        'status' => 'No hay compañías registradas con ese usuario',
                    ];
                    $result->isSucess = false;
                    $result->id = 1;
                    $result->size = 1;
                    $result->date = date('Y-m-d');
                    $result->error = (object) [
                        'id'    => 1,
                        'status' => 'No hay compañías registradas con ese usuario',
                    ];
                }
            } else {
                $result->statusCode = Response::HTTP_UNAUTHORIZED;
                $result->statusMessage = 'Por favor, confirmar su correo';
                $result->result = (object) [
                    'id'    => 1,
                    'status' => 'Por favor, confirmar su correo',
                ];
                $result->isSucess = false;
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');
                $result->error = (object) [
                    'id'    => 1,
                    'status' => 'Por favor, confirmar su correo',
                ];
            }
        } else {

            $result->statusCode = Response::HTTP_UNAUTHORIZED;
            $result->statusMessage = 'Usuario no registrado, para continuar crea un nuevo usuario';
            $result->result = (object) [
                'id'    => 1,
                'status' => 'Usuario no registrado, para continuar crea un nuevo usuario',
            ];
            $result->isSucess = false;
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');
            $result->error = (object) [
                'id'    => 1,
                'status' => 'Usuario no registrado, para continuar crea un nuevo usuario',
            ];
        }

        return response()->json($result, $result->statusCode);
    }

    /**
     * @OA\Post(
     *   tags={"Seguridad"},
     *   path="/api/forgotpassword",
     *   summary="Recuperar contraseña",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"email"},
     *       @OA\Property(property="email", type="string"),
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Se ha enviado el código de verificación",
     *     @OA\JsonContent(
     *       type="object",
     *       required={"code","message","data"},
     *       @OA\Property(property="code", type="string"),
     *       @OA\Property(property="message", type="string"),
     *       @OA\Property(property="data", type="object"),
     *     )
     *   ),
     *   @OA\Response(response=400, description="No se ha enviado el código de verificación"),
     * )
     */

    public function forgotPassword(Request $request)
    {
        $result = new Result();

        try {

            $user = Customer::where('email', $request->email)->first();

            if( $user ) {

                $resultOPT = ApiController::GetCodeOTP($request->email);
                $token = $resultOPT->token;

                Log::info("OTP: ".$resultOPT->otp);
                Log::info("Correo: ".$request->email);
                Log::info("Token: ".$token);
                $nueva_cookie = cookie('token_phone',''.$token,1);

                $mailbody = new OTPCodeValidator($user->first_name,$user->last_name, $resultOPT->otp);

                $mail = new PHPMailer();
                $mail->isSMTP();
                $mail->isHTML(true);
                $mail->setFrom('sac@tulivery.com', config('app.name'));
                $mail->addAddress($request->email, $user->first_name." ".$user->last_name);
                $mail->Username = config('mail.mailers.smtp.username');  //"AKIA2RXLAJJKYN747G6O";
                $mail->Password = config('mail.mailers.smtp.password'); //"BIfgBbCcvON/ilK32VqNs3SDh1EXuAPSw4dW9P8aQ3KH";
                $mail->Host = config('mail.mailers.smtp.host'); //"email-smtp.us-east-1.amazonaws.com";
                $mail->Subject = 'Recuperación de contraseña '.config('app.name');
                $mail->Body = $mailbody->render();
                $mail->SMTPAuth = true;
                $mail->SMTPDebug = 0;
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                $mail->CharSet = 'UTF-8';
                $mail->SMTPSecure = "tls";
                $mail->Port = config('mail.mailers.smtp.port');

                $mail->AltBody = '';

                if($mail->send()){
                    $nueva_cookie = cookie('token_email',''.$token,5);
                    Session::put('token_email',$token);
                    Session::put('email_code',$request->email);

                    $result->statusCode = Response::HTTP_OK;
                    $result->statusMessage = 'Se ha enviado el código de verificación';
                    $result->isSucess = true;
                    $result->result = (object) [
                        'token' => $token
                    ];
                    $result->id = 1;
                    $result->size = 1;
                    $result->date = date('Y-m-d');

                    return response()->json($result, $result->statusCode);

                } else {
                    $result->statusCode = Response::HTTP_BAD_REQUEST;
                    $result->statusMessage = 'No se ha enviado el código de verificación';
                    $result->isSucess = false;
                    $result->result = (object) [
                        'id'        => 1,
                        'status'    => 'No se ha enviado el código de verificación',
                    ];
                    $result->id = 1;
                    $result->size = 1;
                    $result->date = date('Y-m-d');
                    $result->error = $mail->ErrorInfo;

                    return response()->json($result, $result->statusCode);
                }

            }

        $result->statusCode = Response::HTTP_UNAUTHORIZED;
        $result->statusMessage = 'Usuario no registrado en Tulivery Tienda';
        $result->isSucess = false;
        $result->result = (object) [
            'id'        => 1,
            'status'    => 'Usuario no registrado en Tulivery Tienda',
        ];
        $result->id = 1;
        $result->size = 1;
        $result->date = date('Y-m-d');

        return response()->json($result, $result->statusCode);

        } catch ( Exception $ex) {

            $result->statusCode = Response::HTTP_BAD_REQUEST;
            $result->statusMessage = 'ERROR';
            $result->isSucess = false;
            $result->result = (object) [
                'id'        => 1,
                'status'    => $ex,
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');
            $result->error = $ex;

            return response()->json($result, $result->statusCode);
        }
    }

    /**
     * @OA\Post(
     *   tags={"Seguridad"},
     *   path="/api/recoverpasswordwithemail",
     *   summary="Recuperar contraseña con correo electrónico",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"email","password"},
     *       @OA\Property(property="email", type="string"),
     *       @OA\Property(property="password", type="string"),
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Se actualizó la contraseña",
     *     @OA\JsonContent(
     *       type="object",
     *       required={"code","message","data"},
     *       @OA\Property(property="code", type="string"),
     *       @OA\Property(property="message", type="string"),
     *       @OA\Property(property="data", type="object"),
     *     )
     *   ),
     *   @OA\Response(response=400, description="No se pudo actualizar"),
     *   @OA\Response(response=401, description="Usuario no registrado en Tulivery Tienda"),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function recoverPasswordWithEmail(Request $request)
    {

        $result = new Result();

        $user = DB::table('company_user')
            ->select('id','cia_id','user_id', 'first_name', 'last_name', 'email', 'password', 'role', 'check_email')
            ->where('email', $request->email)
            ->whereIn('status', [1,3])
            ->first();

        if($user){

            $password = Hash::make($request->password);
            $update = DB::table('company_user')
                        ->where('email', $request->email)
                        ->update(['password' =>   $password]);

            if ($update){

                if($user->role == "adm"|| $user->role == "sys"|| $user->role == "sac"){
                    $stores = DB::table('store as s')
                        ->join('company as c', 'c.id', '=', 's.cia_id')
                        ->select('s.id','s.cia_id','s.title','s.legal_name','s.legal_number'
                        ,'s.phone','s.street_name','s.street_number','s.reference','s.district'
                        ,'s.province','s.city','s.ubigeo','s.latitude','s.longitude','s.cover','s.status','c.name as company_name','c.logo as icon')
                        ->where('s.cia_id','=',$user->cia_id)
                        ->whereIn('s.status',[1,3])
                        ->get();
                }else if($user->role == "usr"){
                    $store_ids = CompanyUserStore::select('store_id')
                        ->where('cia_id','=',$user->cia_id)
                        ->where('user_id','=',$user->id)
                        ->where('status','=',1)
                        ->get();

                    $ids = [];
                    foreach($store_ids AS $store){
                        array_push($ids, $store->store_id);
                    }

                    $stores = DB::table('store as s')
                        ->join('company as c', 'c.id', '=', 's.cia_id')
                        ->select('s.id','s.cia_id','s.title','s.legal_name','s.legal_number'
                        ,'s.phone','s.street_name','s.street_number','s.reference','s.district'
                        ,'s.province','s.city','s.ubigeo','s.latitude','s.longitude','s.cover','s.status','c.name as company_name','c.logo as icon')
                        ->whereIn('s.id',$ids)
                        ->where('s.cia_id','=',$user->cia_id)
                        ->whereIn('s.status',[1,3])
                        ->get();
                }

                $user->id = $user->user_id;
                $user->password = $request->password;
                if(count($stores) > 0){
                    foreach($stores as $store){
                        $store->district = DB::table('geo_district')
                            ->where('id', $store->district)
                            ->value('district');
                        $store->city = DB::table('geo_city')
                            ->where('id', $store->city)
                            ->value('city');
                        $store->province = DB::table('geo_province')
                            ->where('id', $store->province)
                            ->value('province');
                    }

                    $result->statusCode = Response::HTTP_OK;
                    $result->statusMessage = 'Se actualizó la contraseña';
                    $result->isSucess = false;
                    $result->result = (object) [
                        'user'    => $user,
                        'stores' => $stores
                    ];
                    $result->id = 1;
                    $result->size = 1;
                    $result->date = date('Y-m-d');

                } else {

                    $result->statusCode = Response::HTTP_OK;
                    $result->statusMessage = 'Se actualizó la contraseña';
                    $result->isSucess = false;
                    $result->result = (object) [
                        'user'    => $user
                    ];
                    $result->id = 1;
                    $result->size = 1;
                    $result->date = date('Y-m-d');

                }


            } else {

                $result->statusCode = Response::HTTP_BAD_REQUEST;
                $result->statusMessage = 'No se pudo actualizar';
                $result->isSucess = false;
                $result->result = (object) [
                    'id'    => 1,
                    'status' => 'No se pudo actualizar'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

            }

        } else {

            $result->statusCode = Response::HTTP_UNAUTHORIZED;
            $result->statusMessage = 'Usuario no registrado en Tulivery Tienda';
            $result->isSucess = false;
            $result->result = (object) [
                'id'    => 1,
                'status' => 'Usuario no registrado en Tulivery Tienda'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

        }

        return response()->json($result, $result->statusCode);
    }

    /**
     * @OA\Post(
     *   tags={"Seguridad"},
     *   path="/api/changepasswordwithemail",
     *   summary="Cambiar contraseña con correo electrónico",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"email","password"},
     *       @OA\Property(property="email", type="string"),
     *       @OA\Property(property="password", type="string"),
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Se actualizó la contraseña",
     *     @OA\JsonContent(
     *       type="object",
     *       required={"code","message","data"},
     *       @OA\Property(property="code", type="string"),
     *       @OA\Property(property="message", type="string"),
     *       @OA\Property(property="data", type="object"),
     *     )
     *   ),
     *   @OA\Response(response=400, description="No se pudo actualizar"),
     *   @OA\Response(response=401, description="Usuario no registrado  o no tiene tiendas asignada en Tulivery Tienda"),
     * )
     */
    public function changePasswordWithforgotPassword(Request $request)
    {
        $result = new Result();
        $authlucapostoken = $request->headers->all("auth-lucapos-token");

        if (!isset($authlucapostoken))
        {
            $result->statusCode = Response::HTTP_UNAUTHORIZED;
            $result->statusMessage = 'No se pudo cambiar la contraseña por que no se envio el token de seguridad';
            $result->isSucess = false;
            $result->result = (object) [
                'id' => 1,
                'status' => 'No se pudo cambiar la contraseña por que no se envio el token de seguridad'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);
        }

        $token = DB::table('user')->select('token')->where('email', $request->email)->first();

        if ($token->token != $authlucapostoken[0])
        {
            $result->statusCode = Response::HTTP_UNAUTHORIZED;
            $result->statusMessage = 'El token de seguridad no coincide.';
            $result->isSucess = false;
            $result->result = (object) [
                'id' => 1,
                'status' => 'El token de seguridad no coincide.'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);
        }

        $password = Hash::make($request->password);

        DB::table('user')->where('email', $request->email)->update(['token' =>  $request->token, 'password' => $password, 'check_email' => 1]);

        $credentials = request(['email', 'password']);;

        if (!$token = Auth::attempt($credentials)) {

            $result->statusCode = Response::HTTP_UNAUTHORIZED;
            $result->statusMessage = 'Su contraseña es incorrecta';
            $result->result = (object) [
                'id'    => 1,
                'status' => 'Su contraseña es incorrecta',
            ];
            $result->id = 1;
            $result->size = 1;
            $result->isSucess = false;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);
        }

        $user = User::select('user.id as id', 'cia_id' , 'user.first_name', 'user.last_name', 'user.email', 'user.password', 'role','user.check_email')
        ->leftJoin('company_user', 'user.id', '=', 'company_user.user_id')
        ->where('user.email', $request->email)
        ->whereIn('user.status', [1,3])
        ->first();

        $user->password = null;

        $result->statusCode = Response::HTTP_OK;
        $result->statusMessage = 'Se actualizó la contraseña';
        $result->isSucess = true;
        $result->result = (object) [
            'has_companies' => true,
            'user'    => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60
        ];
        $result->id = 1;
        $result->size = 1;
        $result->date = date('Y-m-d');

        return response()->json($result, $result->statusCode);


        if($user){

            $user = DB::table('company_user')
            ->select('cia_id','user_id as id', 'first_name', 'last_name', 'email', 'password', 'role', 'check_email')
            ->where('email', $request->email)
            ->whereIn('status', [1,3])
            ->first();

            $password = Hash::make($request->password);
            $update = DB::table('company_user')
                        ->where('email', $request->email)
                        ->update(['password' =>   $password, 'check_email' => 1]);

            DB::table('user')
                ->where('email', $request->email)
                ->update(['token' =>  $request->token, 'password' => $password, 'check_email' => 1]);

            $credentials = request(['email', 'password']);;
            $access_token = Auth::attempt($credentials);

            if ($update){

                $count_company = User::where('email','=',$request->email)->count();

                if($count_company > 0){

                    if($count_company > 1){

                        $companies = User::where('email','=',$request->email)
                            ->select('cia_id')
                            ->get();

                        $ids = [];
                        foreach($companies AS $company){
                             array_push($ids, $company->cia_id);
                        }

                        $companies = Company::whereIn('id',$ids)
                            ->select('id as cia_id', 'name as company_name', 'logo as icon')
                            ->orderBy('name','ASC')
                            ->get();

                            $result->statusCode = Response::HTTP_OK;
                            $result->statusMessage = 'Se actualizó la contraseña';
                            $result->isSucess = true;
                            $result->result = (object) [
                                'has_companies' => true,
                                'user'    => $user,
                                'companies' => $companies,
                                'access_token' => $access_token,
                                'token_type' => 'bearer',
                                'expires_in' => Auth::factory()->getTTL() * 60
                            ];
                            $result->id = 1;
                            $result->size = 1;
                            $result->date = date('Y-m-d');

                            return response()->json($result, $result->statusCode);

                    }else{
                        if($user->role == "adm" || $user->role == "sys"|| $user->role == "sac" || $user->role == "own"){
                            $stores = DB::table('store as s')
                                ->join('company as c', 'c.id', '=', 's.cia_id')
                                ->select('s.id','s.cia_id','s.title','s.legal_name','s.legal_number'
                                ,'s.phone','s.street_name','s.street_number','s.reference','s.district'
                                ,'s.province','s.city','s.ubigeo','s.latitude','s.longitude','s.cover','s.license_id','s.license_status','s.status','c.name as company_name','c.logo as icon')
                                ->where('s.cia_id','=',$user->cia_id)
                                ->whereIn('s.status',[1,3])
                                ->get();

                        }else if($user->role == "usr"){
                            $store_ids = CompanyUserStore::select('store_id')
                                ->where('cia_id','=',$user->cia_id)
                                ->where('user_id','=',$user->id)
                                ->where('status','=',1)
                                ->get();

                            $ids = [];
                            foreach($store_ids AS $store){
                                array_push($ids, $store->store_id);
                            }

                            $stores = DB::table('store as s')
                                ->join('company as c', 'c.id', '=', 's.cia_id')
                                ->select('s.id','s.cia_id','s.title','s.legal_name','s.legal_number'
                                ,'s.phone','s.street_name','s.street_number','s.reference','s.district'
                                ,'s.province','s.city','s.ubigeo','s.latitude','s.longitude','s.cover','s.license_id','s.license_status','s.status','c.name as company_name','c.logo as icon')
                                ->whereIn('s.id',$ids)
                                ->where('s.cia_id','=',$user->cia_id)
                                ->whereIn('s.status',[1,3])
                                ->get();
                        }

                        if(isset($stores)){
                            if(count($stores) > 0){

                                foreach($stores as $store){
                                    Log::info("store: ".$store->id);
                                    $store->district = DB::table('geo_district')
                                        ->where('id', $store->district)
                                        ->value('district');
                                    $store->city = DB::table('geo_city')
                                        ->where('id', $store->city)
                                        ->value('city');
                                    $store->province = DB::table('geo_province')
                                        ->where('id', $store->province)
                                        ->value('province');
                                }

                                $result->statusCode = Response::HTTP_OK;
                                $result->statusMessage = 'Se actualizó la contraseña';
                                $result->isSucess = true;
                                $result->result = (object) [
                                    'user'    => $user,
                                    'has_companies' => false,
                                    'stores' => $stores,
                                    'access_token' => $access_token,
                                    'token_type' => 'bearer',
                                    'expires_in' => Auth::factory()->getTTL() * 60
                                ];
                                $result->id = 1;
                                $result->size = 1;
                                $result->date = date('Y-m-d');

                            } else {
                                $result->statusCode = Response::HTTP_UNAUTHORIZED;
                                $result->statusMessage = 'No hay tiendas asignadas a esa cuenta';
                                $result->isSucess = false;
                                $result->result = (object) [
                                    'id' => 1,
                                    'status' => 'No hay tiendas asignadas a esa cuenta'
                                ];
                                $result->id = 1;
                                $result->size = 1;
                                $result->date = date('Y-m-d');
                            }
                        } else {

                            $result->statusCode = Response::HTTP_UNAUTHORIZED;
                            $result->statusMessage = 'No hay tiendas asignadas a esa cuenta';
                            $result->isSucess = false;
                            $result->result = (object) [
                                'id' => 1,
                                'status' => 'No hay tiendas asignadas a esa cuenta'
                            ];
                            $result->id = 1;
                            $result->size = 1;
                            $result->date = date('Y-m-d');

                        }

                    }

                } else {

                    $result->statusCode = Response::HTTP_UNAUTHORIZED;
                    $result->statusMessage = 'No hay compañías registradas con ese usuario';
                    $result->isSucess = false;
                    $result->result = (object) [
                        'id' => 1,
                        'status' => 'No hay compañías registradas con ese usuario'
                    ];
                    $result->id = 1;
                    $result->size = 1;
                    $result->date = date('Y-m-d');

                }

            } else {

                $result->statusCode = Response::HTTP_BAD_REQUEST;
                $result->statusMessage = 'No se pudo actualizar';
                $result->isSucess = false;
                $result->result = (object) [
                    'id' => 1,
                    'status' => 'No se pudo actualizar'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');
            }

        } else {

            $result->statusCode = Response::HTTP_UNAUTHORIZED;
            $result->statusMessage = 'Usuario no registrado en Tulivery Tienda';
            $result->isSucess = false;
            $result->result = (object) [
                'id' => 1,
                'status' => 'Usuario no registrado en Tulivery Tienda'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

        }

        return response()->json($result, $result->statusCode);
	}

    /**
     * @OA\Post(
     *   tags={"Registrar"},
     *   path="/api/registercompany",
     *   summary="Registrar Comercio y Usuario",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"name, last_name, phone, store_name, email, password"},
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="last_name", type="string"),
     *       @OA\Property(
     *         property="phone",
     *         type="object",
     *         ref="#/components/schemas/Phone"
     *       ),
     *       @OA\Property(property="store_name", type="string"),
     *       @OA\Property(property="email", type="string"),
     *       @OA\Property(property="password", type="string"),
     *     ),
     *   ),
     *   @OA\Response(response=200, description="Registrado con exito."),
     *   @OA\Response(response=401, description="El usuario con el correo proporcionado ya tiene una comercio registrado o campos incompletos."),
     *   @OA\Response(response=500, description="Excepcion no controlada."),
     * )
     */
    public function registerCompany(Request $request)
    {
        $result = new Result();

        if(empty($request->name)){
            $result->statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
            $result->statusMessage = 'Por favor ingresa un nombre.';
            $result->isSucess = false;
            $result->result = (object) [
                'id' => 1,
                'status' => 'Por favor ingresa un nombre.'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);
        }

        if(empty($request->last_name)){
            $result->statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
            $result->statusMessage = 'Por favor ingresa un apellido.';
            $result->isSucess = false;
            $result->result = (object) [
                'id' => 1,
                'status' => 'Por favor ingresa un apellido.'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);
        }

        if(empty($request->email)){
            $result->statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
            $result->statusMessage = 'Por favor ingresa un correo electrónico';
            $result->isSucess = false;
            $result->result = (object) [
                'id' => 1,
                'status' => 'Por favor ingresa un correo electrónico'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);
        }

        if(empty($request->password)){
            $result->statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
            $result->statusMessage = 'Por favor ingresa un contraseña.';
            $result->isSucess = false;
            $result->result = (object) [
                'id' => 1,
                'status' => 'Por favor ingresa un contraseña.'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);
        }

        if(empty($request->phone)){

            $result->statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
            $result->statusMessage = 'Por favor ingresa un número de teléfono.';
            $result->isSucess = false;
            $result->result = (object) [
                'id' => 1,
                'status' => 'Por favor ingresa un número de teléfono.'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);
        }

        if(empty($request->store_name)){

            $result->statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
            $result->statusMessage = 'Por favor ingresa el nombre de la tienda.';
            $result->isSucess = false;
            $result->result = (object) [
                'id' => 1,
                'status' => 'Por favor ingresa el nombre de la tienda.'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);
        }

        try {

            DB::beginTransaction();

            $customer = User::where('email','=',$request->email)->first();

            if(!isset($customer)){
                $customer = new User;
                $customer->first_name = $request->name;
                $customer->last_name = $request->last_name;
                $customer->phone = $request->phone['e164Number'];
                $customer->email = $request->email;
                $customer->password = Hash::make($request->password);
                $customer = UsersController::registerCustomer($customer);
            }

            $company = CompanyUser::where('user_id','=',$customer->id)
                        ->where('role','=','own')
                        ->first();

            if (isset($company)) {

                $result->statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
                $result->statusMessage = 'El usuario con el correo: <b>'.$request->email.'</b> proporcionado ya tiene una comercio registrado.';
                $result->isSucess = false;
                $result->result = (object) [
                    'id' => 1,
                    'status' => 'El usuario con el correo proporcionado ya tiene una comercio registrado.'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }


            $company = new CompanyInscripcion();
            $company->user_id = $customer->id;
            $company->user_name = $customer->email;
            $company->user_pass = $customer->password;
            $company->company = $request->store_name;
            $company->client_name = $customer->first_name;
            $company->client_lastname = $customer->last_name;
            $company->client_phone = $customer->phone;
            $company->status = $request->opt_register;
            $company->save();

            $company_user = new CompanyUser();
            $company_user->user_id = $customer->id;
            $company_user->first_name = $customer->first_name;
            $company_user->last_name = $customer->last_name;
            $company_user->role = "own";
            $company_user = CompanyController::registerCompanyUser($company_user);

            DB::commit();

            $mailbody = new RegisterCompany($customer->first_name, $customer->last_name, $company->company);

            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->isHTML(true);
            $mail->setFrom('sac@tulivery.com', config('app.name'));
            $mail->addAddress($customer->email, $customer->first_name." ".$customer->last_nam);
            $mail->Username = config('mail.mailers.smtp.username');  //"AKIA2RXLAJJKYN747G6O";
            $mail->Password = config('mail.mailers.smtp.password'); //"BIfgBbCcvON/ilK32VqNs3SDh1EXuAPSw4dW9P8aQ3KH";
            $mail->Host = config('mail.mailers.smtp.host'); //"email-smtp.us-east-1.amazonaws.com";
            $mail->Subject = 'Gracias por registrarse en '.config('app.name');
            $mail->Body = $mailbody->render();
            $mail->SMTPAuth = true;
            $mail->SMTPDebug = 0;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $mail->CharSet = 'UTF-8';
            $mail->SMTPSecure = "tls";
            $mail->Port = config('mail.mailers.smtp.port');

            $mail->AltBody = '';

            $mail->send();

            $credentials = request(['email', 'password']);
            if (!$token = Auth::attempt($credentials)) {

                $result->statusCode = Response::HTTP_UNAUTHORIZED;
                $result->statusMessage = 'Su contraseña es incorrecta';
                $result->result = (object) [
                    'id'    => 1,
                    'status' => 'Su contraseña es incorrecta',
                ];
                $result->id = 1;
                $result->size = 1;
                $result->isSucess = false;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            $result->statusCode = Response::HTTP_OK;
            $result->statusMessage = 'Registrado con exito.';
            $result->isSucess = false;
            $result->result = (object) [
                'id' => 1,
                'status' => 'Registrado con exito.'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);

        } catch (Exception $ex) {
            DB::rollBack();
            $result->statusCode = 500;
            $result->statusMessage = 'No se pudo registrar la tienda:'.$ex->errorInfo[2];
            $result->isSucess = false;
            $result->result = $ex->errorInfo;
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');
            $result->error = $ex;

            return response()->json($result, $result->statusCode);
        }

    }

    /**
     * @OA\Get(
     *   tags={"Extras"},
     *   path="/api/getuserbyemail",
     *   summary="Comprobar usuario registrado por Email",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\Parameter(ref="#/components/parameters/email"),
     *   ),
     *   @OA\Response(response=200, description="Usuario encontrado"),
     *   @OA\Response(response=201, description="Usuario registrado pero no verificado."),
     *   @OA\Response(response=206, description="No se encontro usuario registrado."),
     *   @OA\Response(response=400, description="Error no controlado."),
     * )
     */
    public function getUserByEmail($email)
    {
        $result = new Result();

        try {

            $user = Customer::where('email','=', $email)
            ->first();

            if($user) {

                if ($user->check_email === 0) {

                    $result->statusCode = Response::HTTP_CREATED;
                    $result->statusMessage = 'Usuario registrado pero no verificado.';
                    $result->isSucess = false;
                    $result->result = (object) [
                        'email' => $user->email,
                        'phone' => $user->phone
                    ];
                    $result->id = 1;
                    $result->size = 1;
                    $result->date = date('Y-m-d');

                    return response()->json($result, $result->statusCode);
                }

                $result->statusCode = Response::HTTP_OK;
                $result->statusMessage = 'OK';
                $result->isSucess = false;
                $result->result = (object) [
                    'email' => $user->email,
                    'phone' => $user->phone
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            $result->statusCode = Response::HTTP_PARTIAL_CONTENT;
            $result->statusMessage = 'No hay usuario registrado con el email porporcionado.';
            $result->isSucess = false;
            $result->result = (object) [
                'id'        => 1,
                'status'    => 'No hay usuario registrado con el email porporcionado.',
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);

        } catch( Exception $ex) {

            $result->statusCode = Response::HTTP_BAD_REQUEST;
            $result->statusMessage = 'ERROR';
            $result->isSucess = false;
            $result->result = $ex->errorInfo;
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');
            $result->error = $ex;

            return response()->json($result, $result->statusCode);
        }

    }

    /**
     * @OA\Get(
     *   tags={"Extras"},
     *   path="/api/getusercompanybyemail",
     *   summary="Comprobar si el usuario cuenta con comercio registrado",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\Parameter(ref="#/components/parameters/email"),
     *   ),
     *   @OA\Response(response=200, description="El usuario puede registrar un comercio."),
     *   @OA\Response(response=201, description="Usuario registrado pero no verificado."),
     *   @OA\Response(response=401, description="Estimado Juan usted ya tiene un comercio con el correo xxx@xxx.com. Le recomendamos usar otro correo para un nuevo comercio."),
     *   @OA\Response(response=400, description="Error no controlado."),
     * )
     */
    public function getUserCompanyByEmail($email)
    {

        $result = new Result();

        try {

            $user = User::where('email','=', $email)->first();

            if(!isset($user)) {
                $result->statusCode = Response::HTTP_CREATED;
                $result->statusMessage = 'Usuario no registrado.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id' => 1,
                    'message' => 'Usuario no registrado.'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');
                return response()->json($result, $result->statusCode);
            }

            if ($user->check_email === 0) {

                $result->statusCode = Response::HTTP_CREATED;
                $result->statusMessage = 'Usuario registrado pero no verificado.';
                $result->isSucess = false;
                $result->result = (object) [
                    'email' => $user->email,
                    'phone' => $user->phone
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            $user_company = CompanyUser::where('email','=', $email)
                ->where('role','=', 'own')
                ->first();

            if($user_company) {

                $result->statusCode = Response::HTTP_UNAUTHORIZED;
                $result->statusMessage = 'Estimado <b>'.$user->first_name.' '.$user->last_name.'</b> usted ya tiene un comercio con el correo: <b>'.$user->email.'.</b><br> Le recomendamos usar otro correo para un nuevo comercio.';
                $result->isSucess = false;
                $result->result = (object) [
                    'id' => 1,
                    'status' => 'Estimado '.$user->first_name.' '.$user->last_name.' usted ya tiene un comercio con el correo '.$user->email.'. Le recomendamos usar otro correo para un nuevo comercio'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            $result->statusCode = Response::HTTP_OK;
            $result->statusMessage = 'OK';
            $result->isSucess = false;
            $result->result = (object) [
                'id'        => 1,
                'status'    => 'El usuario puede registrar un comercio.',
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);

        } catch( Exception $ex) {

            $result->statusCode = Response::HTTP_BAD_REQUEST;
            $result->statusMessage = $ex->errorInfo[2];
            $result->isSucess = false;
            $result->result = $ex->errorInfo;
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');
            $result->error = $ex;

            return response()->json($result, $result->statusCode);
        }

    }

    /**
     * @OA\Post(
     *   tags={"Seguridad"},
     *   path="/api/generatecodeformail",
     *   summary="Generar Codigo OTP con Email",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"email"},
     *       @OA\Property(property="email", type="string"),
     *     )
     *   ),
     *   @OA\Response(response=200, description="Se ha enviado el codigo de Verificacion"),
     *   @OA\Response(response=401, description="No se ha enviado el código de verificación"),
     * )
     */
    public function generateCodeforMail(Request $request)
    {
        $result = new Result();

        try {

            $resultOPT = ApiController::GetCodeOTP($request->email);
            $token = $resultOPT->token;

            Log::info("OTP: ".$resultOPT->otp);
            Log::info("Correo: ".$request->email);
            Log::info("Token: ".$token);
            $nueva_cookie = cookie('token_phone',''.$token,1);

            $mailbody = new OTPCodeValidator('','', $resultOPT->otp);

            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->isHTML(true);
            $mail->setFrom('sac@tulivery.com', config('app.name'));
            $mail->addAddress($request->email, $request->first_name." ".$request->last_name);
            $mail->Username = config('mail.mailers.smtp.username');  //"AKIA2RXLAJJKYN747G6O";
            $mail->Password = config('mail.mailers.smtp.password'); //"BIfgBbCcvON/ilK32VqNs3SDh1EXuAPSw4dW9P8aQ3KH";
            $mail->Host = config('mail.mailers.smtp.host'); //"email-smtp.us-east-1.amazonaws.com";
            $mail->Subject = 'Codigo de verificacion '.config('app.name');
            $mail->Body = $mailbody->render();
            $mail->SMTPAuth = true;
            $mail->SMTPDebug = 0;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $mail->CharSet = 'UTF-8';
            $mail->SMTPSecure = "tls";
            $mail->Port = config('mail.mailers.smtp.port');

            $mail->AltBody = '';

            if($mail->send()){

                $result->statusCode = Response::HTTP_OK;
                $result->statusMessage = 'Se ha enviado el código de verificación';
                $result->isSucess = true;
                $result->result = (object) [
                    'token' => $token
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);

            } else {
                $result->statusCode = Response::HTTP_BAD_REQUEST;
                $result->statusMessage = 'No se ha enviado el código de verificación';
                $result->isSucess = false;
                $result->result = (object) [
                    'id'        => 1,
                    'status'    => 'No se ha enviado el código de verificación',
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');
                $result->error = $mail->ErrorInfo;

                return response()->json($result, $result->statusCode);
            }

        } catch ( Exception $ex) {

            $result->statusCode = Response::HTTP_BAD_REQUEST;
            $result->statusMessage = 'ERROR';
            $result->isSucess = false;
            $result->result = (object) [
                'id'        => 1,
                'status'    => $ex,
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');
            $result->error = $ex;

            return response()->json($result, $result->statusCode);
        }
    }

    /**
     * @OA\Post(
     *   tags={"Seguridad"},
     *   path="/api/validatecode",
     *   summary="Validar código OTP",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"email","code","token"},
     *       @OA\Property(property="email", type="string"),
     *       @OA\Property(property="code", type="string"),
     *       @OA\Property(property="token", type="string"),
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OTP validado correctamente",
     *     @OA\JsonContent(
     *       type="object",
     *       required={"code","message","data"},
     *       @OA\Property(property="code", type="string"),
     *       @OA\Property(property="message", type="string"),
     *       @OA\Property(property="data", type="object"),
     *     )
     *   ),
     *   @OA\Response(response=400, description="OTP no validado"),
     * )
     */
    public function validateCode(Request $request)
    {
        $result = new Result();

        try {

            if(empty($request->code)) {

                $result->statusCode = Response::HTTP_NOT_FOUND;
                $result->statusMessage = 'El campo "code" no puede estar vacio';
                $result->isSucess = false;
                $result->result = (object) [
                    'id'        => 1,
                    'status'    => 'El campo "code" no puede estar vacio',
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            if(empty($request->token)) {
                $result->statusCode = Response::HTTP_NOT_FOUND;
                $result->statusMessage = 'El campo "token" no puede estar vacio';
                $result->isSucess = false;
                $result->result = (object) [
                    'id'        => 1,
                    'status'    => 'El campo "token" no puede estar vacio',
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            if(empty($request->email)) {
                $result->statusCode = Response::HTTP_NOT_FOUND;
                $result->statusMessage = 'Codigo de verificación correcto';
                $result->isSucess = false;
                $result->result = (object) [
                    'id'        => 1,
                    'status'    => 'Codigo de verificación correcto',
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            Log::info("OTP QUE RECIBO: ".$request->code);
            Log::info("CORREO QUE RECIBO: ".$request->email);
            Log::info("TOKEN QUE RECIBO: ".$request->token);


            $http = Http::get('http://ec2-34-239-124-25.compute-1.amazonaws.com/api/OTPUX/'.$request->email.'/'.$request->code.'/'.$request->token);
            $otp = $http->object();

            $result2 = $otp->isSucess;
            $sms = $otp->statusMessage;


            if($result2) {

                DB::table('user')
                ->where('email', $request->email)
                ->update(['token' =>  $request->token]);

                $result->statusCode = Response::HTTP_OK;
                $result->statusMessage = 'Codigo de verificación correcto';
                $result->isSucess = true;
                $result->result = (object) [
                    'id'        => 1,
                    'status'    => 'Codigo de verificación correcto',
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

            } else {

                $result->statusCode = Response::HTTP_BAD_REQUEST;
                $result->statusMessage = 'Codigo de verificación no valido o expirado'; //$otp->statusMessage;
                $result->isSucess = false;
                $result->result = (object) [
                    'id'        => 1,
                    'status'    => 'Codigo de verificación no valido',
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');
            }

            return response()->json($result, $result->statusCode);

        } catch (Exception $ex) {

            $result->statusCode = Response::HTTP_BAD_REQUEST;
            $result->statusMessage = 'ERROR';
            $result->isSucess = false;
            $result->result = (object) [
                'id'        => 1,
                'status'    => 'Error no controlado',
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');
            $result->error = $ex;

            return response()->json($result, $result->statusCode);
        }


    }

    /*** NOTIFICATION PUSH****/
    /*public function sendPushNotification(Request $request)
    {

        $token = DB::table('user')
            ->where('id',$request->user_id)
            ->value('token');

		$data = array(
			'title'      => $request->title,
			'body'       => $request->body,
            'index'		 => $request->index,
            'store_id'	 => $request->store_id
		);
		$post = array(
			'to'                => $token,
			'data'              => $data,
			'priority'          => 10,
			'content_available' => true,
			'ttl'               => 3600,
		);
		//-------------------------------------------------------------------------------
		$response = Curl::to('https://fcm.googleapis.com/fcm/send')
			->withHeader('Authorization: key=AAAADMv1buA:APA91bF6fRZsvNHzRZM-fHmFTXHx7kBBvn7NO7T2_PCdBRyu8v5Fn_1OjolnjPYjQqdlr8ENitIARlJMqy1SKkmywpBK4Og4e69zM7aoX9xdq1i8tKQh_aIkqoTFQw-0lMCswLRjMDoD')
			->withContentType('application/json')
			->withData(json_encode($post))
			->post();
		//-------------------------------------------------------------------------------
        return $response;

    }

    public function sendNotificationMachin($user_id, $title, $body, $index, $order_id)
    {

        $token = DB::table('user')
            ->where('id',$user_id)
            ->value('token_cus');

		$data = array(
			'title'      => $title,
			'body'       => $body,
			'index'		 => $index,
			'order_id'	 => $order_id
		);

		$post = array(
			'to'                => $token,
			'data'              => $data,
			'priority'          => 10,
			'content_available' => true,
			'ttl'               => 3600,
		);

		//-------------------------------------------------------------------------------
		$response = Curl::to('https://fcm.googleapis.com/fcm/send')
			->withHeader('Authorization: key=AAAAls22kBM:APA91bEvOuNM9RMmic1u74cHTN-dSjNoD_EgPo4fCD0ibiKqwlyQCDM8p2uicqqyCgvYDFtCfQGUp6NAOrZ5wa4LVWyIaF8lulqOuWde4iUG-j3w6eihgzhjTfsjteTkhYb1TYagBRdy')
			->withContentType('application/json')
			->withData(json_encode($post))
			->post();
		//-------------------------------------------------------------------------------
        return $response;

    } */

    static public function GetCodeOTP(string $email)
    {
            $http = Http::get('http://ec2-34-239-124-25.compute-1.amazonaws.com/api/OTPUX/'.$email);
            $otp = $http->object();
            return $otp->result;
    }

    /**
     * @OA\Get(
     *   tags={"Extras"},
     *   path="/api/ui/typedocument",
     *   summary="Tipos de documentos",
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=400, description="Error."),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function gettypedocument()
    {

      $result = new Result();

      try {

        $type_document = Parameters::where('type','=','doct_type')
                                    ->where('status','=','1')
                                    ->get();

        $result->statusCode = Response::HTTP_OK;
              $result->statusMessage = "OK";
              $result->isSucess = true;
              $result->result = $type_document;
              $result->id = 1;
              $result->size = 1;
              $result->date = date('Y-m-d');

       return response()->json($result, $result->statusCode);

      } catch(Exception $ex) {

            $result->statusCode = Response::HTTP_BAD_REQUEST;
            $result->statusMessage = 'ERROR';
            $result->isSucess = false;
            $result->result = (object) [
                'id'        => 1,
                'status'    => $ex,
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');
            $result->error = $ex;

            return response()->json($result, $result->statusCode);
      }


    }

    /**
     * @OA\Get(
     *   tags={"Extras"},
     *   path="/api/ui/typepaymentaccepted",
     *   summary="Metodos de Pagos",
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=400, description="Error."),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function gettypepaymentaccepted()
    {

        $result = new Result();

        try {

          $type_document = Parameters::where('type','=','payment_accepted')
                                    ->where('status','=','1')
                                    ->get();

          $result->statusCode = Response::HTTP_OK;
                $result->statusMessage = "OK";
                $result->isSucess = true;
                $result->result = $type_document;
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

         return response()->json($result, $result->statusCode);

        } catch(Exception $ex) {

              $result->statusCode = Response::HTTP_BAD_REQUEST;
              $result->statusMessage = 'ERROR';
              $result->isSucess = false;
              $result->result = (object) [
                  'id'        => 1,
                  'status'    => $ex,
              ];
              $result->id = 1;
              $result->size = 1;
              $result->date = date('Y-m-d');
              $result->error = $ex;

              return response()->json($result, $result->statusCode);
        }

    }

    /**
     * @OA\Get(
     *   tags={"Extras"},
     *   path="/api/ui/typeaddress",
     *   summary="Tipos de Direccion",
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=400, description="Error."),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function gettypeaddress()
    {

        $result = new Result();

        try {

          $type_document = Parameters::where('type','=','address_type')
                                    ->where('status','=','1')
                                    ->get();

          $result->statusCode = Response::HTTP_OK;
                $result->statusMessage = "OK";
                $result->isSucess = true;
                $result->result = $type_document;
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

         return response()->json($result, $result->statusCode);

        } catch(Exception $ex) {

              $result->statusCode = Response::HTTP_BAD_REQUEST;
              $result->statusMessage = 'ERROR';
              $result->isSucess = false;
              $result->result = (object) [
                  'id'        => 1,
                  'status'    => $ex,
              ];
              $result->id = 1;
              $result->size = 1;
              $result->date = date('Y-m-d');
              $result->error = $ex;

              return response()->json($result, $result->statusCode);
        }

    }

    /**
     * @OA\Get(
     *   tags={"Localización"},
     *   path="/api/ui/cities",
     *   summary="Ciudad",
     *   @OA\Response(response=200, description="Respuesta exitosa"),
     *   @OA\Response(response=404, description="No existe de imagen a subir"),
     *   @OA\Response(response=400, description="Error no controlado."),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function getCities()
    {

        $result = new Result();

        try {

          $cite = GeoCity::orderBy('city','ASC')->get();

          $result->statusCode = Response::HTTP_OK;
                $result->statusMessage = "OK";
                $result->isSucess = true;
                $result->result = $cite;
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

         return response()->json($result, $result->statusCode);

        } catch(Exception $ex) {

              $result->statusCode = Response::HTTP_BAD_REQUEST;
              $result->statusMessage = 'ERROR';
              $result->isSucess = false;
              $result->result = (object) [
                  'id'        => 1,
                  'status'    => $ex,
              ];
              $result->id = 1;
              $result->size = 1;
              $result->date = date('Y-m-d');
              $result->error = $ex;

              return response()->json($result, $result->statusCode);
        }
    }

    /**
     * @OA\Get(
     *   tags={"Localización"},
     *   path="/api/ui/provinces/{city_id}",
     *   summary="Provinica",
     *   @OA\Parameter(
     *     name="city_id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=200, description="Respuesta exitosa"),
     *   @OA\Response(response=404, description="No existe de imagen a subir"),
     *   @OA\Response(response=400, description="Error no controlado."),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function getProvinces($city_id)
    {

        $result = new Result();

        try {

          $cite = GeoProvince::orderBy('province','ASC')
                            ->where('city_id',$city_id)
                            ->get();

          $result->statusCode = Response::HTTP_OK;
                $result->statusMessage = "OK";
                $result->isSucess = true;
                $result->result = $cite;
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

         return response()->json($result, $result->statusCode);

        } catch(Exception $ex) {

              $result->statusCode = Response::HTTP_BAD_REQUEST;
              $result->statusMessage = 'ERROR';
              $result->isSucess = false;
              $result->result = (object) [
                  'id'        => 1,
                  'status'    => $ex,
              ];
              $result->id = 1;
              $result->size = 1;
              $result->date = date('Y-m-d');
              $result->error = $ex;

              return response()->json($result, $result->statusCode);
        }
    }

    /**
     * @OA\Get(
     *   tags={"Localización"},
     *   path="/api/ui/districts/{province_id}",
     *   summary="Distrito",
     *   @OA\Parameter(
     *     name="province_id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=200, description="Respuesta exitosa"),
     *   @OA\Response(response=404, description="No existe de imagen a subir"),
     *   @OA\Response(response=400, description="Error no controlado."),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function getDistricts($province_id)
    {

        $result = new Result();

        try {

          $cite = GeoDistrict::orderBy('district','ASC')
                            ->where('province_id',$province_id)
                            ->get();

          $result->statusCode = Response::HTTP_OK;
                $result->statusMessage = "OK";
                $result->isSucess = true;
                $result->result = $cite;
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

         return response()->json($result, $result->statusCode);

        } catch(Exception $ex) {

              $result->statusCode = Response::HTTP_BAD_REQUEST;
              $result->statusMessage = 'ERROR';
              $result->isSucess = false;
              $result->result = (object) [
                  'id'        => 1,
                  'status'    => $ex,
              ];
              $result->id = 1;
              $result->size = 1;
              $result->date = date('Y-m-d');
              $result->error = $ex;

              return response()->json($result, $result->statusCode);
        }
    }


    /**
     * @OA\Schema(
     *   schema="Company",
     *    @OA\Property(
     *     property="id",
     *     type="string",
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="legal_name",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="legal_document",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="legal_number",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="description",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="website",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="logo",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="company_type",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="payment_accepted",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="qr_account",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="qr_wallet",
     *     type="string"
     *   ),
     *    @OA\Property(
     *     property="qr_phone",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="activate_webstore",
     *     type="integer"
     *   ),
     *   @OA\Property(
     *     property="web_template",
     *     type="integer"
     *   ),
     *   @OA\Property(
     *     property="banner_web",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="banner_app",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="activate_stock",
     *     type="integer"
     *   ),
     *   @OA\Property(
     *     property="visible_zero_stock",
     *     type="integer"
     *   ),
     *   @OA\Property(
     *     property="activate_delivery",
     *     type="integer"
     *   ),
     *   @OA\Property(
     *     property="activate_moturider",
     *     type="integer"
     *   ),
     *   @OA\Property(
     *     property="ranking_service",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="ranking_delivery",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="ranking_avg",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="order_max",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="salesman_code",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="observations",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="sac_email",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="sac_phone",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="company_delivery",
     *     type="object",
     *     ref="#/components/schemas/CompanyDelivery"
     *   ),
     *   @OA\Property(
     *     property="company_tag",
     *     type="string"
     *   ),
     *
     * )
     */

    /**
     * @OA\Schema(
     *    @OA\Property(
     *     property="id",
     *     type="string",
     *   ),
     *   @OA\Property(
     *     property="cia_id",
     *     type="string",
     *   ),
     *   schema="CompanyDelivery",
     *   @OA\Property(
     *     property="auto_delivery",
     *     type="integer"
     *   ),
     *   @OA\Property(
     *     property="delivery_way",
     *     type="integer"
     *   ),
     *   @OA\Property(
     *     property="delivery_map",
     *     type="integer"
     *   ),
     *   @OA\Property(
     *     property="delivery_min",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="rate_type",
     *     type="integer"
     *   ),
     *   @OA\Property(
     *     property="rate_fix",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="rate_ruler_km",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="rate_var_km",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="rate_ruler_value",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="rate_var_value",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="rate_outcity_do",
     *     type="boolean"
     *   ),
     *   @OA\Property(
     *     property="rate_outcity_text",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="delivery_avg",
     *     type="string"
     *   ),
     *   @OA\Property(
     *     property="preparation_avg",
     *     type="string"
     *   ),
     * )
     */

    /**
     * @OA\Schema(
     *   schema="Store",
     *   required={"title", "phone", "street_name", "street_number", "district", "province", "city", "latitude","longitude"},
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *   ),
     *   @OA\Property(
     *     property="cia_id",
     *     type="string",
     *   ),
     *   @OA\Property(
     *     property="title",
     *     type="string",
     *   ),
     *   @OA\Property(
     *     property="phone",
     *     type="object",
     *     ref="#/components/schemas/Phone"
     *   ),
     *   @OA\Property(
     *     property="street_name",
     *     type="string",
     *   ),
     *   @OA\Property(
     *     property="street_number",
     *     type="string",
     *   ),
     *   @OA\Property(
     *     property="reference",
     *     type="string",
     *   ),
     *   @OA\Property(
     *     property="district",
     *     type="string",
     *   ),
     *   @OA\Property(
     *     property="province",
     *     type="string",
     *   ),
     *   @OA\Property(
     *     property="city",
     *     type="string",
     *   ),
     *   @OA\Property(
     *     property="latitude",
     *     type="string",
     *   ),
     *   @OA\Property(
     *     property="longitude",
     *     type="string",
     *   ),
     *   @OA\Property(
     *     property="store_hours",
     *     type="array",
     *     @OA\Items(ref="#/components/schemas/StoreHours")
     *   ),
     * )
     */

    /**
     * @OA\Schema(
     *   schema="StoreHours",
     *   required={"start_time", "end_time", "weekday"},
     *   @OA\Property(
     *     property="start_time",
     *     type="date",
     *   ),
     *   @OA\Property(
     *     property="end_time",
     *     type="date",
     *   ),
     *   @OA\Property(
     *     property="weekday",
     *     type="integer",
     *   ),
     * )
     */

    /**
     *  @OA\Schema(
     *   schema="User",
     *   required={"first_name", "last_name", "email", "phone"},
     *   @OA\Property(
     *     property="first_name",
     *     type="string",
     *     description="Nombre"
     *   ),
     *   @OA\Property(
     *     property="last_name",
     *     type="string",
     *     description="Apellidos"
     *   ),
     *   @OA\Property(
     *     property="email",
     *     type="string",
     *     description="Correo electronico"
     *   ),
     *   @OA\Property(
     *     property="phone",
     *     type="object",
     *     ref="#/components/schemas/Phone"
     *   ),
     *  )
     */

    /**
     * @OA\Schema(
     *   schema="Phone",
     *   required={"e164Number"},
     *   @OA\Property(
     *     property="number",
     *     type="string",
     *     description="982217786"
     *   ),
     *   @OA\Property(
     *     property="internationalNumber",
     *     type="string",
     *     description="+51 982 217 786"
     *   ),
     *   @OA\Property(
     *     property="nationalNumber",
     *     type="string",
     *     description="982 217 786"
     *   ),
     *   @OA\Property(
     *     property="e164Number",
     *     type="string",
     *     description="+51982217786"
     *   ),
     *   @OA\Property(
     *     property="countryCode",
     *     type="string",
     *     description="PE"
     *   ),
     *   @OA\Property(
     *     property="dialCode",
     *     type="string",
     *     description="+51"
     *   )
     * )
     */

    /**
     *  @OA\Schema(
     *   schema="ErrorStatus",
     *   @OA\Property(
     *     property="id",
     *     type="integer",
     *   ),
     *   @OA\Property(
     *     property="status",
     *     type="string",
     *   ),
     *  )
     */

    /**
     *  @OA\Schema(
     *   schema="StoreAssign",
     *   required={"chkStore","store_id"},
     *   @OA\Property(
     *     property="chkStore",
     *     type="boolean",
     *   ),
     *   @OA\Property(
     *     property="store_id",
     *     type="integer",
     *   ),
     *  )
     */
}

