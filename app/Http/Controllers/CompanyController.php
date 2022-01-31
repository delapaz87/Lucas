<?php

namespace App\Http\Controllers;

use App\Mail\InvitationCompanyMailer;
use App\Models\Company;
use App\Models\CompanyDelivery;
use App\Models\CompanyInscripcion;
use App\Models\CompanyTag;
use App\Models\CompanyUser;
use App\Models\CompanyUserStore;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use App\Object\Result;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPMailer\PHPMailer\PHPMailer;
use Tymon\JWTAuth\Facades\JWTAuth;

class CompanyController extends Controller
{

    /**
     * @OA\Get(
     *   tags={"Comercio"},
     *   path="/api/commerce/inscription",
     *   summary="Mostrar compañia inscrita",
     *   @OA\Response(response=200, description="Respuesta exitosa"),
     *   @OA\Response(response=400, description="Error no controlado."),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function getComanyInscripcion()
    {
        $result = new Result();

        try {

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

            $company_user = CompanyUser::where('user_id',$user->id)->where('role','own')->first();
            if(isset($company_user)) {

                $company_inscripcion = CompanyInscripcion::where('user_id',$user->id)->where('status','2')->first();

                if(isset($company_inscripcion)) {
                    $company_inscripcion->user_pass = null;

                    $result->statusCode = Response::HTTP_OK;
                    $result->statusMessage = 'Respuesta exitosa.';
                    $result->isSucess = true;
                    $result->result = $company_inscripcion;
                    $result->id = 1;
                    $result->size = 1;
                    $result->date = date('Y-m-d');

                    return response()->json($result, $result->statusCode);
                }
            }

            $result->statusCode = Response::HTTP_PARTIAL_CONTENT;
            $result->statusMessage = 'Respuesta exitosa.';
            $result->isSucess = true;
            $result->result = (object) [
                'id' => 1,
                'status' => 'No existe usuario registrado con ese usuario'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);

        } catch (Exception $ex) {

            $result->statusCode = Response::HTTP_BAD_REQUEST;
            $result->statusMessage = 'ERROR.';
            $result->isSucess = false;
            $result->result = $ex->errorInfo;;
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');
            $result->error = $ex;

            return response()->json($result, $result->statusCode);
        }

    }

    /**
     * @OA\Get(
     *   tags={"Comercio"},
     *   path="/api/commerce/company",
     *   summary="Mostrar compañia",
     *   @OA\Response(response=200, description="Respuesta exitosa"),
     *   @OA\Response(response=400, description="Error no controlado."),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function getComanyByUser()
    {
        $result = new Result();

        try {

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

            $company_user = CompanyUser::where('user_id',$user->id)->where('role','own')->first();

            if($company_user->cia_id == '') {

                $result->statusCode = Response::HTTP_PARTIAL_CONTENT;
                $result->statusMessage = 'Respuesta exitosa.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id' => 1,
                    'status' => 'No existe informacion de comercio.'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            $company = Company::where('id','=',$company_user->cia_id)->first();
            $company->company_delivery = CompanyDelivery::where('cia_id',$company_user->cia_id)->first();

            $tag = DB::table('company_tag')->select('tag_id')->where('cia_id',$company_user->cia_id)->get();

            $tag_array = [];
            foreach ($tag as &$value)
            {
                 array_push($tag_array, $value->tag_id);
            }

            $company->company_tag = $tag_array;

            $result->statusCode = Response::HTTP_OK;
            $result->statusMessage = 'Respuesta exitosa.';
            $result->isSucess = true;
            $result->result = $company;
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);

        } catch(Exception $ex) {

            $result->statusCode = Response::HTTP_BAD_REQUEST;
            $result->statusMessage = 'ERROR.';
            $result->isSucess = false;
            $result->result = $ex->errorInfo;;
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');
            $result->error = $ex;

            return response()->json($result, $result->statusCode);
        }
    }

    /**
     * @OA\Put(
     *   tags={"Comercio"},
     *   path="/api/commerce/company",
     *   summary="Actualizar compañia",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/Company")
     *   ),
     *   @OA\Response(response=200, description="Respuesta exitosa"),
     *   @OA\Response(response=400, description="Error no controlado."),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function updateCompany(Request $request)
    {

        $result = new Result();

        try {

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

            $company_user = CompanyUser::where('user_id',$user->id)->where('role','own')->first();

            if(!isset($company_user)){

                $result->statusCode = Response::HTTP_NOT_FOUND;
                $result->statusMessage = 'Respuesta exitosa.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id' => 1,
                    'status' => 'No existe informacion de comercio.'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            if($company_user->cia_id == '') {

                DB::beginTransaction();
                $company = new Company;
                $company->id = Uuid::uuid6();
                $company->name = $request->name;
                $company->legal_document = $request->legal_document;
                $company->legal_name = $request->legal_name;
                $company->legal_number = $request->legal_number;
                $company->website = $request->website;
                $company->description = $request->description;
                $company->logo = $request->logo;
                $company->qr_account = $request->qr_account;
                $company->banner_web = $request->banner_web;
                $company->banner_app = $request->banner_app;
                $company->qr_owner = $request->qr_owner;
                $company->qr_phone = $request->qr_phone;
                $company->web_url = $request->web_url;
                $company->acronym = CompanyController::generatedAcronym($request->name, 10);
                $company->payment_accepted = isset($request->payment_accepted) ? $request->payment_accepted : "1|2";
                $company->company_type = isset($request->company_type) ? $request->company_type : 9;
                $company->activate_webstore = isset($request->activate_webstore) ? $request->activate_webstore : 0;
                $company->web_template = isset($request->web_template) ? $request->web_template : 0;
                $company->activate_stock = isset($request->activate_stock) ? $request->activate_stock : 0;
                $company->visible_zero_stock = isset($request->visible_zero_stock) ? $request->visible_zero_stock : 0;
                $company->activate_delivery = isset($request->activate_delivery) ? $request->activate_delivery : 0;
                $company->covid_permission = isset($request->covid_permission) ? $request->covid_permission : 1;
                $company->is_working = isset($request->is_working) ? $request->is_working : 1;
                $company->security = isset($request->security) ? $request->security : 0;
                $company->subscription = isset($request->subscription) ? $request->subscription : 1;
                $company->admin_name = $user->first_name." ".$user->last_name;
                $company->admin_phone = $user->phone;
                $company->admin_email = $user->email;
                $company->sac_phone = $request->sac_phone;
                $company->sac_email = $request->sac_email;
                $company->status = 1;
                $company->save();

                $company_user->cia_id = $company->id;
                $company_user->save();

                if($company->activate_webstore == '1') {

                    $store = Store::where('cia_id',$company->id)->first();
                    if(!isset($store)){

                        $store = new Store();
                        $store->title = $company->name;
                        $store->cia_id = $company->id;
                        $store = StoresController::registerStore($store);

                        $user_store = new CompanyUserStore();
                        $user_store->cia_id = $company->id;
                        $user_store->user_id = $company_user->user_id;
                        $user_store->store_id = $store->id;
                        $user_store = StoresController::registerUserStore($user_store);

                    }

                }

                $company_inscripcion = CompanyInscripcion::where('user_id',$company_user->user_id)->first();
                $company_inscripcion->status = 9;
                $company_inscripcion->save();

                DB::commit();

            } else {

                DB::beginTransaction();

                $company = Company::where('id',$company_user->cia_id)->first();
                $company->name = $request->name;
                $company->legal_document = $request->legal_document;
                $company->legal_name = $request->legal_name;
                $company->legal_number = $request->legal_number;
                $company->website = $request->website;
                $company->description = $request->description;
                $company->logo = $request->logo;
                $company->qr_account = $request->qr_account;
                $company->banner_web = $request->banner_web;
                $company->banner_app = $request->banner_app;
                $company->qr_owner = $request->qr_owner;
                $company->qr_phone = $request->qr_phone;
                $company->web_url = $request->web_url;
                $company->payment_accepted = isset($request->payment_accepted) ? $request->payment_accepted : "1|2";
                $company->company_type = isset($request->company_type) ? $request->company_type : 9;
                $company->activate_webstore = isset($request->activate_webstore) ? $request->activate_webstore : 0;
                $company->web_template = isset($request->web_template) ? $request->web_template : 3;
                $company->activate_stock = isset($request->activate_stock) ? $request->activate_stock : 0;
                $company->visible_zero_stock = isset($request->visible_zero_stock) ? $request->visible_zero_stock : 0;
                $company->activate_delivery = isset($request->activate_delivery) ? $request->activate_delivery : 0;
                $company->covid_permission = isset($request->covid_permission) ? $request->covid_permission : 1;
                $company->is_working = isset($request->is_working) ? $request->is_working : 1;
                $company->security = isset($request->security) ? $request->security : 0;
                $company->subscription = isset($request->subscription) ? $request->subscription : 1;
                $company->admin_name = $user->first_name." ".$user->last_name;
                $company->admin_phone = $request->admin_phone;
                $company->admin_email = $request->admin_email;
                $company->sac_phone = $request->sac_phone;
                $company->sac_email = $request->sac_email;
                $company->status = 1;
                $company->save();

                if($company->activate_webstore == '1') {

                    $store = Store::where('cia_id',$company->id)->first();
                    if(!isset($store)){

                        $store = new Store();
                        $store->title = $company->name;
                        $store->cia_id = $company->id;
                        $store = StoresController::registerStore($store);

                        $user_store = new CompanyUserStore();
                        $user_store->cia_id = $company->id;
                        $user_store->user_id = $company_user->user_id;
                        $user_store->store_id = $store->id;
                        $user_store = StoresController::registerUserStore($user_store);

                    }

                }

                DB::commit();
            }

            if(isset($request->company_delivery)) {

                $delivery_map = $request->company_delivery['delivery_map'];
                $delivery_way = $request->company_delivery['delivery_way'];
                $rate_type = $request->company_delivery['rate_type'];
                $rate_fix = $request->company_delivery['rate_fix'];
                $delivery_min = $request->company_delivery['delivery_min'];
                $rate_ruler_km = $request->company_delivery['rate_ruler_km'];
                $rate_var_km = $request->company_delivery['rate_var_km'];
                $rate_ruler_value = $request->company_delivery['rate_ruler_value'];
                $rate_var_value = $request->company_delivery['rate_var_value'];
                $rate_outcity_do = $request->company_delivery['rate_outcity_do'];
                $rate_outcity_text = $request->company_delivery['rate_outcity_text'];

                $company_delivery = CompanyDelivery::where('cia_id', $company_user->cia_id )->first();

                if(isset($company_delivery)) {

                    DB::beginTransaction();
                    $company_delivery->auto_delivery = 0;
                    $company_delivery->delivery_map = $delivery_map;
                    $company_delivery->delivery_way = $delivery_way;
                    $company_delivery->rate_type = $rate_type;
                    $company_delivery->rate_fix = $rate_fix;
                    $company_delivery->delivery_min = $delivery_min;
                    $company_delivery->rate_ruler_km = $rate_ruler_km;
                    $company_delivery->rate_var_km = $rate_var_km;
                    $company_delivery->rate_ruler_value = $rate_ruler_value;
                    $company_delivery->rate_var_value = $rate_var_value;
                    $company_delivery->rate_outcity_do = $rate_outcity_do;
                    $company_delivery->rate_outcity_text = $rate_outcity_text;
                    $company_delivery->save();
                    DB::commit();

                } else {

                    DB::beginTransaction();
                    $newCompany = new CompanyDelivery();
                    $newCompany->cia_id = $company_user->cia_id;
                    $newCompany->auto_delivery = 0;
                    $newCompany->delivery_map = $delivery_map;
                    $newCompany->delivery_way = $delivery_way;
                    $newCompany->rate_type = $rate_type;
                    $newCompany->rate_fix = $rate_fix;
                    $newCompany->delivery_min = $delivery_min;
                    $newCompany->rate_ruler_km = $rate_ruler_km;
                    $newCompany->rate_var_km = $rate_var_km;
                    $newCompany->rate_ruler_value = $rate_ruler_value;
                    $newCompany->rate_var_value = $rate_var_value;
                    $newCompany->rate_outcity_do = $rate_outcity_do;
                    $newCompany->rate_outcity_text = $rate_outcity_text;
                    $newCompany->save();
                    DB::commit();
                }
            }

            if(isset($request->company_tag)) {

                DB::beginTransaction();
                CompanyTag::where('cia_id', $company_user->cia_id )->delete();

                foreach($request->company_tag as $t){

                    $tag = new CompanyTag();
                    $tag->cia_id = $company_user->cia_id;
                    $tag->tag_id = $t;
                    $tag->save();
                }

                DB::commit();
            }


            $com = self::replaceCharacteres($company->name).$company->legal_number;

            if(!file_exists("/var/www/html/tuliveryWeb/public/tiendas/".$com."/company")){
                mkdir("/var/www/html/tuliveryWeb/public/tiendas/".$com."/company",0777,true);
            }

            $company = Company::where('id',$company_user->cia_id)->first();
            $company->company_delivery = CompanyDelivery::where('cia_id',$company_user->cia_id)->first();
            $tag = DB::table('company_tag')->select('tag_id')->where('cia_id',$company_user->cia_id)->get();

            $tag_array = [];
            foreach ($tag as &$value)
            {
                 array_push($tag_array, $value->tag_id);
            }

            $company->company_tag = $tag_array;

            $result->statusCode = Response::HTTP_OK;
            $result->statusMessage = 'Actualizado con exito.';
            $result->isSucess = true;
            $result->result = $company;
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);

        } catch (Exception $ex) {

            DB::rollBack();
            $result->statusCode = 500;
            $result->statusMessage = 'ERROR';
            $result->isSucess = false;
            $result->result = $ex;
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');
            $result->error = $ex;

            return response()->json($result, $result->statusCode);
        }

    }

    /**
     * @OA\Get(
     *   tags={"Comercio by Usuario"},
     *   path="/api/company/users",
     *   summary="Mostrar usuarios by compañia",
     *   @OA\Response(response=200, description="Respuesta exitosa"),
     *   @OA\Response(response=400, description="Error no controlado."),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function getUsersByCompany()
    {

        $result = new Result();

        try {

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

            $company_user_owner = CompanyUser::where('user_id',$user->id)->where('role','own')->first();
            $company_user = CompanyUser::select('user.id as id', 'user.first_name', 'user.last_name', 'user.email', 'user.phone', 'company_user.role')
            ->leftJoin('user', 'user.id', '=', 'company_user.user_id')
            ->where('cia_id',$company_user_owner->cia_id)->get();

            $result->statusCode = Response::HTTP_OK;
            $result->statusMessage = 'Respuesta exitosa.';
            $result->isSucess = true;
            $result->result = $company_user;
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);

        } catch (Exception $ex) {

            $result->statusCode = Response::HTTP_BAD_REQUEST;
            $result->statusMessage = 'ERROR.';
            $result->isSucess = false;
            $result->result = $ex->errorInfo;;
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');
            $result->error = $ex;

            return response()->json($result, $result->statusCode);
        }
    }

    /**
     * @OA\Post(
     *   tags={"Comercio by Usuario"},
     *   path="/api/company/users",
     *   summary="Invitar usuario a compañia",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"first_name","last_name","email", "cia_id", "role"},
     *       @OA\Property(property="first_name", type="string"),
     *       @OA\Property(property="last_name", type="string"),
     *       @OA\Property(property="email", type="string"),
     *       @OA\Property(property="cia_id", type="string"),
     *       @OA\Property(property="role", type="string"),
     *     )
     *   ),
     *   @OA\Response(response=200, description="Respuesta exitosa"),
     *   @OA\Response(response=400, description="Error no controlado."),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function postUsersByCompany(Request $request)
    {
        $result = new Result();

        try {

            if(empty($request->first_name)) {

                $result->statusCode = Response::HTTP_NOT_FOUND;
                $result->statusMessage = 'Por favor ingresa el "first_name" del invitado.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id' => 1,
                    'status' => 'Por favor ingresa el "first_name" del invitado.'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            if(empty($request->last_name)) {

                $result->statusCode = Response::HTTP_NOT_FOUND;
                $result->statusMessage = 'Por favor ingresa el "last_name" del invitado.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id' => 1,
                    'status' => 'Por favor ingresa el "last_name" del invitado.'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            if(empty($request->email)) {

                $result->statusCode = Response::HTTP_NOT_FOUND;
                $result->statusMessage = 'Por favor ingresa el "email" del invitado.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id' => 1,
                    'status' => 'Por favor ingresa el "email" del invitado.'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            if(empty($request->cia_id)) {

                $result->statusCode = Response::HTTP_NOT_FOUND;
                $result->statusMessage = 'Por favor ingresa el "cia_id" del comercio.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id' => 1,
                    'status' => 'Por favor ingresa el "cia_id" del comercio.'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            if(empty($request->role)) {

                $result->statusCode = Response::HTTP_NOT_FOUND;
                $result->statusMessage = 'Por favor ingresa el "role" del invitado.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id' => 1,
                    'status' => 'Por favor ingresa el "role" del invitado.'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            $company_user = User::select('user.id as id', 'cia_id' , 'user.first_name', 'user.last_name', 'user.email', 'user.password', 'role','user.check_email')
            ->leftJoin('company_user', 'user.id', '=', 'company_user.user_id')
            ->where('user.email', $request->email)
            ->whereIn('company_user.role', ['adm','sys','sac','own'])
            ->first();

            if(isset($company_user)) {

                $result->statusCode = Response::HTTP_NOT_FOUND;
                $result->statusMessage = 'El usuario indicado no puede ser invitado.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id' => 1,
                    'status' => 'El usuario indicado no puede ser invitado.'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

             DB::beginTransaction();

                $customer = User::where('email','=',$request->email)->first();
                if(!isset($customer)) {
                    $customer = new User();
                    $customer->first_name = $request->first_name;
                    $customer->last_name = $request->last_name;
                    $customer->email = $request->email;
                    $customer = UsersController::registerCustomer($customer);
                }

                $company_user = CompanyUser::where('user_id','=',$customer->id)
                        ->where('cia_id',$request->cia_id)
                        ->first();

                if(!isset($company_user)) {
                    $company_user = new CompanyUser();
                    $company_user->cia_id = $request->cia_id;
                    $company_user->user_id = $customer->id;
                    $company_user->first_name = $customer->first_name;
                    $company_user->last_name = $customer->last_name;
                    $company_user->role = $request->role;
                    $company_user = CompanyController::registerCompanyUser($company_user);
                }

             DB::commit();

            $mailbody = new InvitationCompanyMailer($customer->first_name, $customer->last_name, '');

            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->isHTML(true);
            $mail->setFrom('sac@tulivery.com', config('app.name'));
            $mail->addAddress($customer->email, $customer->first_name." ".$customer->last_name);
            $mail->Username = config('mail.mailers.smtp.username');
            $mail->Password = config('mail.mailers.smtp.password');
            $mail->Host = config('mail.mailers.smtp.host');
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

            if($mail->send()) {

                $result->statusCode = Response::HTTP_OK;
                $result->statusMessage = 'Respuesta exitosa.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id' => 1,
                    'status' => 'Se envio la invitacion al correo indicado'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);

            } else {

                DB::rollBack();
                $result->statusCode = Response::HTTP_BAD_REQUEST;
                $result->statusMessage = 'No se pudo enviar el correo de invitacion.';
                $result->isSucess = false;
                $result->result = (object) [
                    'id' => 1,
                    'status' => 'No se pudo enviar el correo de invitacion'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');
                $result->error = $mail->ErrorInfo;

                return response()->json($result, $result->statusCode);
            }

        } catch (Exception $ex) {

            DB::rollBack();
            $result->statusCode = Response::HTTP_BAD_REQUEST;
            $result->statusMessage = 'ERROR.';
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


    public function ValidateRUC($ruc){

        $result = new Result();

        if (!isset($ruc)) {

            $result->statusCode = Response::HTTP_NOT_ACCEPTABLE;
            $result->statusMessage = 'El RUC debe tener 11 dígitos.';
            $result->isSucess = false;
            $result->result = (object) [
                'id' => 1,
                'status' => 'El RUC debe tener 11 dígitos.'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);
        }

        $token = "9e5e9820-79a0-49df-bd14-e9e732ad46f7-46eaac05-ba68-43ca-874d-d0ae99475333";
        $vari = [
            'token' => $token,
            'ruc' => $ruc
        ];
        $headers = [
            'Content-Type' => 'application/json'
        ];

        try {

            $http = Http::post('https://ruc.com.pe/api/v1/ruc', $vari);

        }catch(Exception $e){
            return response()->json([0,"Se presento un error\n".$e]);
        }

        if($http->getStatusCode() == 200){
            $respuesta = json_decode($http->getBody(),true);
            $legal_name = $respuesta['nombre_o_razon_social'];

            $result->statusCode = Response::HTTP_OK;
            $result->statusMessage = 'RUC Válido.';
            $result->isSucess = true;
            $result->result = (object) [
                'id' => 1,
                'status' => 'RUC Válido.',
                'razon_social' => $legal_name
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);

        } else if($http->getStatusCode() == 406){

            $result->statusCode = Response::HTTP_NOT_ACCEPTABLE;
            $result->statusMessage = 'El RUC debe tener 11 dígitos.';
            $result->isSucess = false;
            $result->result = (object) [
                'id' => 1,
                'status' => 'El RUC debe tener 11 dígitos.'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);

        }else if($http->getStatusCode() == 404){

            $result->statusCode = Response::HTTP_NOT_FOUND;
            $result->statusMessage = 'El RUC no existe.';
            $result->isSucess = false;
            $result->result = (object) [
                'id' => 1,
                'status' => 'El RUC no existe.'
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);
        }
    }

    /**
     * @OA\Post(
     *   tags={"Imagen"},
     *   path="/api/ui/upload",
     *   summary="Subir imagen codificada en base64",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"upload"},
     *       @OA\Property(property="upload", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Respuesta exitosa"),
     *   @OA\Response(response=404, description="No existe de imagen a subir"),
     *   @OA\Response(response=400, description="Error no controlado."),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function uploadImageBase64(Request $request)
    {
        $result = new Result();

        try {

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

            if(!isset($request->upload)) {
                $result->statusCode = Response::HTTP_NOT_FOUND;
                $result->statusMessage = 'No existe informacion de imagen.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id' => 1,
                    'status' => 'No existe informacion de imagen.'
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            $company_user = CompanyUser::where('user_id',$user->id)->where('role','own')->first();
            $company = Company::where('id',$company_user->cia_id)->first();

            $title = CompanyController::replaceCharacteres($company->name." ".$company->legal_number);
            $name = CompanyController::replaceCharacteres($user->first_name." ".$user->last_name);

            /*
            if(!file_exists(public_path('/tiendas/'.$title.'/'.$name.'/company'))){
                 mkdir(public_path('/tiendas/'.$title.'/'.$name.'/company'),0777,true);
            } */

