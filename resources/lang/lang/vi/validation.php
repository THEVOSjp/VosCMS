<?php

/**
 * Validation messages - Vietnamese
 */

return [
    // Basic messages
    'accepted' => ':attribute phải được chấp nhận.',
    'required' => 'Trường :attribute là bắt buộc.',
    'email' => 'Vui lòng nhập địa chỉ email hợp lệ.',
    'min' => 'Trường :attribute phải có ít nhất :min ký tự.',
    'max' => 'Trường :attribute không được vượt quá :max ký tự.',
    'numeric' => 'Trường :attribute phải là số.',
    'integer' => 'Trường :attribute phải là số nguyên.',
    'string' => 'Trường :attribute phải là chuỗi.',
    'boolean' => 'Trường :attribute phải là đúng hoặc sai.',
    'array' => 'Trường :attribute phải là mảng.',
    'date' => 'Trường :attribute không phải là ngày hợp lệ.',
    'same' => 'Xác nhận :attribute không khớp.',
    'confirmed' => 'Xác nhận :attribute không khớp.',
    'unique' => ':attribute đã được sử dụng.',
    'exists' => ':attribute đã chọn không hợp lệ.',
    'in' => ':attribute đã chọn không hợp lệ.',
    'not_in' => ':attribute đã chọn không hợp lệ.',
    'regex' => 'Định dạng :attribute không hợp lệ.',
    'url' => 'Định dạng :attribute không hợp lệ.',
    'alpha' => 'Trường :attribute chỉ được chứa chữ cái.',
    'alpha_num' => 'Trường :attribute chỉ được chứa chữ cái và số.',
    'alpha_dash' => 'Trường :attribute chỉ được chứa chữ cái, số, dấu gạch ngang và dấu gạch dưới.',

    // Size
    'size' => [
        'numeric' => 'Trường :attribute phải bằng :size.',
        'string' => 'Trường :attribute phải có :size ký tự.',
        'array' => 'Trường :attribute phải chứa :size phần tử.',
    ],

    'between' => [
        'numeric' => 'Trường :attribute phải nằm trong khoảng :min và :max.',
        'string' => 'Trường :attribute phải có từ :min đến :max ký tự.',
        'array' => 'Trường :attribute phải có từ :min đến :max phần tử.',
    ],

    // File
    'file' => 'Trường :attribute phải là tệp.',
    'image' => 'Trường :attribute phải là hình ảnh.',
    'mimes' => 'Trường :attribute phải là tệp loại: :values.',
    'max_file' => 'Trường :attribute không được lớn hơn :max kilobytes.',

    // Password
    'password' => [
        'lowercase' => 'Mật khẩu phải chứa ít nhất một chữ thường.',
        'uppercase' => 'Mật khẩu phải chứa ít nhất một chữ hoa.',
        'number' => 'Mật khẩu phải chứa ít nhất một số.',
        'special' => 'Mật khẩu phải chứa ít nhất một ký tự đặc biệt.',
    ],

    // Date
    'date_format' => 'Trường :attribute không khớp với định dạng :format.',
    'after' => 'Trường :attribute phải là ngày sau :date.',
    'before' => 'Trường :attribute phải là ngày trước :date.',
    'after_or_equal' => 'Trường :attribute phải là ngày sau hoặc bằng :date.',
    'before_or_equal' => 'Trường :attribute phải là ngày trước hoặc bằng :date.',

    // Attribute names
    'attributes' => [
        'name' => 'họ tên',
        'email' => 'email',
        'password' => 'mật khẩu',
        'password_confirmation' => 'xác nhận mật khẩu',
        'phone' => 'số điện thoại',
        'customer_name' => 'họ tên',
        'customer_email' => 'email',
        'customer_phone' => 'số điện thoại',
        'booking_date' => 'ngày đặt chỗ',
        'start_time' => 'giờ bắt đầu',
        'guests' => 'số khách',
        'service_id' => 'dịch vụ',
        'category_id' => 'danh mục',
        'duration' => 'thời gian',
        'price' => 'giá',
        'description' => 'mô tả',
        'notes' => 'ghi chú',
        'reason' => 'lý do',
    ],
];
