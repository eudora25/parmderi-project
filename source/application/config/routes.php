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
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// 병원 검색 관련 라우팅
$route['hospital'] = 'Hospital_search';
$route['hospital/mobile'] = 'Hospital_search/mobile';
$route['hospital/search'] = 'Hospital_search/search';
$route['hospital/detail/(:num)'] = 'Hospital_search/detail/$1';
$route['hospital/region/(:any)'] = 'Hospital_search/region/$1';
$route['hospital/region/(:any)/(:any)'] = 'Hospital_search/region/$1/$2';
$route['hospital/stats'] = 'Hospital_search/stats';
$route['hospital/autocomplete'] = 'Hospital_search/autocomplete';
$route['hospital/api'] = 'Hospital_search/api';

// 엑셀 업로드 관련 라우팅
$route['excel'] = 'Excel_upload';
$route['excel/upload'] = 'Excel_upload/upload';
$route['excel/preview'] = 'Excel_upload/preview';
$route['excel/stats'] = 'Excel_upload/stats';
$route['excel/failed_records/(:num)'] = 'Excel_upload/failed_records/$1';
$route['excel/failed_records'] = 'Excel_upload/failed_records';
$route['excel/reprocess/(:num)'] = 'Excel_upload/reprocess/$1';

// 질문 유형 관리 라우팅
$route['question'] = 'Question_manager';
$route['question/test'] = 'Question_manager/test';
$route['question/stats'] = 'Question_manager/stats';
$route['question/logs'] = 'Question_manager/logs';
$route['question/samples'] = 'Question_manager/samples';
$route['question/analyze'] = 'Question_manager/analyze';
$route['question/api_stats'] = 'Question_manager/api_stats';
$route['question/detail/(:num)'] = 'Question_manager/detail/$1';
$route['question/add'] = 'Question_manager/add';
$route['question/edit/(:num)'] = 'Question_manager/edit/$1';
