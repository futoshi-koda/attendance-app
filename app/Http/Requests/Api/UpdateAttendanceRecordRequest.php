<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // ルートパラメータから対象の勤怠レコードIDを取得する
        $attendanceRecord = $this->route('attendanceRecord');
        $attendanceRecordId = is_object($attendanceRecord) ? $attendanceRecord->id : $attendanceRecord;

        return [
            'date' => [
                'sometimes',
                'required',
                'date_format:Y-m-d',
                Rule::unique('attendance_records', 'date')
                    ->where('user_id', $this->user()->id)
                    ->ignore($attendanceRecordId),
            ],
            'clock_in' => ['sometimes', 'required', 'date_format:H:i:s'],
            'clock_out' => ['nullable', 'date_format:H:i:s', 'after:clock_in'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * カスタムエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'date.required' => '勤怠日は必須です。',
            'date.date_format' => '勤怠日は YYYY-MM-DD 形式で指定してください。',
            'date.unique' => 'この日付の勤怠は既に登録されています。',
            'clock_in.required' => '出勤時刻は必須です。',
            'clock_in.date_format' => '出勤時刻は HH:mm:ss 形式で指定してください。',
            'clock_out.date_format' => '退勤時刻は HH:mm:ss 形式で指定してください。',
            'clock_out.after' => '退勤時刻は出勤時刻より後の時刻を指定してください。',
            'comment.max' => '備考は 255 文字以内で入力してください。',
        ];
    }
}