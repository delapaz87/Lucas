<?php

namespace App\Http\Middleware;

use App\Object\Result;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $result = new Result();
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {

                $result->statusCode = Response::HTTP_UNAUTHORIZED;
                $result->statusMessage = 'Token is Invalid';
                $result->result = (object) [
                    'id' => 1,
                    'status' => $e->getMessage()
                ];
                $result->isSucess = false;
                $result->id = 1;
                $result->size = 1;
                $result->date = date('Y-m-d');
                $result->error = $e;

                return response()->json($result, $result->statusCode);

            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {

                $result->statusCode = Response::HTTP_UNAUTHORIZED;
                $result->statusMessage = 'Token is Expired';
                $result->result = (object) [
                    'id' => 1,
                    'status' => $e->getMessage()
                ];
                $result->isSucess = false;
                $result->id = 1;
                $result->size = 1;
                $result->error = $e;

                return response()->json($result, $result->statusCode);

            } else {
                $result->statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                $result->statusMessage = 'Token is Invalid';
                $result->result = (object) [
                    'id' => 1,
                    'status' => $e->getMessage()
                ];
                $result->isSucess = false;
                $result->id = 1;
                $result->size = 1;
                $result->error = $e;

                return response()->json($result, $result->statusCode);
            }
        }
        return $next($request);
    }
}
