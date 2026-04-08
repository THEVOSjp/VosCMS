<?php

declare(strict_types=1);

namespace RzxLib\Http\Controllers\Api;

use RzxLib\Core\Http\Controller;
use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use RzxLib\Core\Updater\Updater;
use RzxLib\Core\Database\Connection;

/**
 * UpdateApiController - 업데이트 API
 * 서버 사이드에서 GitHub API 호출 (토큰 보안)
 */
class UpdateApiController extends Controller
{
    private ?Updater $updater = null;

    /**
     * Updater 인스턴스 가져오기
     */
    private function getUpdater(): Updater
    {
        if ($this->updater === null) {
            $pdo = Connection::getInstance()->getPdo();
            $this->updater = new Updater($pdo, BASE_PATH);
        }
        return $this->updater;
    }

    /**
     * 업데이트 확인 API
     */
    public function check(Request $request): Response
    {
        try {
            $updater = $this->getUpdater();
            $result = $updater->checkForUpdates();

            return $this->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 업데이트 실행 API
     */
    public function perform(Request $request): Response
    {
        try {
            // CSRF 검증
            if (!$this->verifyCsrfToken($request)) {
                return $this->json([
                    'success' => false,
                    'error' => 'CSRF 토큰이 유효하지 않습니다.',
                ], 403);
            }

            $updater = $this->getUpdater();
            $targetVersion = $request->input('version');

            $result = $updater->performUpdate($targetVersion);

            return $this->json([
                'success' => $result['success'],
                'data' => $result,
            ], $result['success'] ? 200 : 500);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 롤백 API
     */
    public function rollback(Request $request): Response
    {
        try {
            // CSRF 검증
            if (!$this->verifyCsrfToken($request)) {
                return $this->json([
                    'success' => false,
                    'error' => 'CSRF 토큰이 유효하지 않습니다.',
                ], 403);
            }

            $updater = $this->getUpdater();
            $backupPath = $request->input('backup_path');

            $result = $updater->rollback($backupPath);

            return $this->json([
                'success' => $result['success'],
                'data' => $result,
            ], $result['success'] ? 200 : 500);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 백업 목록 API
     */
    public function backups(Request $request): Response
    {
        try {
            $updater = $this->getUpdater();
            $backups = $updater->getBackups();

            return $this->json([
                'success' => true,
                'data' => $backups,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 시스템 요구사항 API
     */
    public function requirements(Request $request): Response
    {
        try {
            $updater = $this->getUpdater();
            $requirements = $updater->checkRequirements();

            $allMet = !in_array(false, $requirements, true);

            return $this->json([
                'success' => true,
                'data' => [
                    'requirements' => $requirements,
                    'all_met' => $allMet,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 현재 버전 정보 API
     */
    public function version(Request $request): Response
    {
        try {
            $updater = $this->getUpdater();
            $versionInfo = $updater->getCurrentVersion();

            // GitHub 정보는 내부용이므로 클라이언트에 노출하지 않음
            unset($versionInfo['github']);

            return $this->json([
                'success' => true,
                'data' => $versionInfo,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * CSRF 토큰 검증
     */
    private function verifyCsrfToken(Request $request): bool
    {
        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');

        if (empty($token)) {
            return false;
        }

        $sessionToken = $_SESSION['csrf_token'] ?? null;

        return $sessionToken && hash_equals($sessionToken, $token);
    }
}
