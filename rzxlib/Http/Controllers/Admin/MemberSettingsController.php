<?php

declare(strict_types=1);

namespace RzxLib\Http\Controllers\Admin;

use RzxLib\Core\Http\Controller;
use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;

/**
 * MemberSettingsController - 회원 설정 관리
 *
 * @package RzxLib\Http\Controllers\Admin
 */
class MemberSettingsController extends Controller
{
    /**
     * 기본 설정 페이지
     */
    public function general(Request $request): Response
    {
        return $this->view('admin.members.settings.general');
    }

    /**
     * 기본 설정 저장
     */
    public function updateGeneral(Request $request): Response
    {
        return $this->view('admin.members.settings.general');
    }

    /**
     * 기능 설정 페이지
     */
    public function features(Request $request): Response
    {
        return $this->view('admin.members.settings.features');
    }

    /**
     * 기능 설정 저장
     */
    public function updateFeatures(Request $request): Response
    {
        return $this->view('admin.members.settings.features');
    }

    /**
     * 약관 설정 페이지
     */
    public function terms(Request $request): Response
    {
        return $this->view('admin.members.settings.terms');
    }

    /**
     * 약관 설정 저장
     */
    public function updateTerms(Request $request): Response
    {
        return $this->view('admin.members.settings.terms');
    }

    /**
     * 회원가입 설정 페이지
     */
    public function register(Request $request): Response
    {
        return $this->view('admin.members.settings.register');
    }

    /**
     * 회원가입 설정 저장
     */
    public function updateRegister(Request $request): Response
    {
        return $this->view('admin.members.settings.register');
    }

    /**
     * 로그인 설정 페이지
     */
    public function login(Request $request): Response
    {
        return $this->view('admin.members.settings.login');
    }

    /**
     * 로그인 설정 저장
     */
    public function updateLogin(Request $request): Response
    {
        return $this->view('admin.members.settings.login');
    }

    /**
     * 디자인 설정 페이지
     */
    public function design(Request $request): Response
    {
        return $this->view('admin.members.settings.design');
    }

    /**
     * 디자인 설정 저장
     */
    public function updateDesign(Request $request): Response
    {
        return $this->view('admin.members.settings.design');
    }
}