            $date = date("Y_m_d_H_i_s");
            $imgPath = '/tiendas/'.$title.'/'.$name.'/company/'.$date.".webp";

            // Remover la parte de la cadena de texto que no necesitamos (data:image/png;base64,)
            // y usar base64_decode para obtener la información binaria de la imagen
            $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->upload));
            // Finalmente guarda la imágen en el directorio especificado y con la informacion dada

            /* file_put_contents($imgPath,$data); */

            Storage::disk('public_image')->put($imgPath, $data);

            $url = config('app.url').'images/tiendas/'.$title.'/'.$name.'/company/'.$date.'.webp';

            $result->statusCode = Response::HTTP_OK;
            $result->statusMessage = 'Respuesta exitosa.';
            $result->isSucess = true;
            $result->result = (object) [
                'url' => $url
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);

        } catch (Exception $ex) {

            $result->statusCode = Response::HTTP_BAD_REQUEST;
            $result->statusMessage = 'ERROR.';
            $result->isSucess = false;
            $result->result = $ex;
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');
            $result->error = $ex;

            return response()->json($result, $result->statusCode);
        }

    }

    static public function generatedAcronym($name, $length)
    {
        return strtoupper(substr(str_shuffle(str_replace(' ','',$name)),0,$length));
    }

    static function replaceCharacteres($company){
        $com = str_replace(" ","_",strtolower($company));
        $com = str_replace("","",strtolower($com));
        $com = str_replace("-","",strtolower($com));
        $com = str_replace("*","",strtolower($com));
        $com = str_replace("+","",strtolower($com));
        $com = str_replace(".","",strtolower($com));
        $com = str_replace(":","",strtolower($com));
        $com = str_replace(",","",strtolower($com));
        $com = str_replace(";","",strtolower($com));
        $com = str_replace("{","",strtolower($com));
        $com = str_replace("[","",strtolower($com));
        $com = str_replace("^","",strtolower($com));
        $com = str_replace("}","",strtolower($com));
        $com = str_replace("]","",strtolower($com));
        $com = str_replace("`","",strtolower($com));
        $com = str_replace("¨","",strtolower($com));
        $com = str_replace("´","",strtolower($com));
        $com = str_replace("~","",strtolower($com));
        $com = str_replace("°","",strtolower($com));
        $com = str_replace("|","",strtolower($com));
        $com = str_replace("¬","",strtolower($com));
        $com = str_replace("!","",strtolower($com));
        $com = str_replace('"',"",strtolower($com));
        $com = str_replace("#","",strtolower($com));
        $com = str_replace("$","",strtolower($com));
        $com = str_replace("%","",strtolower($com));
        $com = str_replace("&","",strtolower($com));
        $com = str_replace("(","",strtolower($com));
        $com = str_replace(")","",strtolower($com));
        $com = str_replace("=","",strtolower($com));
        $com = str_replace("'","",strtolower($com));
        $com = str_replace("?","",strtolower($com));
        $com = str_replace("¿","",strtolower($com));
        $com = str_replace("¡","",strtolower($com));
        $com = str_replace("á","a",strtolower($com));
        $com = str_replace("é","e",strtolower($com));
        $com = str_replace("í","i",strtolower($com));
        $com = str_replace("ó","o",strtolower($com));
        $com = str_replace("ú","u",strtolower($com));
        $com = str_replace("Á","A",strtolower($com));
        $com = str_replace("É","E",strtolower($com));
        $com = str_replace("Í","I",strtolower($com));
        $com = str_replace("Ó","O",strtolower($com));
        $com = str_replace("Ú","U",strtolower($com));
        return $com;
    }

    static public function registerCompany(Company $company) : Company
    {
            $company->id = Uuid::uuid6();
            $company->acronym = CompanyController::generatedAcronym($company->name, 10);
            $company->payment_accepted = isset($company->company_type) ? $company->company_type : "1|2";
            $company->company_type = isset($company->company_type) ? $company->company_type : 9;
            $company->activate_webstore = isset($company->activate_webstore) ? $company->activate_webstore : 0;
            $company->web_template = isset($company->web_template) ? $company->web_template : 3;
            $company->activate_stock = isset($company->activate_stock) ? $company->activate_stock : 0;
            $company->visible_zero_stock = isset($company->visible_zero_stock) ? $company->visible_zero_stock : 0;
            $company->activate_delivery = isset($company->activate_delivery) ? $company->activate_delivery : 0;
            $company->covid_permission = isset($company->covid_permission) ? $company->covid_permission : 1;
            $company->is_working = isset($company->is_working) ? $company->is_working : 1;
            $company->security = isset($company->security) ? $company->security : 0;
            $company->subscription = isset($company->subscription) ? $company->subscription : 1;
            $company->status = isset($company->status) ? $company->status : 1;
            $company->save();

            return $company;
    }

    static public function registerCompanyUser(CompanyUser $company_user) : CompanyUser
    {
            //$company_user->check_email = isset($company_user->check_email) ? $company_user->check_email : 1;
            $company_user->status = isset($company_user->status) ? $company_user->status : 1;
            $company_user->save();

            return $company_user;
    }
}
