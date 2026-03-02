<?php

declare(strict_types=1);

namespace RzxLib\Http\Controllers\Admin;

use RzxLib\Core\Http\Controller;
use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;

/**
 * SettingsController - 관리자 설정
 *
 * @package RzxLib\Http\Controllers\Admin
 */
class SettingsController extends Controller
{
    /**
     * 일반 설정 페이지
     */
    public function general(Request $request): Response
    {
        return $this->view('admin.settings.general');
    }

    /**
     * 일반 설정 저장
     */
    public function updateGeneral(Request $request): Response
    {
        // 뷰 파일에서 직접 처리하므로 리다이렉트
        return $this->view('admin.settings.general');
    }

    /**
     * SEO 설정 페이지
     */
    public function seo(Request $request): Response
    {
        return $this->view('admin.settings.seo');
    }

    /**
     * SEO 설정 저장
     */
    public function updateSeo(Request $request): Response
    {
        return $this->view('admin.settings.seo');
    }

    /**
     * PWA 설정 페이지
     */
    public function pwa(Request $request): Response
    {
        return $this->view('admin.settings.pwa');
    }

    /**
     * PWA 설정 저장
     */
    public function updatePwa(Request $request): Response
    {
        return $this->view('admin.settings.pwa');
    }

    /**
     * 시스템 정보 페이지 (리다이렉트)
     */
    public function system(Request $request): Response
    {
        return $this->view('admin.settings.system');
    }

    /**
     * 시스템 정보 - 정보관리
     */
    public function systemInfo(Request $request): Response
    {
        return $this->view('admin.settings.system.info');
    }

    /**
     * 시스템 정보 - 캐시관리
     */
    public function systemCache(Request $request): Response
    {
        return $this->view('admin.settings.system.cache');
    }

    /**
     * 시스템 정보 - 캐시관리 액션
     */
    public function systemCacheAction(Request $request): Response
    {
        return $this->view('admin.settings.system.cache');
    }

    /**
     * 시스템 정보 - 모드관리
     */
    public function systemMode(Request $request): Response
    {
        return $this->view('admin.settings.system.mode');
    }

    /**
     * 시스템 정보 - 모드관리 액션
     */
    public function systemModeAction(Request $request): Response
    {
        return $this->view('admin.settings.system.mode');
    }

    /**
     * 시스템 정보 - 로그관리
     */
    public function systemLogs(Request $request): Response
    {
        return $this->view('admin.settings.system.logs');
    }

    /**
     * 시스템 정보 - 로그관리 액션
     */
    public function systemLogsAction(Request $request): Response
    {
        return $this->view('admin.settings.system.logs');
    }
}
