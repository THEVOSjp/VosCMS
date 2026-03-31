<?php

declare(strict_types=1);

namespace RzxLib\Http\Controllers\Admin;

use RzxLib\Core\Http\Controller;
use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use RzxLib\Core\Validation\Validator;
use RzxLib\Core\Validation\ValidationException;
use RzxLib\Reservation\Models\Service;
use RzxLib\Reservation\Models\ServiceCategory;

/**
 * ServiceController - 서비스 관리
 *
 * @package RzxLib\Http\Controllers\Admin
 */
class ServiceController extends Controller
{
    /**
     * 서비스 목록
     */
    public function index(Request $request): Response
    {
        $categoryId = $request->input('category_id');
        $active = $request->input('active');

        $query = Service::query()->orderBy('name');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($active !== null) {
            $query->where('is_active', $active === '1');
        }

        $services = $query->get();
        $categories = ServiceCategory::active();

        return $this->view('admin.services.index', [
            'services' => array_map([Service::class, 'fromArray'], $services),
            'categories' => $categories,
            'filters' => [
                'category_id' => $categoryId,
                'active' => $active,
            ],
        ]);
    }

    /**
     * 서비스 생성 폼
     */
    public function create(Request $request): Response
    {
        $categories = ServiceCategory::active();

        return $this->view('admin.services.create', [
            'categories' => $categories,
        ]);
    }

    /**
     * 서비스 생성 처리
     */
    public function store(Request $request): Response
    {
        try {
            $validated = $this->validateService($request->all());

            $service = Service::fromArray($validated);

            if (!$service->save()) {
                throw new \RuntimeException('서비스 저장에 실패했습니다.');
            }

            return $this->redirect('/admin/services/' . $service->id)
                ->withSuccess('서비스가 생성되었습니다.');

        } catch (ValidationException $e) {
            return $this->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\RuntimeException $e) {
            return $this->back()
                ->withError($e->getMessage())
                ->withInput();
        }
    }

    /**
     * 서비스 상세
     */
    public function show(Request $request, int $id): Response
    {
        $service = Service::find($id);

        if (!$service) {
            return $this->notFound('서비스를 찾을 수 없습니다.');
        }

        $category = $service->category_id ? ServiceCategory::find($service->category_id) : null;

        return $this->view('admin.services.show', [
            'service' => $service,
            'category' => $category,
        ]);
    }

    /**
     * 서비스 수정 폼
     */
    public function edit(Request $request, int $id): Response
    {
        $service = Service::find($id);

        if (!$service) {
            return $this->notFound('서비스를 찾을 수 없습니다.');
        }

        $categories = ServiceCategory::active();

        return $this->view('admin.services.edit', [
            'service' => $service,
            'categories' => $categories,
        ]);
    }

    /**
     * 서비스 수정 처리
     */
    public function update(Request $request, int $id): Response
    {
        $service = Service::find($id);

        if (!$service) {
            return $this->notFound('서비스를 찾을 수 없습니다.');
        }

        try {
            $validated = $this->validateService($request->all(), $id);

            // 업데이트
            $service->name = $validated['name'];
            $service->description = $validated['description'] ?? null;
            $service->short_description = $validated['short_description'] ?? null;
            $service->duration = (int) $validated['duration'];
            $service->price = (float) $validated['price'];
            $service->currency = $validated['currency'] ?? 'KRW';
            $service->category_id = isset($validated['category_id']) ? (int) $validated['category_id'] : null;
            $service->is_active = (bool) ($validated['is_active'] ?? true);
            $service->max_capacity = (int) ($validated['max_capacity'] ?? 1);
            $service->buffer_time = (int) ($validated['buffer_time'] ?? 0);
            $service->advance_booking_days = isset($validated['advance_booking_days']) ? (int) $validated['advance_booking_days'] : null;
            $service->min_notice_hours = isset($validated['min_notice_hours']) ? (int) $validated['min_notice_hours'] : null;

            if (!$service->save()) {
                throw new \RuntimeException('서비스 수정에 실패했습니다.');
            }

            return $this->redirect('/admin/services/' . $id)
                ->withSuccess('서비스가 수정되었습니다.');

        } catch (ValidationException $e) {
            return $this->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\RuntimeException $e) {
            return $this->back()
                ->withError($e->getMessage())
                ->withInput();
        }
    }

    /**
     * 서비스 삭제
     */
    public function destroy(Request $request, int $id): Response
    {
        $service = Service::find($id);

        if (!$service) {
            if ($request->wantsJson()) {
                return $this->error('서비스를 찾을 수 없습니다.', 404);
            }
            return $this->notFound('서비스를 찾을 수 없습니다.');
        }

        // 예약이 있는지 확인
        $hasReservations = \RzxLib\Reservation\Models\Reservation::query()
            ->where('service_id', $id)
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($hasReservations) {
            if ($request->wantsJson()) {
                return $this->error('예약이 있는 서비스는 삭제할 수 없습니다. 비활성화를 고려해주세요.');
            }
            return $this->back()->withError('예약이 있는 서비스는 삭제할 수 없습니다.');
        }

        if (!$service->delete()) {
            if ($request->wantsJson()) {
                return $this->error('서비스 삭제에 실패했습니다.');
            }
            return $this->back()->withError('서비스 삭제에 실패했습니다.');
        }

        if ($request->wantsJson()) {
            return $this->success(null, '서비스가 삭제되었습니다.');
        }

        return $this->redirect('/admin/services')
            ->withSuccess('서비스가 삭제되었습니다.');
    }

    /**
     * 서비스 활성/비활성 토글
     */
    public function toggleActive(Request $request, int $id): Response
    {
        $service = Service::find($id);

        if (!$service) {
            return $this->error('서비스를 찾을 수 없습니다.', 404);
        }

        $service->is_active = !$service->is_active;

        if (!$service->save()) {
            return $this->error('상태 변경에 실패했습니다.');
        }

        return $this->success([
            'is_active' => $service->is_active,
        ], $service->is_active ? '서비스가 활성화되었습니다.' : '서비스가 비활성화되었습니다.');
    }

    /**
     * 서비스 유효성 검사
     */
    protected function validateService(array $data, ?int $excludeId = null): array
    {
        $rules = [
            'name' => 'required|string|max:200',
            'description' => 'string',
            'short_description' => 'string|max:500',
            'duration' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency' => 'string|max:3',
            'category_id' => 'integer',
            'is_active' => 'boolean',
            'max_capacity' => 'integer|min:1',
            'buffer_time' => 'integer|min:0',
            'advance_booking_days' => 'integer|min:1',
            'min_notice_hours' => 'integer|min:0',
        ];

        $messages = [
            'name.required' => '서비스명을 입력해주세요.',
            'duration.required' => '소요 시간을 입력해주세요.',
            'price.required' => '가격을 입력해주세요.',
        ];

        $validator = new Validator($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
