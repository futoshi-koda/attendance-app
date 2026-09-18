<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_clock_in' => ['required', 'date_format:H:i'],
            'new_clock_out' => ['required', 'date_format:H:i', 'after:new_clock_in'],
            'new_break_in.*' => ['nullable', 'date_format:H:i'],
            'new_break_out.*' => ['nullable', 'date_format:H:i'],
            'comment' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_clock_in.required' => '出勤時間を入力してください',
            'new_clock_in.date_format' => '出勤時間はHH:MM形式で入力してください',
            'new_clock_out.required' => '退勤時間を入力してください',
            'new_clock_out.date_format' => '退勤時間はHH:MM形式で入力してください',
            'new_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_break_in.*.date_format' => '休憩開始時間はHH:MM形式で入力してください',
            'new_break_out.*.date_format' => '休憩終了時間はHH:MM形式で入力してください',
            'comment.required' => '備考を記入してください',
        ];
    }

    /**
     * 相関バリデーション（出勤・退勤時刻と休憩時刻の比較チェック）
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn = $this->input('new_clock_in');
            $clockOut = $this->input('new_clock_out');
            $breakIns = $this->input('new_break_in', []);
            $breakOuts = $this->input('new_break_out', []);

            // 片方入力チェック
            foreach ($breakIns as $index => $breakIn) {
                $breakOut = $breakOuts[$index] ?? null;
                if (($breakIn && !$breakOut) || (!$breakIn && $breakOut)) {
                    $validator->errors()->add(
                        "new_break_in.{$index}",
                        '休憩の開始時間と終了時間は両方入力してください'
                    );
                }
            }

            // 出勤・退勤時刻が揃っている場合のみ休憩範囲の判定を実施
            if ($clockIn && $clockOut) {
                foreach ($breakIns as $index => $breakIn) {
                    $breakOut = $breakOuts[$index] ?? null;

                    // 2. 休憩開始時間が出勤時間より前、または退勤時間より後になっている場合
                    if ($breakIn) {
                        if ($breakIn < $clockIn || $breakIn > $clockOut) {
                            $validator->errors()->add(
                                "new_break_in.{$index}",
                                '休憩時間が不適切な値です'
                            );
                        }
                    }

                    // 3. 休憩終了時間が退勤時間より後になっている場合
                    if ($breakOut) {
                        if ($breakOut > $clockOut) {
                            $validator->errors()->add(
                                "new_break_out.{$index}",
                                '休憩時間もしくは退勤時間が不適切な値です'
                            );
                        }
                    }
                }
            }
        });
    }
}