<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 테스트용 컨트롤러
 * - 서버 환경 정보(phpinfo)를 표시합니다.
 */
class Test extends CI_Controller {

	/**
	 * http://localhost/index.php/test 로 접속 시 실행됩니다.
	 */
	public function index()
	{
		// 'phpinfo_view' 라는 이름의 뷰 파일을 로드합니다.
		$this->load->view('phpinfo_view');
	}
} 
