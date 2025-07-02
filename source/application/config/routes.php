<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'Dashboard';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// 의약품 검색 라우트
$route['medical_products'] = 'medical_products/index';
$route['medical_products/search'] = 'medical_products/search';
$route['medical_products/search_ajax'] = 'medical_products/search_ajax';

// 의약품 업로드 라우트 추가
$route['medical_products/upload'] = 'medical_products/upload';
$route['medical_products/process_upload'] = 'medical_products/process_upload';

// 병원 검색 라우트
$route['hospital_search'] = 'hospital_search/index';
$route['hospital_search/search'] = 'hospital_search/search';
$route['hospital_search/detail/(:num)'] = 'hospital_search/detail/$1';
$route['hospital_search/region/(:any)'] = 'hospital_search/region/$1';
$route['hospital_search/region/(:any)/(:any)'] = 'hospital_search/region/$1/$2';
$route['hospital_search/stats'] = 'hospital_search/stats';
$route['hospital_search/autocomplete'] = 'hospital_search/autocomplete';
