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
     * 사이트 기본 설정 페이지
     */
    public function site(Request $request): Response
    {
        return $this->view('admin.settings.site');
    }

    /**
     * 사이트 기본 설정 저장
     */
    public function updateSite(Request $request): Response
    {
        return $this->view('admin.settings.site');
    }

    /**
     * 메일 설정 페이지
     */
    public function mail(Request $request): Response
    {
        return $this->view('admin.settings.mail');
    }

    /**
     * 메일 설정 저장
     */
    public function updateMail(Request $request): Response
    {
        return $this->view('admin.settings.mail');
    }

    /**
     * 언어 설정 페이지
     */
    public function language(Request $request): Response
    {
        return $this->view('admin.settings.language');
    }

    /**
     * 언어 설정 저장
     */
    public function updateLanguage(Request $request): Response
    {
        return $this->view('admin.settings.language');
    }

    /**
     * 번역 관리 페이지
     */
    public function translations(Request $request): Response
    {
        return $this->view('admin.settings.translations');
    }

    /**
     * 번역 저장
     */
    public function updateTranslations(Request $request): Response
    {
        return $this->view('admin.settings.translations');
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

    /**
     * 시스템 정보 - 업데이트 관리
     */
    public function systemUpdates(Request $request): Response
    {
        return $this->view('admin.settings.system.updates');
    }

    /**
     * 시스템 정보 - 업데이트 AJAX 처리
     */
    public function systemUpdatesAjax(Request $request): Response
    {
        header('Content-Type: application/json');

        $action = $request->input('action', '');

        try {
            $pdo = \RzxLib\Core\Database\Connection::getInstance()->getPdo();
            $updater = new \RzxLib\Core\Updater\Updater($pdo, BASE_PATH);

            switch ($action) {
                case 'check':
                    $result = $updater->checkForUpdates();
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                case 'perform':
                    $version = $request->input('version');
                    $result = $updater->performUpdate($version);
                    echo json_encode(['success' => $result['success'], 'data' => $result]);
                    break;

                case 'rollback':
                    $backupPath = $request->input('backup_path');
                    $result = $updater->rollback($backupPath);
                    echo json_encode(['success' => $result['success'], 'data' => $result]);
                    break;

                case 'backups':
                    $backups = $updater->getBackups();
                    echo json_encode(['success' => true, 'data' => $backups]);
                    break;

                case 'requirements':
                    $requirements = $updater->checkRequirements();
                    $allMet = !in_array(false, $requirements, true);
                    echo json_encode([
                        'success' => true,
                        'data' => ['requirements' => $requirements, 'all_met' => $allMet]
                    ]);
                    break;

                case 'version':
                    $versionInfo = $updater->getCurrentVersion();
                    unset($versionInfo['github']);
                    echo json_encode(['success' => true, 'data' => $versionInfo]);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }

        exit;
    }
}
