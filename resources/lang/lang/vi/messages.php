<?php
/**
 * Vietnamese Language File
 */

return [
    // Common
    'app_name' => 'RezlyX',
    'welcome' => 'Chào mừng',
    'home' => 'Trang chủ',
    'back' => 'Quay lại',
    'next' => 'Tiếp theo',
    'cancel' => 'Hủy',
    'confirm' => 'Xác nhận',
    'save' => 'Lưu',
    'delete' => 'Xóa',
    'edit' => 'Chỉnh sửa',
    'search' => 'Tìm kiếm',
    'loading' => 'Đang tải...',
    'no_data' => 'Không có dữ liệu.',
    'error' => 'Đã xảy ra lỗi.',
    'success' => 'Đã xử lý thành công.',

    // Auth
    'auth' => [
        'login' => 'Đăng nhập',
        'logout' => 'Đăng xuất',
        'register' => 'Đăng ký',
        'email' => 'Email',
        'password' => 'Mật khẩu',
        'password_confirm' => 'Xác nhận mật khẩu',
        'remember_me' => 'Ghi nhớ đăng nhập',
        'forgot_password' => 'Quên mật khẩu?',
        'reset_password' => 'Đặt lại mật khẩu',
        'invalid_credentials' => 'Email hoặc mật khẩu không đúng.',
        'account_inactive' => 'Tài khoản này đã bị vô hiệu hóa.',
    ],

    // Reservation
    'reservation' => [
        'title' => 'Đặt chỗ',
        'new' => 'Đặt chỗ mới',
        'my_reservations' => 'Đặt chỗ của tôi',
        'select_service' => 'Chọn dịch vụ',
        'select_date' => 'Chọn ngày',
        'select_time' => 'Chọn giờ',
        'customer_info' => 'Thông tin của bạn',
        'payment' => 'Thanh toán',
        'confirmation' => 'Xác nhận',
        'status' => [
            'pending' => 'Chờ xử lý',
            'confirmed' => 'Đã xác nhận',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            'no_show' => 'Không đến',
        ],
    ],

    // Services
    'service' => [
        'title' => 'Dịch vụ',
        'category' => 'Danh mục',
        'price' => 'Giá',
        'duration' => 'Thời gian',
        'description' => 'Mô tả',
        'options' => 'Tùy chọn',
    ],

    // Member
    'member' => [
        'profile' => 'Hồ sơ của tôi',
        'points' => 'Điểm',
        'grade' => 'Cấp độ thành viên',
        'reservations' => 'Lịch sử đặt chỗ',
        'payments' => 'Lịch sử thanh toán',
        'settings' => 'Cài đặt',
    ],

    // Payment
    'payment' => [
        'title' => 'Thanh toán',
        'amount' => 'Số tiền',
        'method' => 'Phương thức thanh toán',
        'card' => 'Thẻ tín dụng',
        'bank_transfer' => 'Chuyển khoản ngân hàng',
        'virtual_account' => 'Tài khoản ảo',
        'points' => 'Điểm',
        'use_points' => 'Sử dụng điểm',
        'available_points' => 'Điểm khả dụng',
        'complete' => 'Thanh toán hoàn tất',
        'failed' => 'Thanh toán thất bại',
    ],

    // Time
    'time' => [
        'today' => 'Hôm nay',
        'tomorrow' => 'Ngày mai',
        'minutes' => 'phút',
        'hours' => 'giờ',
        'days' => 'ngày',
    ],

    // Validation
    'validation' => [
        'required' => 'Trường :attribute là bắt buộc.',
        'email' => 'Vui lòng nhập địa chỉ email hợp lệ.',
        'min' => 'Trường :attribute phải có ít nhất :min ký tự.',
        'max' => 'Trường :attribute không được vượt quá :max ký tự.',
        'confirmed' => 'Xác nhận :attribute không khớp.',
    ],
];
