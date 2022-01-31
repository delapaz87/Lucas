<?php

namespace App\Http\Controllers;

use App\Models\CompanyUser;
use App\Models\User;
use App\Object\Result;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class UsersController extends Controller {


    /**
     * @OA\Get(
     *   tags={"Usuarios"},
     *   path="/api/users",
     *   summary="Mostrar usuarios",
     *   @OA\Response(response=200, description="Respuesta exitosa"),
     *   @OA\Response(response=400, description="Error no controlado."),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function getUsers()
    {

        $result = new Result();

        try {

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

            $users = User::select('id', 'first_name', 'last_name', 'sur_name',
            'phone', 'email', 'birthdate', 'icon', 'wish_offers', 'wish_notify',
            'source_app', 'created_at', 'updated_at')->paginate();

            $result->statusCode = Response::HTTP_OK;
            $result->statusMessage = 'Respuesta exitosa.';
            $result->isSucess = true;
            $result->result = $users;
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
     *   tags={"Usuarios"},
     *   path="/api/changepassword",
     *   summary="Cambiar la contraseña",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"user_id","email","password"},
     *       @OA\Property(property="user_id", type="integer"),
     *       @OA\Property(property="email", type="string"),
     *       @OA\Property(property="password", type="string"),
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Se actualizó la contraseña",
     *     @OA\JsonContent(
     *       type="object",
     *       required={"statusCode", "statusMessage", "result", "isSucess", "id", "size", "date", "error"},
     *       @OA\Property(property="statusCode", type="integer"),
     *       @OA\Property(property="statusMessage", type="string"),
     *       @OA\Property(property="result", type="object", ref="#/components/schemas/ErrorStatus"),
     *       @OA\Property(property="isSucess", type="boolean"),
     *       @OA\Property(property="id", type="integer"),
     *       @OA\Property(property="size", type="integer"),
     *       @OA\Property(property="date", type="string"),
     *       @OA\Property(property="error", type="object"),
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="No se pudo actualizar la contraseña"
     *   ),
     *   @OA\Response(response=401, description="Error no controlado"),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function changePassword(Request $request)
    {
        $result = new Result();

        try {

            if(empty($request->user_id)) {
                $result->statusCode = Response::HTTP_BAD_REQUEST;
                $result->statusMessage = 'La propiedad "id" no puedes estar vacia.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id'        => 1,
                    'status'    => 'La propiedad "id" no puedes estar vacia.',
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            if(empty($request->email)) {
                $result->statusCode = Response::HTTP_BAD_REQUEST;
                $result->statusMessage = 'La propiedad "email" no puedes estar vacia.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id'        => 1,
                    'status'    => 'La propiedad "email" no puedes estar vacia.',
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            if(empty($request->password)) {
                $result->statusCode = Response::HTTP_BAD_REQUEST;
                $result->statusMessage = 'La propiedad "password" no puedes estar vacia.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id'        => 1,
                    'status'    => 'La propiedad "password" no puedes estar vacia.',
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            $user = User::where('id', $request->user_id)
                ->where('email',$request->email)
                ->whereIn('status', [1,3])->first();

            if(!$user) {

                $result->statusCode = Response::HTTP_BAD_REQUEST;
                $result->statusMessage = 'No se encontro el usuario.';
                $result->isSucess = true;
                $result->result = (object) [
                    'id'        => 1,
                    'status'    => 'No se encontro el usuario.',
                ];
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');

                return response()->json($result, $result->statusCode);
            }

            User::where('id', $request->user_id)->update(['password' => Hash::make($request->password)]);

            $result->statusCode = Response::HTTP_OK;
            $result->statusMessage = 'Se actualizo la contraseña con exito.';
            $result->isSucess = true;
            $result->result = (object) [
                'id'        => 1,
                'status'    => 'Se actualizo la contraseña con exito.',
            ];
            $result->id = 1;
            $result->size = 1;
            $result->date = date('Y-m-d');

            return response()->json($result, $result->statusCode);

        } catch (Exception $ex) {

            $result->statusCode = Response::HTTP_NOT_FOUND;
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

    static public function registerCustomer(User $customer) : User
    {
            $customer->icon = isset($customer->icon) ? $customer->icon : "users/default.png";
            $customer->wish_offers = isset($customer->wish_offers) ? $customer->wish_offers : 1;
            $customer->wish_notify = isset($customer->wish_notify) ? $customer->wish_notify : 1;
            $customer->check_phone = isset($customer->check_phone) ? $customer->check_phone : 0;
            $customer->check_email = isset($customer->check_email) ? $customer->check_email : 1;
            $customer->check_payonline = isset($customer->check_payonline) ? $customer->check_payonline : 0;
            $customer->check_payafter = isset($customer->check_payafter) ? $customer->check_payafter : 0;
            $customer->user_guest = isset($customer->user_guest) ? $customer->user_guest : 0;
            $customer->user_black = isset($customer->user_black) ? $customer->user_black : 0;
            $customer->super_user = isset($customer->super_user) ? $customer->super_user : 0;
            $customer->source_app = isset($customer->source_app) ? $customer->source_app : "WEB";
            $customer->status =  isset($customer->status) ? $customer->status : 1;
            $customer->save();

            return $customer;
    }
}
