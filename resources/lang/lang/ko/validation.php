<?php

/**
 * 유효성 검사 메시지 - 한국어
 */

return [
    // 기본 메시지
    'accepted' => ':attribute에 동의해야 합니다.',
    'required' => ':attribute 필드는 필수입니다.',
    'email' => '올바른 이메일 주소를 입력해주세요.',
    'min' => ':attribute은(는) 최소 :min자 이상이어야 합니다.',
    'max' => ':attribute은(는) 최대 :max자를 초과할 수 없습니다.',
    'numeric' => ':attribute은(는) 숫자여야 합니다.',
    'integer' => ':attribute은(는) 정수여야 합니다.',
    'string' => ':attribute은(는) 문자열이어야 합니다.',
    'boolean' => ':attribute은(는) true 또는 false여야 합니다.',
    'array' => ':attribute은(는) 배열이어야 합니다.',
    'date' => '올바른 날짜 형식이 아닙니다.',
    'same' => ':attribute 확인이 일치하지 않습니다.',
    'confirmed' => ':attribute 확인이 일치하지 않습니다.',
    'unique' => '이미 사용 중인 :attribute입니다.',
    'exists' => '선택한 :attribute이(가) 유효하지 않습니다.',
    'in' => '선택한 :attribute이(가) 유효하지 않습니다.',
    'not_in' => '선택한 :attribute이(가) 유효하지 않습니다.',
    'regex' => ':attribute 형식이 올바르지 않습니다.',
    'url' => '올바른 URL 형식이 아닙니다.',
    'alpha' => ':attribute은(는) 문자만 포함할 수 있습니다.',
    'alpha_num' => ':attribute은(는) 문자와 숫자만 포함할 수 있습니다.',
    'alpha_dash' => ':attribute은(는) 문자, 숫자, 대시, 밑줄만 포함할 수 있습니다.',

    // 크기
    'size' => [
        'numeric' => ':attribute은(는) :size이어야 합니다.',
        'string' => ':attribute은(는) :size자여야 합니다.',
        'array' => ':attribute은(는) :size개의 항목을 포함해야 합니다.',
    ],

    'between' => [
        'numeric' => ':attribute은(는) :min에서 :max 사이여야 합니다.',
        'string' => ':attribute은(는) :min자에서 :max자 사이여야 합니다.',
        'array' => ':attribute은(는) :min개에서 :max개 사이의 항목을 포함해야 합니다.',
    ],

    // 파일
    'file' => ':attribute은(는) 파일이어야 합니다.',
    'image' => ':attribute은(는) 이미지 파일이어야 합니다.',
    'mimes' => ':attribute은(는) :values 형식이어야 합니다.',
    'max_file' => ':attribute은(는) :max KB를 초과할 수 없습니다.',

    // 비밀번호
    'password' => [
        'lowercase' => '비밀번호에 소문자가 포함되어야 합니다.',
        'uppercase' => '비밀번호에 대문자가 포함되어야 합니다.',
        'number' => '비밀번호에 숫자가 포함되어야 합니다.',
        'special' => '비밀번호에 특수문자가 포함되어야 합니다.',
    ],

    // 날짜
    'date_format' => ':attribute은(는) :format 형식이어야 합니다.',
    'after' => ':attribute은(는) :date 이후 날짜여야 합니다.',
    'before' => ':attribute은(는) :date 이전 날짜여야 합니다.',
    'after_or_equal' => ':attribute은(는) :date 이후 날짜이거나 같은 날짜여야 합니다.',
    'before_or_equal' => ':attribute은(는) :date 이전 날짜이거나 같은 날짜여야 합니다.',

    // 속성 이름
    'attributes' => [
        'name' => '이름',
        'email' => '이메일',
        'password' => '비밀번호',
        'password_confirmation' => '비밀번호 확인',
        'phone' => '전화번호',
        'customer_name' => '예약자명',
        'customer_email' => '이메일',
        'customer_phone' => '전화번호',
        'booking_date' => '예약 날짜',
        'start_time' => '시작 시간',
        'guests' => '인원',
        'service_id' => '서비스',
        'category_id' => '카테고리',
        'duration' => '소요시간',
        'price' => '가격',
        'description' => '설명',
        'notes' => '메모',
        'reason' => '사유',
    ],
];
