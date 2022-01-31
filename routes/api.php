<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\StoresController;
use App\Http\Controllers\TagsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\UsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//Authenticaction
Route::get('/test', [ApiController::class,'test']); // Token de Authenticacion
Route::post('/login', [ApiController::class,'login']); // Token de Authenticacion
Route::post('/registercompany', [ApiController::class,'registerCompany']); // Registrar Store
Route::post('/forgotpassword',[ApiController::class,'forgotPassword']); // Recuperar Contraseña
Route::post('/changepasswordwithforgotpassword',[ApiController::class,'changePasswordWithforgotPassword']); // Cambiar contraseña por Email

//Extras
Route::get('/getuserbyemail/{email}', [ApiController::class,'getUserByEmail']); // Get User By Email
Route::get('/getusercompanybyemail/{email}', [ApiController::class,'getUserCompanyByEmail']); // Get User By Email
Route::post('/generatecodeformail',[ApiController::class,'generateCodeforMail']); // Generar codigo OTP
Route::post('/validatecode',[ApiController::class,'validateCode']); // Validar Codigo OTP

Route::get('/validator/ruc/{ruc}', [CompanyController::class,'ValidateRUC']); // Validador RUC


// PRODUCT
Route::get('/product/getproducts', [ProductController::class,'GetProduct']);
Route::put('/product/editproduct', [ProductController::class,'EditProduct']);
Route::post('/product/newproduct', [ProductController::class,'NewProduct']);
Route::get('/product/getproductbyid/{id}', [ProductController::class,'GetProductById']);

// CATEGORY
Route::get('/category/getcategories', [CategoryController::class,'GetGategories']);
Route::get('/category/getcategorybyid/{id}', [CategoryController::class,'GetGategoryById']);
Route::post('/category/newcategory', [CategoryController::class,'NewCategory']);
Route::put('/category/editcategory', [CategoryController::class,'EditCategory']);

Route::group(['middleware' => ['jwt.verify']], function() {

    /*AÑADE AQUI LAS RUTAS QUE QUIERAS PROTEGER CON JWT*/
    Route::post('/changepassword',[UsersController::class,'changePassword']); //Cambiar Contraseña
    Route::post('/recoverpasswordwithemail',[ApiController::class,'recoverPasswordWithEmail']); // Recuperar contraseña por Email

    //Company
    Route::get('/commerce/inscription', [CompanyController::class,'getComanyInscripcion']); // Comercio Inscripcion
    Route::get('/commerce/company', [CompanyController::class,'getComanyByUser']); // Comercio
    Route::put('/commerce/company', [CompanyController::class,'updateCompany']); // Update Comercio
    Route::put('/commerce/deliveryrate', [CompanyController::class,'updateDeliveryByUser']); // Update Delivery

    //Store
    Route::get('/stores', [StoresController::class,'getStores']); // Stores
    Route::put('/stores', [StoresController::class,'updateStores']);  // Stores update
    Route::post('/stores', [StoresController::class,'newStores']);  // New Stores Id
    Route::put('/stores/hours', [StoresController::class,'updateHours']);  // Stores Hours Update
    Route::post('/stores/assign', [StoresController::class,'assignStoreByUserId']);  // Assign Store a User
    Route::post('/stores/coverage/{cia_id}', [StoresController::class,'getStoreCoverage']);  // Assign Store a User

    //User Company
    Route::get('/company/users', [CompanyController::class,'getUsersByCompany']); // Users Company By Store
    Route::post('/company/users', [CompanyController::class,'postUsersByCompany']); // Invite User a Company

    // Clientes
    Route::get('/clients/company/{company_id}/id/{client_id}', [ClientsController::class,'getClientCompanyById']); // Mostrar Clientes
    Route::get('/clients/company/{company_id}', [ClientsController::class,'getClientByCompany']); // Mostrar Clientes
    Route::put('/clients/company/{company_id}', [ClientsController::class,'putClientsByCompany']); // Actualizar Clientes
    Route::post('/clients/company/{company_id}', [ClientsController::class,'postClientsByCompany']); // Nuevo Cliente

    //Upload image
    Route::post('/ui/upload', [CompanyController::class,'uploadImageBase64']);  // Upload Image

    //Users
    Route::get('/users', [UsersController::class,'getUsers']); // Users Company By Store

    //UI
    Route::get('/ui/typedocument', [ApiController::class,'gettypedocument']); // Consultar los tipos de documentos
    Route::get('/ui/typepaymentaccepted', [ApiController::class,'gettypepaymentaccepted']); // Metodos de Pagos Aceptados
    Route::get('/ui/typeaddress', [ApiController::class,'gettypeaddress']); // Tipos de Direcccion

    //Tag
    Route::get('/ui/tag', [TagsController::class,'getTag']); // Etiquetas
    Route::post('/ui/tag', [TagsController::class,'saveTag']); // Save Etiquetas

    Route::get('/ui/cities',[ApiController::class,'getCities']);
    Route::get('/ui/provinces/{city_id}',[ApiController::class,'getProvinces']);
    Route::get('/ui/districts/{province_id}',[ApiController::class,'getDistricts']);

});

